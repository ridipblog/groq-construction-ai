<?php

namespace App\Services\Estimation;

class WarehousePebEstimator
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    /**
     * Estimate complete quantities and costs for industrial Pre-Engineered Steel Buildings (PEB)
     *
     * @param float $lengthM Warehouse clear internal length in metres
     * @param float $widthM Clear clear span width in metres (e.g. 15m - 40m)
     * @param float $eaveHeightM Eave height in metres (default 6.0m - 9.0m)
     * @param string $floorLoadGrade Flooring traffic grade: 'medium_duty' (150mm M25), 'heavy_duty' (200mm M25/M30), or 'light_duty' (120mm M20)
     * @param float $baySpacingM Distance between portal frame bays in metres (default 6.0m)
     * @param float $dadoWallHeightM Height of perimeter brick splash/dado wall in metres (default 2.1m)
     * @return array
     */
    public function estimate(
        float $lengthM,
        float $widthM,
        float $eaveHeightM = 7.0,
        string $floorLoadGrade = 'medium_duty',
        float $baySpacingM = 6.0,
        float $dadoWallHeightM = 2.1
    ): array {
        $floorAreaM2 = round($lengthM * $widthM, 2);
        $floorAreaSqft = round($floorAreaM2 * 10.7639, 2);
        $perimeterM = round(2.0 * ($lengthM + $widthM), 2);

        $numBays = (int) ceil($lengthM / max(4.0, $baySpacingM));
        $numPortalFrames = $numBays + 1;
        $numColumns = $numPortalFrames * 2;

        // 1. Structural Steel Estimations (IS 800 / IS 808 / IS 801)
        // Primary built-up steel (Rigid portal frames, tapered rafters, columns): ~18 to 26 kg/m2 of floor area
        $primarySteelRateKgPerM2 = ($widthM > 25.0) ? 24.0 : 20.0;
        $primarySteelTonnage = round(($floorAreaM2 * $primarySteelRateKgPerM2) / 1000.0, 3);
        $primarySteelKg = round($primarySteelTonnage * 1000.0, 2);

        // Secondary cold-formed steel (Z & C purlins, girts, eave struts, sag rods): ~8 to 11 kg/m2
        $secondarySteelRateKgPerM2 = 9.5;
        $secondarySteelTonnage = round(($floorAreaM2 * $secondarySteelRateKgPerM2) / 1000.0, 3);
        $secondarySteelKg = round($secondarySteelTonnage * 1000.0, 2);

        $totalPebSteelTonnage = round($primarySteelTonnage + $secondarySteelTonnage, 3);
        $totalPebSteelKg = round($primarySteelKg + $secondarySteelKg, 2);

        // 2. Galvalume Cladding (Roof Sheeting & Upper Wall Cladding)
        // Roof slope allowance ~1:10 adds ~5% to plan area
        $roofSheetingAreaM2 = round($floorAreaM2 * 1.06, 2);
        // Wall cladding covers height from top of brick dado up to eave height + gable ends
        $effectiveWallHeightM = max(0.5, $eaveHeightM - $dadoWallHeightM);
        $sidewallCladdingAreaM2 = $perimeterM * $effectiveWallHeightM;
        // Gable end triangle area (2 ends): 2 * (0.5 * width * gable_rise) where slope is 1:10 (rise = 0.05 * width)
        $gableRiseM = round($widthM * 0.05, 2);
        $gableCladdingAreaM2 = round($widthM * $gableRiseM, 2);
        $totalWallCladdingAreaM2 = round($sidewallCladdingAreaM2 + $gableCladdingAreaM2, 2);
        $totalGalvalumeAreaM2 = round($roofSheetingAreaM2 + $totalWallCladdingAreaM2, 2);

        // 3. Civil Substructure: RCC Isolated Footings & Stub Pedestals (M25)
        // 1 pedestal per column (1.8m depth, 1.8m x 1.8m footing, 450x450mm stub column with 4 nos Grade 8.8 anchor bolts)
        // Concrete volume: ~1.95 cu.m per column assembly
        $pedestalConcreteM3 = round($numColumns * 1.95, 2);
        // Tie beam linking all perimeter pedestals: 300x450mm
        $plinthTieBeamConcreteM3 = round($perimeterM * 0.30 * 0.45, 2);
        $totalSubstructureConcreteM3 = round($pedestalConcreteM3 + $plinthTieBeamConcreteM3, 2);

        // Fe500D rebar for pedestals, tie beams, and column footings: ~90 kg/m3 of concrete
        $substructureSteelKg = round($totalSubstructureConcreteM3 * 90.0, 2);
        $substructureSteelTonnage = round($substructureSteelKg / 1000.0, 3);

        // 4. Perimeter Brick Dado Wall (2.1m Height)
        $dadoWallAreaM2 = round($perimeterM * $dadoWallHeightM, 2);
        $dadoBrickVolumeM3 = round($dadoWallAreaM2 * 0.23, 2);
        $dadoBricksCount = (int) round($dadoBrickVolumeM3 * 500);

        // 5. Industrial Concrete Flooring (VDF / FM2 Slab)
        $slabThicknessM = match ($floorLoadGrade) {
            'heavy_duty' => 0.20,
            'light_duty' => 0.12,
            default => 0.15,
        };
        $flooringConcreteM3 = round($floorAreaM2 * $slabThicknessM, 2);

        // Total site materials (cement, aggregates, sand)
        $totalCivilConcreteM3 = round($totalSubstructureConcreteM3 + $flooringConcreteM3, 2);
        $cementBags = (int) ceil(($totalCivilConcreteM3 * 8.4) + ($dadoWallAreaM2 * 2 * 0.18) + ($dadoBricksCount * 0.024));
        $sandCft = round(($totalCivilConcreteM3 * 15.5) + ($dadoWallAreaM2 * 2 * 0.70) + ($dadoBricksCount * 0.08), 1);
        $sandBrass = round($sandCft / 100.0, 2);
        $aggregateCft = round($totalCivilConcreteM3 * 30.0, 1);
        $aggregateBrass = round($aggregateCft / 100.0, 2);

        // 6. Pricing & Financials from config
        $rates = config('construction.rates.warehouse_peb', []);
        $ratePrimarySteel = $rates['primary_structural_steel']['rate'] ?? 98000.0;
        $rateSecondarySteel = $rates['secondary_cold_formed_steel']['rate'] ?? 105000.0;
        $rateCladding = $rates['galvalume_roof_wall_cladding']['rate'] ?? 620.0;
        $ratePedestals = $rates['rcc_pedestals_m25']['rate'] ?? 7400.0;
        $rateVdfFloor = $rates['industrial_vdf_flooring']['rate'] ?? 850.0;
        $rateDadoWall = $rates['brick_dado_wall']['rate'] ?? 1350.0;
        $rateFe500D = config('construction.rates.residential.Item 102.rate', 82000.0);

        $costPrimarySteel = $primarySteelTonnage * $ratePrimarySteel;
        $costSecondarySteel = $secondarySteelTonnage * $rateSecondarySteel;
        $costCladding = $totalGalvalumeAreaM2 * $rateCladding;
        $costSubstructureConcrete = $totalSubstructureConcreteM3 * $ratePedestals;
        $costSubstructureSteel = $substructureSteelTonnage * $rateFe500D;
        $costFlooring = $floorAreaM2 * $rateVdfFloor;
        $costDadoWall = $dadoWallAreaM2 * $rateDadoWall;

        $totalEstimatedCost = $costPrimarySteel + $costSecondarySteel + $costCladding + $costSubstructureConcrete + $costSubstructureSteel + $costFlooring + $costDadoWall;

        // Commercial 12m TMT steel rods schedule for substructure
        $rebarDetails = $this->rebarService->calculateRebarBreakdown(
            $substructureSteelKg,
            [20 => 0.40, 16 => 0.35, 10 => 0.25],
            $rateFe500D
        );

        return [
            'project_type' => 'Industrial Pre-Engineered Steel Building (PEB Warehouse)',
            'scope' => "PEB Warehouse {$lengthM}m (L) x {$widthM}m (W) with {$eaveHeightM}m eave height across {$floorAreaM2} sq.m ({$floorAreaSqft} sq.ft) with {$floorLoadGrade} VDF floor",
            'inputs' => [
                'length_m' => $lengthM,
                'width_m' => $widthM,
                'eave_height_m' => $eaveHeightM,
                'ridge_height_m' => round($eaveHeightM + $gableRiseM, 2),
                'floor_area_sqm' => $floorAreaM2,
                'floor_area_sqft' => $floorAreaSqft,
                'bay_spacing_m' => $baySpacingM,
                'number_of_bays' => $numBays,
                'number_of_columns' => $numColumns,
                'floor_load_grade' => $floorLoadGrade,
                'dado_wall_height_m' => $dadoWallHeightM,
            ],
            'peb_structural_steel' => [
                'primary_rigid_frames_tonnes' => $primarySteelTonnage,
                'primary_steel_specification' => 'Built-up plate girders & rafters (IS 2062 Grade E250 / E350)',
                'secondary_purlins_girts_tonnes' => $secondarySteelTonnage,
                'secondary_steel_specification' => 'Cold-formed continuous Z & C sections (IS 801 / IS 811) with 275 gsm galvanized coating',
                'total_structural_steel_tonnes' => $totalPebSteelTonnage,
                'total_structural_steel_kg' => $totalPebSteelKg,
            ],
            'cladding_and_envelope' => [
                'roof_sheeting_area_m2' => $roofSheetingAreaM2,
                'wall_cladding_area_m2' => $totalWallCladdingAreaM2,
                'total_galvalume_cladding_m2' => $totalGalvalumeAreaM2,
                'specification' => '0.50mm TCT High Tensile AZ150 Galvalume pre-painted profiled sheet (IS 14246)',
            ],
            'civil_and_flooring' => [
                'substructure_concrete_m25_m3' => $totalSubstructureConcreteM3,
                'substructure_rebar_tonnes' => $substructureSteelTonnage,
                'substructure_12m_rods' => $rebarDetails['total_standard_12m_rods'],
                'dado_brickwork_area_m2' => $dadoWallAreaM2,
                'dado_bricks_count' => $dadoBricksCount,
                'vdf_floor_thickness_mm' => (int)($slabThicknessM * 1000),
                'vdf_floor_concrete_m3' => $flooringConcreteM3,
                'total_cement_bags_50kg' => $cementBags,
                'sand_brass' => $sandBrass,
                'aggregate_brass' => $aggregateBrass,
            ],
            'substructure_rebar_breakdown' => $rebarDetails,
            'cost_breakdown' => [
                'primary_structural_steel_inr' => round($costPrimarySteel, 2),
                'secondary_purlins_girts_inr' => round($costSecondarySteel, 2),
                'galvalume_roof_and_wall_cladding_inr' => round($costCladding, 2),
                'rcc_footings_pedestals_tie_beams_inr' => round($costSubstructureConcrete, 2),
                'substructure_steel_reinforcement_inr' => round($costSubstructureSteel, 2),
                'industrial_vdf_concrete_floor_inr' => round($costFlooring, 2),
                'brick_dado_wall_with_plaster_inr' => round($costDadoWall, 2),
                'total_estimated_cost_inr' => round($totalEstimatedCost, 2),
                'cost_per_sqft_inr' => round($totalEstimatedCost / max(1.0, $floorAreaSqft), 2),
                'cost_per_sqm_inr' => round($totalEstimatedCost / max(1.0, $floorAreaM2), 2),
            ],
            'engineering_guidance' => [
                'anchor_bolts' => 'High-strength Grade 8.8 anchor bolts cast with rigid template in RCC stub pedestals.',
                'joint_cutting' => 'VDF industrial floor slab requires saw-cut contraction joints at 4m x 4m grid within 24 hours of casting.',
            ]
        ];
    }
}

