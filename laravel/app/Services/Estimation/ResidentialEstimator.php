<?php

namespace App\Services\Estimation;

class ResidentialEstimator
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    public function estimate(
        float $builtUpAreaSqft,
        int $floors = 1,
        bool $seismicZoneV = true
    ): array {
        $totalBuiltUpSqft = $builtUpAreaSqft * $floors;
        $totalBuiltUpSqm = $totalBuiltUpSqft * 0.092903;

        // 1. Structural Concrete & Steel
        $steelPerSqft = $seismicZoneV ? 4.5 : 3.8;
        $totalSteelKg = $totalBuiltUpSqft * $steelPerSqft;
        $steelTonne = $totalSteelKg / 1000.0;
        $concreteM3 = round($totalBuiltUpSqft * 0.040, 2);

        // 2. Masonry & Wet Works
        $totalBricks = (int) round($totalBuiltUpSqft * 8.5);
        $plasterM2 = round($totalBuiltUpSqm * 2.6, 2);
        $plasterSqft = round($plasterM2 * 10.7639, 1);
        $flooringM2 = round($totalBuiltUpSqm * 0.75, 2);
        $flooringSqft = round($flooringM2 * 10.7639, 1);

        // 3. Formwork, Centering & Shuttering
        // Steel / plywood shuttering area is approximately 2.4 times total concrete volume
        $shutteringSqm = round($concreteM3 * 2.45, 1);
        $shutteringSqft = round($shutteringSqm * 10.7639, 1);

        // 4. Site Units: Cement, Sand, Aggregates
        $cementBags = (int) ceil(($concreteM3 * 8.4) + ($plasterM2 * 0.18) + ($totalBricks * 0.024));
        $sandCft = round(($concreteM3 * 15.9) + ($plasterM2 * 0.70) + ($totalBricks * 0.08), 1);
        $sandBrass = round($sandCft / 100.0, 2);
        $aggregateCft = round($concreteM3 * 30.0, 1);
        $aggregateBrass = round($aggregateCft / 100.0, 2);

        // 5. Finishing & Architectural Materials
        // Wall Putty (2 coats): ~0.12 kg per sq.ft of plastered wall area
        $wallPuttyKg = round($plasterSqft * 0.12, 1);
        $wallPuttyBags40kg = (int) ceil($wallPuttyKg / 40.0);

        // Primer (1 coat): ~0.035 litres per sq.ft
        $primerLitres = round($plasterSqft * 0.035, 1);

        // Interior & Exterior Paint Emulsion (2 coats):
        // Interior wall plaster: ~70% of total plaster area, coverage ~0.065 L/sq.ft (for 2 coats)
        // Exterior wall plaster: ~30% of total plaster area, coverage ~0.055 L/sq.ft (for 2 coats exterior weather-coat)
        $interiorPaintLitres = round(($plasterSqft * 0.70) * 0.065, 1);
        $exteriorPaintLitres = round(($plasterSqft * 0.30) * 0.055, 1);

        // Waterproofing:
        // Roof slab terrace area + bathroom sunken slabs (~1.1 times floor plate area)
        $waterproofingAreaSqft = round($builtUpAreaSqft * 1.15, 1);
        $waterproofingAreaSqm = round($waterproofingAreaSqft * 0.092903, 1);
        // Liquid polymer waterproofing chemical (@ 0.85 kg/m2 for 2 coats)
        $waterproofingChemicalKg = round($waterproofingAreaSqm * 0.85, 1);

        // Anti-Termite Pre-Construction Soil Treatment (IS 6313 Part II):
        // Plinth floor area + foundation trench perimeter: ~1.2 times builtUpAreaSqft (at 5 litres/m2 emulsion)
        $antiTermiteAreaSqm = round(($builtUpAreaSqft * 0.092903) * 1.2, 1);
        $antiTermiteEmulsionLitres = round($antiTermiteAreaSqm * 5.0, 1);

        // Tile Adhesive & Epoxy Grout for Vitrified Flooring:
        // Polymer modified tile adhesive (~4.5 kg per m2)
        $tileAdhesiveKg = round($flooringM2 * 4.5, 1);
        $tileAdhesiveBags20kg = (int) ceil($tileAdhesiveKg / 20.0);
        $epoxyGroutKg = round($flooringM2 * 0.40, 1);

        // Openings: Doors, Windows & Hardware Estimates
        $estimatedBedrooms = max(1, (int) round($totalBuiltUpSqft / 450.0));
        $estimatedToilets = max(1, (int) round($totalBuiltUpSqft / 600.0));
        $totalDoors = 1 + $estimatedBedrooms + $estimatedToilets + ($floors > 1 ? ($floors - 1) : 0); // Main + rooms + toilets + balcony
        $totalWindows = max(2, (int) round($estimatedBedrooms * 1.5 + 1));
        $overheadWaterTankLitres = ($estimatedBedrooms + 1) * 350; // 350L per bedroom/occupancy capacity
        $overheadWaterTankLitres = max(1000, (int) (ceil($overheadWaterTankLitres / 500) * 500)); // Round to standard 1000L, 1500L, 2000L

        // Electrical & Plumbing Rough-in Material Estimates:
        // PVC electrical conduit pipes (25mm): ~7.5 running feet per sq.ft of built-up area
        $electricalConduitFeet = round($totalBuiltUpSqft * 7.5, 0);
        $copperWiringMeters = round($totalBuiltUpSqft * 4.2, 0); // Lighting + power circuits
        $pvcPlumbingPipesFeet = round($estimatedToilets * 90.0, 0); // Water supply & sanitary per bathroom

        // Rebar breakdown
        $rates = config('construction.rates.residential', []);
        $rateConcreteM3 = $rates['Item 101']['rate'] ?? 7200.0;
        $rateSteelTonne = $rates['Item 102']['rate'] ?? 82000.0;
        $rateBricks1000 = $rates['Item 103']['rate'] ?? 12500.0;
        $ratePlasterM2 = $rates['Item 104']['rate'] ?? 320.0;
        $rateFlooringM2 = $rates['Item 105']['rate'] ?? 1150.0;

        $costConcrete = $concreteM3 * $rateConcreteM3;
        $costSteel = $steelTonne * $rateSteelTonne;
        $costBricks = ($totalBricks / 1000.0) * $rateBricks1000;
        $costPlaster = $plasterM2 * $ratePlasterM2;
        $costFlooring = $flooringM2 * $rateFlooringM2;
        $totalBaseCost = $costConcrete + $costSteel + $costBricks + $costPlaster + $costFlooring;

        $rebarDetails = $this->rebarService->calculateRebarBreakdown($totalSteelKg, null, $rateSteelTonne);

        return [
            'project_type' => 'RCC Framed Residential Building (Assam PWD Building SOR 2025-2026)',
            'scope' => 'Low-rise G+' . ($floors > 1 ? ($floors - 1) : 0) . " ({$floors} level(s)) across Kamrup Metro/Dibrugarh/Silchar/Jorhat",
            'inputs' => [
                'floor_plate_sqft' => $builtUpAreaSqft,
                'number_of_floors' => $floors,
                'total_built_up_area_sqft' => round($totalBuiltUpSqft, 2),
                'total_built_up_area_sqm' => round($totalBuiltUpSqm, 2),
                'seismic_zone_v' => $seismicZoneV,
            ],
            // Primary Indian site logistics
            'indian_market_materials' => [
                'sariya_tmt_steel' => [
                    'total_tonnes' => round($steelTonne, 3),
                    'total_quintals' => round($totalSteelKg / 100.0, 2),
                    'total_kg' => round($totalSteelKg, 2),
                    'total_12m_commercial_rods' => $rebarDetails['total_standard_12m_rods'],
                    'binding_wire_kg' => $rebarDetails['binding_wire_kg'],
                    'grade' => 'Fe 500D (IS 1786 High Ductility)',
                    'approx_cost_inr' => round($costSteel, 2),
                ],
                'cement_bags_50kg' => [
                    'total_bags' => $cementBags,
                    'description' => 'Standard 50kg bags (UltraTech, ACC, Dalmia or equivalent OPC 43/53 or PPC)',
                    'application' => 'Concrete, brick masonry mortar & plastering',
                ],
                'sand_reti_msand' => [
                    'volume_cft' => $sandCft,
                    'volume_brass' => $sandBrass,
                    'note' => '1 Brass = 100 cu.ft (Zone II river sand or washed M-Sand)',
                ],
                'coarse_aggregate_rori_gitti' => [
                    'volume_cft' => $aggregateCft,
                    'volume_brass' => $aggregateBrass,
                    'note' => '20mm down (60%) & 10mm down (40%) graded blue granite / basalt',
                ],
                'first_class_bricks' => [
                    'total_bricks' => $totalBricks,
                    'description' => 'Red clay bricks / fly ash bricks (IS 2212)',
                ],
            ],
            // Comprehensive lifecycle inventory
            'lifecycle_materials_inventory' => [
                'phase_1_structural' => [
                    'title' => 'Structural Core & Foundation',
                    'items' => [
                        ['material' => 'M25 Structural Concrete', 'quantity' => "{$concreteM3} cu.m", 'packaging' => 'Design Mix / RMC', 'code' => 'IS 456'],
                        ['material' => 'Fe500D TMT Steel Rods (12m Rods)', 'quantity' => "{$rebarDetails['total_standard_12m_rods']} Rods", 'packaging' => round($steelTonne, 2) . ' Tonnes (' . round($totalSteelKg / 100, 1) . ' Quintals)', 'code' => 'IS 1786'],
                        ['material' => 'Binding Wire (18 Gauge G.I.)', 'quantity' => "{$rebarDetails['binding_wire_kg']} kg", 'packaging' => 'Coils (~12 kg/Tonne)', 'code' => 'IS 280'],
                        ['material' => 'Shuttering & Formwork Centering', 'quantity' => "{$shutteringSqft} sq.ft", 'packaging' => "{$shutteringSqm} sq.m (Steel / Plywood)", 'code' => 'IS 14687'],
                        ['material' => 'RCC Cover Blocks (20/25/40mm)', 'quantity' => (int) round($totalBuiltUpSqft * 1.8) . ' Nos', 'packaging' => 'Precast High-Density PVC/Mortar', 'code' => 'SP 34'],
                    ]
                ],
                'phase_2_masonry_wet' => [
                    'title' => 'Masonry, Plaster & Earth Treatment',
                    'items' => [
                        ['material' => '1st Class Bricks (IS 2212)', 'quantity' => number_format($totalBricks) . ' Nos', 'packaging' => 'Clay / Fly Ash', 'code' => 'IS 2212'],
                        ['material' => 'Cement (50kg Bags)', 'quantity' => "{$cementBags} Bags", 'packaging' => '50kg Moisture-Proof Bags', 'code' => 'IS 8112 / IS 1489'],
                        ['material' => 'Fine River Sand / M-Sand', 'quantity' => "{$sandBrass} Brass", 'packaging' => "{$sandCft} CFT (Zone II Sand)", 'code' => 'IS 383'],
                        ['material' => 'Coarse Aggregate (10/20mm)', 'quantity' => "{$aggregateBrass} Brass", 'packaging' => "{$aggregateCft} CFT (Graded Granite)", 'code' => 'IS 383'],
                        ['material' => 'Anti-Termite Soil Treatment', 'quantity' => "{$antiTermiteEmulsionLitres} Litres", 'packaging' => 'Chlorpyrifos/Imidacloprid Emulsion', 'code' => 'IS 6313 Part II'],
                    ]
                ],
                'phase_3_finishes_waterproofing' => [
                    'title' => 'Waterproofing, Putty & Paint Finishes',
                    'items' => [
                        ['material' => 'Terrace & Wet Area Waterproofing', 'quantity' => "{$waterproofingAreaSqft} sq.ft", 'packaging' => "{$waterproofingChemicalKg} kg Polymer Slurry Membrane", 'code' => 'IS 3067'],
                        ['material' => 'Wall Care Putty (2 Coats)', 'quantity' => "{$wallPuttyKg} kg", 'packaging' => "{$wallPuttyBags40kg} Bags (40kg White Cement Putty)", 'code' => 'Birla/JK'],
                        ['material' => 'Acrylic Wall Primer (1 Coat)', 'quantity' => "{$primerLitres} Litres", 'packaging' => 'Water-based Primer Buckets', 'code' => 'Interior/Exterior'],
                        ['material' => 'Interior Wall Paint Emulsion', 'quantity' => "{$interiorPaintLitres} Litres", 'packaging' => 'Premium Acrylic Emulsion (2 Coats)', 'code' => 'IS 5411'],
                        ['material' => 'Exterior Weather-Proof Emulsion', 'quantity' => "{$exteriorPaintLitres} Litres", 'packaging' => '100% Acrylic Anti-Fungal Weather Guard', 'code' => 'IS 15489'],
                        ['material' => 'Vitrified Floor Tiles (600x600mm)', 'quantity' => "{$flooringSqft} sq.ft", 'packaging' => "{$flooringM2} sq.m Double-Charged Vitrified", 'code' => 'IS 15622'],
                        ['material' => 'Tile Adhesive (Polymer Base)', 'quantity' => "{$tileAdhesiveKg} kg", 'packaging' => "{$tileAdhesiveBags20kg} Bags (20kg Adhesive)", 'code' => 'IS 15477'],
                        ['material' => 'Waterproof Epoxy Tile Grout', 'quantity' => "{$epoxyGroutKg} kg", 'packaging' => 'Solid Epoxy Grout Packs', 'code' => 'ANSI A118.3'],
                    ]
                ],
                'phase_4_fixtures_mep' => [
                    'title' => 'Openings, Plumbing & Electrical Rough-Ins',
                    'items' => [
                        ['material' => 'Door Frames & Flush Doors', 'quantity' => "{$totalDoors} Sets", 'packaging' => 'Hardwood / Steel Chowkhat + 32mm Flush Doors', 'code' => 'IS 2202'],
                        ['material' => 'UPVC / Powder-Coated Windows', 'quantity' => "{$totalWindows} Sets", 'packaging' => 'Double-Track Glazed with Mosquito Mesh', 'code' => 'IS 16098'],
                        ['material' => 'Overhead Clean Water Tank', 'quantity' => "{$overheadWaterTankLitres} Litres", 'packaging' => 'Triple-Layer Food Grade Polyethylene Tank', 'code' => 'Sintex/Plasto'],
                        ['material' => 'Electrical Heavy PVC Conduits (25mm)', 'quantity' => "{$electricalConduitFeet} Rft", 'packaging' => 'Rigid PVC Flame Retardant Conduits', 'code' => 'IS 9537'],
                        ['material' => 'FR Copper Electrical Wiring', 'quantity' => "{$copperWiringMeters} Metres", 'packaging' => 'Flame Retardant 1.5/2.5/4.0 sq.mm Wires', 'code' => 'IS 694'],
                        ['material' => 'CPVC & SWR Plumbing Pipes', 'quantity' => "{$pvcPlumbingPipesFeet} Rft", 'packaging' => 'SDR 11 CPVC & Class B SWR Drainage Pipes', 'code' => 'IS 15778 / IS 13592'],
                    ]
                ]
            ],
            'material_quantities' => [
                'steel_fe500d' => [
                    'total_kg' => round($totalSteelKg, 2),
                    'total_tonnes' => round($steelTonne, 3),
                    'total_12m_rods' => $rebarDetails['total_standard_12m_rods'],
                    'binding_wire_kg' => $rebarDetails['binding_wire_kg'],
                    'standard' => 'IS 1786 Fe500D (high ductility)',
                    'unit_rate_inr' => $rateSteelTonne,
                    'subtotal_cost_inr' => round($costSteel, 2),
                ],
                'concrete_m25' => [
                    'volume_m3' => $concreteM3,
                    'description' => 'Footings, plinth beams, columns, lintels, 130mm slabs',
                    'standard' => 'IS 456:2000 (1:1.5:3 or design mix)',
                    'unit_rate_inr' => $rateConcreteM3,
                    'subtotal_cost_inr' => round($costConcrete, 2),
                ],
                'brickwork_masonry' => [
                    'total_bricks_count' => $totalBricks,
                    'description' => '9" exterior and 4.5" interior in CM 1:6',
                    'standard' => 'IS 2212 1st Class Bricks',
                    'unit_rate_per_1000_inr' => $rateBricks1000,
                    'subtotal_cost_inr' => round($costBricks, 2),
                ],
                'plastering' => [
                    'area_m2' => $plasterM2,
                    'description' => '12mm internal (1:6) & 15mm external waterproof (1:4)',
                    'standard' => 'IS 1661',
                    'unit_rate_inr' => $ratePlasterM2,
                    'subtotal_cost_inr' => round($costPlaster, 2),
                ],
                'vitrified_tiles' => [
                    'area_m2' => $flooringM2,
                    'description' => '600x600mm double-charged tiles over 20mm mortar',
                    'standard' => 'IS 15622',
                    'unit_rate_inr' => $rateFlooringM2,
                    'subtotal_cost_inr' => round($costFlooring, 2),
                ],
            ],
            'rebar_breakdown' => $rebarDetails,
            'cost_summary' => [
                'steel_cost_inr' => round($costSteel, 2),
                'concrete_cost_inr' => round($costConcrete, 2),
                'brickwork_cost_inr' => round($costBricks, 2),
                'plastering_cost_inr' => round($costPlaster, 2),
                'flooring_cost_inr' => round($costFlooring, 2),
                'total_estimated_materials_cost_inr' => round($totalBaseCost, 2),
                'cost_per_sqft_inr' => round($totalBaseCost / $totalBuiltUpSqft, 2),
            ],
            'seismic_safety_provisions' => [
                'zone' => 'Seismic Zone V',
                'code' => 'IS 13920:2016',
                'requirement' => 'Ductile confinement links with 135-degree hooks and 10d extension at all beam-column junctions.',
            ],
            'architectural_configuration' => [
                'rooms_count' => $estimatedBedrooms,
                'toilets_count' => $estimatedToilets,
                'balconies_count' => $totalBuiltUpSqft >= 800 ? 1 : 0,
                'floors_count' => $floors,
                'doors_count' => $totalDoors,
                'windows_count' => $totalWindows,
                'carpet_area_per_floor_sqft' => round($totalBuiltUpSqft * 0.80, 2),
                'walls_and_circulation_sqft' => round($totalBuiltUpSqft * 0.20, 2),
                'calculated_built_up_area_per_floor_sqft' => $builtUpAreaSqft,
                'total_built_up_area_all_floors_sqft' => $totalBuiltUpSqft,
            ],
        ];
    }
}
