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

        $steelPerSqft = $seismicZoneV ? 4.5 : 3.8;
        $totalSteelKg = $totalBuiltUpSqft * $steelPerSqft;
        $steelTonne = $totalSteelKg / 1000.0;

        $concreteM3 = round($totalBuiltUpSqft * 0.040, 2);
        $totalBricks = (int) round($totalBuiltUpSqft * 8.5);
        $plasterM2 = round($totalBuiltUpSqm * 2.6, 2);
        $flooringM2 = round($totalBuiltUpSqm * 0.75, 2);

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

        $cementBags = (int) ceil(($concreteM3 * 8.4) + ($plasterM2 * 0.18) + ($totalBricks * 0.024));
        $sandCft = round(($concreteM3 * 15.9) + ($plasterM2 * 0.70) + ($totalBricks * 0.08), 1);
        $aggregateCft = round($concreteM3 * 30.0, 1);

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
                    'description' => 'Standard 50kg bags (UltraTech, ACC, Ambuja, Dalmia or equivalent OPC 43/53 or PPC)',
                    'application' => 'Concrete, brick masonry mortar & plastering',
                ],
                'sand_reti_msand' => [
                    'volume_cft' => $sandCft,
                    'volume_brass' => round($sandCft / 100.0, 2),
                    'note' => '1 Brass = 100 cu.ft (Zone II river sand or washed M-Sand)',
                ],
                'coarse_aggregate_rori_gitti' => [
                    'volume_cft' => $aggregateCft,
                    'volume_brass' => round($aggregateCft / 100.0, 2),
                    'note' => '20mm down (60%) & 10mm down (40%) graded blue granite / basalt',
                ],
                'first_class_bricks' => [
                    'total_bricks' => $totalBricks,
                    'description' => 'Red clay bricks / fly ash bricks (IS 2212)',
                ],
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
                'rooms_count' => max(1, (int) round($totalBuiltUpSqft / 400.0)),
                'toilets_count' => max(1, (int) round($totalBuiltUpSqft / 600.0)),
                'balconies_count' => $totalBuiltUpSqft >= 800 ? 1 : 0,
                'floors_count' => $floors,
                'room_size_details' => [
                    'Master Bedroom' => '150.0 sq.ft',
                    'Living/Dining' => '220.0 sq.ft',
                    'Kitchen' => '90.0 sq.ft',
                ],
                'carpet_area_per_floor_sqft' => round($totalBuiltUpSqft * 0.80, 2),
                'walls_and_circulation_sqft' => round($totalBuiltUpSqft * 0.20, 2),
                'calculated_built_up_area_per_floor_sqft' => $builtUpAreaSqft,
                'total_built_up_area_all_floors_sqft' => $totalBuiltUpSqft,
            ],
        ];
    }
}

