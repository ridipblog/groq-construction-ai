<?php

namespace App\Services\Estimation;

class CommercialBuildingEstimator
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    /**
     * Estimate complete structural quantities and lifecycle costs for multi-storey commercial buildings
     *
     * @param float $builtUpAreaSqft Total built-up area across all floors or floor plate in sq.ft
     * @param int $floors Number of above-ground storeys (G=1, G+1=2, G+2=3, etc.)
     * @param bool $hasBasement Whether the building includes an underground basement parking/storage level
     * @param string $occupancyType Occupancy type: 'office', 'retail_mall', 'hotel', 'hospital', 'mixed_use'
     * @return array
     */
    public function estimate(
        float $builtUpAreaSqft,
        int $floors = 3,
        bool $hasBasement = false,
        string $occupancyType = 'office'
    ): array {
        $totalAboveGroundSqft = $builtUpAreaSqft * $floors;
        $basementSqft = $hasBasement ? $builtUpAreaSqft : 0.0;
        $grandTotalBuiltUpSqft = $totalAboveGroundSqft + $basementSqft;
        $grandTotalBuiltUpSqm = $grandTotalBuiltUpSqft * 0.092903;

        // 1. High-Density Commercial Concrete & Steel Factors
        // Commercial framed structures (larger spans, heavier live loads 4-5 kN/m2 per IS 875 Part 2)
        // Steel consumption: ~5.8 kg/sq.ft for superstructure Fe500D; extra for basement raft if present
        $superstructureSteelKg = $totalAboveGroundSqft * 5.8;
        $basementSteelKg = $hasBasement ? ($basementSqft * 7.2) : 0.0; // Heavy basement raft & retaining walls
        $totalSteelKg = round($superstructureSteelKg + $basementSteelKg, 2);
        $totalSteelTonnes = round($totalSteelKg / 1000.0, 3);

        // M30 Structural Concrete: 0.048 cu.m per sq.ft for superstructure
        $superstructureConcreteM3 = round($totalAboveGroundSqft * 0.048, 2);
        // Basement Raft & Retaining wall concrete: ~0.065 cu.m per sq.ft of basement area
        $basementConcreteM3 = $hasBasement ? round($basementSqft * 0.065, 2) : 0.0;
        $totalConcreteM3 = round($superstructureConcreteM3 + $basementConcreteM3, 2);

        // 2. NBC 2016 Part 4 Fire-Water Static Storage Sizing
        // Commercial buildings require dedicated underground static fire reserves based on floor area & occupancy
        // Base allowance: minimum 50,000 litres for low-rise commercial; 100,000 to 200,000 litres for multi-storey
        $fireReserveLitres = 50000;
        if ($grandTotalBuiltUpSqft > 20000) {
            $fireReserveLitres = 150000;
        } elseif ($grandTotalBuiltUpSqft > 10000) {
            $fireReserveLitres = 100000;
        }
        $fireTankCapacityM3 = round($fireReserveLitres / 1000.0, 1);
        // Fire static tank RCC structure volume: ~0.35 m3 concrete per m3 of water capacity
        $fireTankConcreteM3 = round($fireTankCapacityM3 * 0.35, 2);

        // 3. Masonry & Wall Partitions
        // Commercial buildings typically use 4.5" AAC blocks / clay bricks for perimeter and shaft enclosures: ~4.5 bricks per sq.ft
        $totalBricksCount = (int) round($grandTotalBuiltUpSqft * 4.5);
        $plasterM2 = round($grandTotalBuiltUpSqm * 1.8, 2);
        $flooringM2 = round($grandTotalBuiltUpSqm * 0.85, 2);

        // 4. Site Units: Cement, Sand, Aggregates
        $cementBags = (int) ceil(($totalConcreteM3 * 8.6) + ($fireTankConcreteM3 * 8.6) + ($plasterM2 * 0.18) + ($totalBricksCount * 0.024));
        $sandCft = round(($totalConcreteM3 * 15.5) + ($plasterM2 * 0.70) + ($totalBricksCount * 0.08), 1);
        $sandBrass = round($sandCft / 100.0, 2);
        $aggregateCft = round($totalConcreteM3 * 30.0, 1);
        $aggregateBrass = round($aggregateCft / 100.0, 2);

        // 5. Rates from config
        $rates = config('construction.rates.commercial', []);
        $rateM30Concrete = $rates['concrete_m30']['rate'] ?? 8100.0;
        $rateSteelTonne = $rates['steel_fe500d']['rate'] ?? 82500.0;
        $rateBasementWall = $rates['basement_retaining_wall']['rate'] ?? 8600.0;
        $rateFireTank = $rates['fire_reserve_water_tank']['rate'] ?? 8400.0;
        $rateFlooringMep = $rates['commercial_flooring_mep']['rate'] ?? 1650.0;

        $costSuperConcrete = $superstructureConcreteM3 * $rateM30Concrete;
        $costBasementConcrete = $basementConcreteM3 * $rateBasementWall;
        $costSteel = $totalSteelTonnes * $rateSteelTonne;
        $costFireTank = $fireTankConcreteM3 * $rateFireTank;
        $costFlooringMep = $flooringM2 * $rateFlooringMep;

        $totalEstimatedCost = $costSuperConcrete + $costBasementConcrete + $costSteel + $costFireTank + $costFlooringMep;

        // Rebar breakdown (heavier 25mm and 20mm bars for commercial columns and beams)
        $rebarDistribution = [25 => 0.35, 20 => 0.30, 16 => 0.20, 12 => 0.10, 8 => 0.05];
        $rebarBreakdown = $this->rebarService->calculateRebarBreakdown(
            $totalSteelKg,
            $rebarDistribution,
            $rateSteelTonne
        );

        return [
            'project_type' => 'Commercial Framed Building Complex (Assam PWD SOR & NBC 2016)',
            'scope' => ucfirst($occupancyType) . " commercial building with {$floors} storey(s)" . ($hasBasement ? ' + underground basement' : '') . " across {$grandTotalBuiltUpSqft} sq.ft total built-up area",
            'inputs' => [
                'floor_plate_sqft' => $builtUpAreaSqft,
                'number_of_floors' => $floors,
                'has_basement' => $hasBasement,
                'occupancy_type' => $occupancyType,
                'above_ground_built_up_sqft' => $totalAboveGroundSqft,
                'basement_built_up_sqft' => $basementSqft,
                'grand_total_built_up_area_sqft' => $grandTotalBuiltUpSqft,
                'grand_total_built_up_area_sqm' => round($grandTotalBuiltUpSqm, 2),
            ],
            'structural_elements' => [
                'concrete_m30_superstructure_m3' => $superstructureConcreteM3,
                'concrete_m30_basement_m3' => $basementConcreteM3,
                'total_concrete_volume_m3' => $totalConcreteM3,
                'steel_consumption_rate' => '5.8 kg/sq.ft (Superstructure) / 7.2 kg/sq.ft (Basement Raft)',
                'total_steel_tonnes' => $totalSteelTonnes,
                'total_steel_kg' => $totalSteelKg,
            ],
            'fire_safety_provisions' => [
                'nbc_standard' => 'NBC 2016 Part 4 Fire and Life Safety',
                'static_fire_reserve_litres' => $fireReserveLitres,
                'fire_tank_concrete_volume_m3' => $fireTankConcreteM3,
                'mandated_systems' => 'Wet riser with landing valves, automatic sprinkler grid in basement & retail spaces, external yard hydrants, and dual electrical/diesel fire pumps.',
            ],
            'indian_market_materials' => [
                'sariya_tmt_steel' => [
                    'total_tonnes' => $totalSteelTonnes,
                    'total_kg' => $totalSteelKg,
                    'total_12m_commercial_rods' => $rebarBreakdown['total_standard_12m_rods'],
                    'binding_wire_kg' => $rebarBreakdown['binding_wire_kg'],
                    'grade' => 'Fe500D High Ductility (IS 1786)',
                ],
                'cement_bags_50kg' => [
                    'total_bags' => $cementBags,
                    'description' => '50kg bags OPC 53 Grade / Design Mix RMC M30',
                ],
                'sand_reti_msand' => [
                    'volume_cft' => $sandCft,
                    'volume_brass' => $sandBrass,
                    'note' => '1 Brass = 100 cu.ft Zone II river sand or washed M-Sand',
                ],
                'coarse_aggregate_rori_gitti' => [
                    'volume_cft' => $aggregateCft,
                    'volume_brass' => $aggregateBrass,
                    'note' => '20mm & 10mm down graded crushed basalt/granite',
                ],
                'masonry_blocks_bricks' => [
                    'total_units' => $totalBricksCount,
                    'spec' => 'Clay bricks or AAC blocks for exterior cavity & interior shafts',
                ]
            ],
            'rebar_breakdown' => $rebarBreakdown,
            'cost_breakdown' => [
                'superstructure_concrete_m30_inr' => round($costSuperConcrete, 2),
                'basement_retaining_and_raft_inr' => round($costBasementConcrete, 2),
                'steel_fe500d_reinforcement_inr' => round($costSteel, 2),
                'nbc_fire_reserve_tank_inr' => round($costFireTank, 2),
                'commercial_flooring_core_mep_inr' => round($costFlooringMep, 2),
                'total_estimated_materials_cost_inr' => round($totalEstimatedCost, 2),
                'cost_per_sqft_inr' => round($totalEstimatedCost / max(1.0, $grandTotalBuiltUpSqft), 2),
            ],
            'lifecycle_materials_inventory' => [
                'phase_1_structural' => [
                    'title' => 'Core Structural Frame & Raft',
                    'items' => [
                        ['material' => 'M30 Design Mix Concrete', 'quantity' => "{$totalConcreteM3} cu.m", 'packaging' => 'Transit Mixer RMC Pumped', 'code' => 'IS 456'],
                        ['material' => 'Fe500D TMT Steel Rods (12m Rods)', 'quantity' => "{$rebarBreakdown['total_standard_12m_rods']} Rods", 'packaging' => "{$totalSteelTonnes} Tonnes", 'code' => 'IS 1786'],
                        ['material' => 'Binding Wire (18 Gauge)', 'quantity' => "{$rebarBreakdown['binding_wire_kg']} kg", 'packaging' => 'Coils (~12 kg/Tonne)', 'code' => 'IS 280'],
                        ['material' => 'NBC Fire Reserve Tank', 'quantity' => "{$fireTankCapacityM3} cu.m (150kL)", 'packaging' => 'Underground RCC Monolithic Tank', 'code' => 'NBC Part 4'],
                    ]
                ],
                'phase_2_masonry_wet' => [
                    'title' => 'Masonry, Shafts & Fire Barriers',
                    'items' => [
                        ['material' => 'Masonry Blocks / Bricks', 'quantity' => number_format($totalBricksCount) . ' Nos', 'packaging' => 'AAC / First Class Clay Bricks', 'code' => 'IS 2185'],
                        ['material' => 'Cement (50kg Bags)', 'quantity' => "{$cementBags} Bags", 'packaging' => 'OPC 53 / PPC Moisture-Proof Bags', 'code' => 'IS 12269'],
                        ['material' => 'River Sand / M-Sand', 'quantity' => "{$sandBrass} Brass", 'packaging' => "{$sandCft} CFT", 'code' => 'IS 383'],
                    ]
                ],
                'phase_3_finishes_waterproofing' => [
                    'title' => 'Flooring, Putty & Core Architectural',
                    'items' => [
                        ['material' => 'Heavy Duty Vitrified Tiles (800x800mm)', 'quantity' => "{$flooringM2} sq.m", 'packaging' => 'Full-body Vitrified Commercial Flooring', 'code' => 'IS 15622'],
                        ['material' => 'Basement & Terrace Waterproofing', 'quantity' => round($builtUpAreaSqft * 0.092903 * 1.2, 1) . ' sq.m', 'packaging' => 'SBS Bituminous / Polyurethane Membrane', 'code' => 'IS 1346'],
                    ]
                ],
                'phase_4_fixtures_mep' => [
                    'title' => 'Fire Protection & Electrical Risers',
                    'items' => [
                        ['material' => 'Fire Wet Riser & Sprinkler Piping', 'quantity' => '100mm-150mm MS Heavy Class', 'packaging' => 'Schedule 40 Red Primed Pipes', 'code' => 'IS 1239'],
                        ['material' => 'Commercial Electrical Busduct / Cables', 'quantity' => 'Heavy XLPE Armoured Aluminium/Copper', 'packaging' => '1100V Grade Multi-Core Risers', 'code' => 'IS 7098'],
                    ]
                ]
            ]
        ];
    }
}

