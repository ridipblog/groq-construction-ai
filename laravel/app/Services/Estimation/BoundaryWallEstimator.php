<?php

namespace App\Services\Estimation;

class BoundaryWallEstimator
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    /**
     * Estimate complete quantities and costs for boundary wall construction
     *
     * @param float $lengthM Length of perimeter boundary wall in metres
     * @param float $heightM Superstructure height above ground in metres (e.g., 1.8m - 2.4m)
     * @param float $wallThicknessMm Brick wall thickness in mm: 115mm (4.5") or 230mm (9")
     * @param float $pillarSpacingM Centre-to-centre spacing of RCC tie columns in metres (default 3.0m)
     * @param float $foundationDepthM Foundation excavation depth below natural ground level in metres (default 0.9m)
     * @param string $fencingType Security fencing: 'concertina_razor_coil', 'barbed_wire_3strand', or 'none'
     * @return array
     */
    public function estimate(
        float $lengthM,
        float $heightM = 2.0,
        float $wallThicknessMm = 230.0,
        float $pillarSpacingM = 3.0,
        float $foundationDepthM = 0.9,
        string $fencingType = 'concertina_razor_coil'
    ): array {
        $wallThicknessM = $wallThicknessMm / 1000.0;
        $numPillars = (int) ceil($lengthM / max(1.0, $pillarSpacingM)) + 1;

        // 1. Earthwork Excavation & Foundation Bedding
        // Trench width: wall thickness + 0.3m working space (minimum 0.6m)
        $trenchWidthM = max(0.60, $wallThicknessM + 0.30);
        $earthworkM3 = round($lengthM * $trenchWidthM * $foundationDepthM, 2);

        // PCC 1:4:8 Bedding (100mm = 0.10m thick)
        $pccThicknessM = 0.10;
        $pccVolumeM3 = round($lengthM * $trenchWidthM * $pccThicknessM, 2);

        // 2. Concrete Works: RCC Tie Columns, Plinth Beam Band, and Top Weather Coping
        // Columns: 230mm x 230mm from footing top up to wall top
        $columnSectionArea = 0.23 * 0.23;
        $columnHeightTotalM = $heightM + ($foundationDepthM - $pccThicknessM);
        $columnsConcreteM3 = $numPillars * ($columnSectionArea * $columnHeightTotalM);

        // Plinth beam band at ground level: 230mm x 230mm
        $plinthBeamM3 = $lengthM * 0.23 * 0.23;

        // Top Weather Coping (IS 456): 75mm (0.075m) thick with 25mm throating projection
        $copingWidthM = $wallThicknessM + 0.05;
        $copingConcreteM3 = $lengthM * $copingWidthM * 0.075;

        $totalRccConcreteM3 = round($columnsConcreteM3 + $plinthBeamM3 + $copingConcreteM3, 2);

        // 3. Steel Reinforcement (Fe500D)
        // 4 nos 12mm main bars per column + 4 nos 10mm in plinth beam + 2 nos 8mm in coping + 8mm stirrups @ 150mm c/c
        // Structural empirical average: ~85 kg steel per cu.m of RCC elements
        $totalSteelKg = round($totalRccConcreteM3 * 85.0, 2);
        $totalSteelTonnes = round($totalSteelKg / 1000.0, 3);

        // 4. Brickwork Masonry (IS 2212 / IS 1905)
        // Deduct column widths from net brick wall length
        $netBrickLengthM = max(0.0, $lengthM - ($numPillars * 0.23));
        $brickHeightM = $heightM - 0.075; // Subtract coping
        $brickVolumeM3 = $netBrickLengthM * $brickHeightM * $wallThicknessM;
        // Standard red clay bricks: ~500 bricks per cu.m
        $totalBricksCount = (int) round($brickVolumeM3 * 500);

        // 5. Plastering (IS 1661)
        // 15mm cement plaster on both outer and inner faces of wall + column surfaces
        $wallSurfaceAreaBothSidesM2 = round(2.0 * ($lengthM * $heightM), 2);
        $plasterM2 = $wallSurfaceAreaBothSidesM2;

        // 6. Site Units: Cement, Sand, Aggregates
        $cementBags = (int) ceil(($totalRccConcreteM3 * 8.2) + ($pccVolumeM3 * 3.4) + ($plasterM2 * 0.18) + ($totalBricksCount * 0.024));
        $sandCft = round(($totalRccConcreteM3 * 15.5) + ($pccVolumeM3 * 14.0) + ($plasterM2 * 0.70) + ($totalBricksCount * 0.08), 1);
        $sandBrass = round($sandCft / 100.0, 2);
        $coarseAggregateCft = round(($totalRccConcreteM3 * 30.0) + ($pccVolumeM3 * 28.0), 1);
        $coarseAggregateBrass = round($coarseAggregateCft / 100.0, 2);

        // 7. Rates & Pricing from config
        $rates = config('construction.rates.boundary_wall', []);
        $rateEarthwork = $rates['earthwork_excavation']['rate'] ?? 280.0;
        $ratePcc = $rates['pcc_1_4_8']['rate'] ?? 5200.0;
        $rateBrickPer1000 = $rates['brickwork']['rate'] ?? 12500.0;
        $rateRccM25 = $rates['rcc_m25']['rate'] ?? 7200.0;
        $rateSteelTonne = $rates['steel_fe500d']['rate'] ?? 82000.0;
        $ratePlasterM2 = $rates['cement_plaster_1_4']['rate'] ?? 320.0;
        $rateConcertina = $rates['concertina_razor_coil']['rate'] ?? 580.0;
        $rateBarbedWire = $rates['barbed_wire_3strand']['rate'] ?? 220.0;

        $costEarthwork = $earthworkM3 * $rateEarthwork;
        $costPcc = $pccVolumeM3 * $ratePcc;
        $costBricks = ($totalBricksCount / 1000.0) * $rateBrickPer1000;
        $costConcrete = $totalRccConcreteM3 * $rateRccM25;
        $costSteel = $totalSteelTonnes * $rateSteelTonne;
        $costPlaster = $plasterM2 * $ratePlasterM2;

        $costFencing = 0.0;
        $fencingName = 'None';
        if ($fencingType === 'concertina_razor_coil') {
            $costFencing = $lengthM * $rateConcertina;
            $fencingName = 'Concertina Razor Coil 450mm dia with Y-Arms & 3-Strand Barbed Wire';
        } elseif ($fencingType === 'barbed_wire_3strand') {
            $costFencing = $lengthM * $rateBarbedWire;
            $fencingName = 'Standard 3-Strand G.I. Barbed Wire Fencing with Angle Iron Extensions';
        }

        $totalCost = $costEarthwork + $costPcc + $costBricks + $costConcrete + $costSteel + $costPlaster + $costFencing;

        // Rebar breakdown (12mm for columns, 10mm for plinth, 8mm for ties & coping)
        $rebarDistribution = [12 => 0.45, 10 => 0.30, 8 => 0.25];
        $rebarBreakdown = $this->rebarService->calculateRebarBreakdown(
            $totalSteelKg,
            $rebarDistribution,
            $rateSteelTonne
        );

        return [
            'project_type' => 'Perimeter RCC & Brickwork Boundary Wall (Assam PWD SOR 2025-2026)',
            'scope' => "Boundary wall of length {$lengthM}m, superstructure height {$heightM}m, {$wallThicknessMm}mm thickness with RCC posts @ {$pillarSpacingM}m c/c",
            'inputs' => [
                'length_m' => $lengthM,
                'height_m' => $heightM,
                'wall_thickness_mm' => $wallThicknessMm,
                'pillar_spacing_m' => $pillarSpacingM,
                'foundation_depth_m' => $foundationDepthM,
                'total_rcc_pillars_count' => $numPillars,
                'security_fencing_type' => $fencingType,
            ],
            'structural_elements' => [
                'earthwork_excavation_m3' => $earthworkM3,
                'pcc_bedding_1_4_8_m3' => $pccVolumeM3,
                'rcc_m25_concrete_m3' => $totalRccConcreteM3,
                'brickwork_volume_m3' => round($brickVolumeM3, 2),
                'total_bricks_count' => $totalBricksCount,
                'plaster_area_both_faces_m2' => $plasterM2,
                'weather_coping_spec' => '75mm precast/cast-in-situ M25 concrete with drip throating',
            ],
            'indian_market_materials' => [
                'sariya_tmt_steel' => [
                    'total_tonnes' => $totalSteelTonnes,
                    'total_kg' => $totalSteelKg,
                    'total_12m_commercial_rods' => $rebarBreakdown['total_standard_12m_rods'],
                    'binding_wire_kg' => $rebarBreakdown['binding_wire_kg'],
                    'grade' => 'Fe500D (IS 1786)',
                ],
                'cement_bags_50kg' => [
                    'total_bags' => $cementBags,
                    'description' => '50kg bags OPC 43/53 or PPC for foundation, masonry & plastering',
                ],
                'sand_reti_msand' => [
                    'volume_cft' => $sandCft,
                    'volume_brass' => $sandBrass,
                    'note' => '1 Brass = 100 cu.ft Zone II river sand or washed M-Sand',
                ],
                'coarse_aggregate_rori_gitti' => [
                    'volume_cft' => $coarseAggregateCft,
                    'volume_brass' => $coarseAggregateBrass,
                    'note' => 'Graded blue granite aggregate (40mm for PCC, 20mm/10mm for RCC)',
                ],
                'first_class_bricks' => [
                    'total_bricks' => $totalBricksCount,
                    'standard' => 'IS 2212 1st Class Red Clay / Fly Ash Bricks',
                ],
                'security_fencing' => [
                    'type' => $fencingName,
                    'length_m' => $lengthM,
                ]
            ],
            'rebar_breakdown' => $rebarBreakdown,
            'cost_breakdown' => [
                'earthwork_excavation_inr' => round($costEarthwork, 2),
                'pcc_foundation_bed_inr' => round($costPcc, 2),
                'rcc_columns_plinth_coping_inr' => round($costConcrete, 2),
                'steel_reinforcement_inr' => round($costSteel, 2),
                'brickwork_masonry_inr' => round($costBricks, 2),
                'cement_plastering_inr' => round($costPlaster, 2),
                'security_fencing_inr' => round($costFencing, 2),
                'total_cost_inr' => round($totalCost, 2),
                'cost_per_linear_metre_inr' => round($totalCost / max(1.0, $lengthM), 2),
            ],
            'construction_notes' => [
                'coping_and_weep_holes' => 'Provide 75mm RCC weather coping with 25mm projection on both sides. In retained water/earth conditions, 75mm PVC weep holes must be placed at 1.5m centres.',
                'expansion_joints' => 'Provide 20mm expansion joints with bitumen filler board at every 30m length of continuous boundary wall to accommodate thermal movement.',
            ]
        ];
    }
}

