<?php

namespace App\Services\Estimation;

class WaterRetainingService
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    public function estimate(
        float $lengthM,
        float $widthM,
        float $depthM,
        float $wallThicknessM = 0.25,
        float $raftThicknessM = 0.30
    ): array {
        $floorArea = $lengthM * $widthM;
        $perimeter = 2.0 * ($lengthM + $widthM);
        $wallWettedArea = $perimeter * $depthM;
        $totalWettedAreaM2 = round($floorArea + $wallWettedArea, 2);

        $waterstopLinearM = round($perimeter, 2);

        $raftL = $lengthM + (2 * $wallThicknessM) + 0.4;
        $raftW = $widthM + (2 * $wallThicknessM) + 0.4;
        $raftVolumeM3 = $raftL * $raftW * $raftThicknessM;
        $wallVolumeM3 = $perimeter * $wallThicknessM * $depthM;
        $totalConcreteM3 = round($raftVolumeM3 + $wallVolumeM3, 2);

        $cementKg = $totalConcreteM3 * 380.0;
        $crystallineAdmixtureKg = round($cementKg * 0.02, 2);

        $totalSteelKg = round($totalConcreteM3 * 115.0, 2);
        $totalSteelTonnes = round($totalSteelKg / 1000.0, 3);

        $liquidRates = config('construction.rates.liquid_retaining', []);
        $rateWaterstop = $liquidRates['waterstop']['rate'] ?? 450.0;
        $rateDualWaterproof = $liquidRates['dual_waterproofing']['rate'] ?? 650.0;
        $rateTiles = $liquidRates['pool_tiles']['rate'] ?? 1450.0;
        $rateSteelTonne = 82000.0;
        $rateM30Concrete = 8000.0;

        $costWaterstop = $waterstopLinearM * $rateWaterstop;
        $costWaterproofing = $totalWettedAreaM2 * $rateDualWaterproof;
        $costTiles = $totalWettedAreaM2 * $rateTiles;
        $costConcrete = $totalConcreteM3 * $rateM30Concrete;
        $costSteel = $totalSteelTonnes * $rateSteelTonne;

        $totalCost = $costWaterstop + $costWaterproofing + $costTiles + $costConcrete + $costSteel;

        $rebarDistribution = [12 => 0.50, 10 => 0.35, 8 => 0.15];
        $rebarBreakdown = $this->rebarService->calculateRebarBreakdown(
            $totalSteelKg,
            $rebarDistribution,
            $rateSteelTonne
        );

        return [
            'project_type' => 'Swimming Pool / Water Retaining Structure',
            'governing_standard' => 'IS 3370 (Part 1-4) & SP 34 Concrete Storage Structures',
            'dimensions' => [
                'length_m' => $lengthM,
                'width_m' => $widthM,
                'depth_m' => $depthM,
                'capacity_litres' => round($lengthM * $widthM * $depthM * 1000.0, 1),
                'wetted_surface_area_m2' => $totalWettedAreaM2,
                'construction_joint_length_m' => $waterstopLinearM,
            ],
            'concrete_and_waterproofing' => [
                'concrete_volume_m3' => $totalConcreteM3,
                'concrete_grade' => 'M30 Design Mix (IS 3370)',
                'water_cement_ratio' => '<= 0.45',
                'crystalline_admixture_kg' => $crystallineAdmixtureKg,
                'crack_width_limit' => '<= 0.1mm under design hydrostatic head',
                'waterstop_spec' => '20mm x 10mm bentonite-rubber swellable waterstop bar',
                'dual_layer_waterproofing_spec' => '2-coat polymer-modified cementitious slurry + 145 gsm mesh (1.5-2.0mm DFT)',
            ],
            'reinforcement' => [
                'total_steel_kg' => $totalSteelKg,
                'total_steel_tonnes' => $totalSteelTonnes,
                'grade' => 'Fe500D (IS 1786)',
                'rebar_breakdown' => $rebarBreakdown,
            ],
            'cost_breakdown' => [
                'waterstop_joint_sealing_inr' => round($costWaterstop, 2),
                'dual_layer_waterproofing_inr' => round($costWaterproofing, 2),
                'pool_finishes_and_tiles_inr' => round($costTiles, 2),
                'concrete_inr' => round($costConcrete, 2),
                'steel_inr' => round($costSteel, 2),
                'total_cost_inr' => round($totalCost, 2),
            ],
            'groundwater_precaution' => 'In high groundwater regions, pressure relief valves (hydrostatic relief valves) must be installed in main drains to prevent pool floor flotation during emptying.',
        ];
    }
}

