<?php

namespace App\Services\Estimation;

class BridgePileService
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    public function estimate(
        float $peakDischargeQ,
        float $laceySiltFactorF = 1.0,
        float $pileDiameterMm = 1000.0,
        int $numPiles = 4,
        float $superstructureSpanM = 35.0,
        float $waterDepthAboveBedM = 4.0
    ): array {
        $f = max($laceySiltFactorF, 0.1);
        $meanScourR = 0.473 * pow($peakDischargeQ / $f, 1.0 / 3.0);
        $maxScourDPier = 2.0 * $meanScourR;
        $minEmbedmentDepth = $maxScourDPier / 3.0;

        $totalPileDepthBelowBedM = $maxScourDPier + $minEmbedmentDepth;
        $effectiveDrilledLengthPerPileM = $totalPileDepthBelowBedM + $waterDepthAboveBedM;

        $diaM = $pileDiameterMm / 1000.0;
        $pileAreaM2 = (M_PI / 4.0) * ($diaM ** 2);
        $concreteVolumePerPileM3 = $pileAreaM2 * $effectiveDrilledLengthPerPileM;
        $totalConcreteM3 = $concreteVolumePerPileM3 * $numPiles;

        $steelPercentage = 0.6;
        $steelAreaM2 = ($steelPercentage / 100.0) * $pileAreaM2;
        $steelKgPerMLongitudinal = $steelAreaM2 * 7850.0;
        $totalSteelKgPerPile = ($steelKgPerMLongitudinal * 1.25) * $effectiveDrilledLengthPerPileM;
        $totalSteelKg = $totalSteelKgPerPile * $numPiles;
        $totalSteelTonnes = $totalSteelKg / 1000.0;

        $bridgeRates = config('construction.rates.bridge', []);

        if ($superstructureSpanM > 60) {
            $concreteDesc = 'High-Performance Concrete M45 for superstructure & M40 for piers (>60m span)';
            $concreteRate = $bridgeRates['Item 1.02']['rate'] ?? 9450.0;
        } elseif ($superstructureSpanM > 30) {
            $concreteDesc = 'High-Performance Concrete M40/M45 (>30m span river bridges)';
            $concreteRate = $bridgeRates['Item 1.02']['rate'] ?? 9450.0;
        } else {
            $concreteDesc = 'Structural Concrete M35 Grade';
            $concreteRate = $bridgeRates['Item 1.01']['rate'] ?? 8650.0;
        }

        $rateBoringLinearM = $bridgeRates['Item 3.01']['rate'] ?? 4550.0;
        $rateSteelTonne = $bridgeRates['Item 2.01']['rate'] ?? 86500.0;

        $totalLinearMetresBoring = $effectiveDrilledLengthPerPileM * $numPiles;
        $costBoring = $totalLinearMetresBoring * $rateBoringLinearM;
        $costConcrete = $totalConcreteM3 * $concreteRate;
        $costSteel = $totalSteelTonnes * $rateSteelTonne;
        $totalCost = $costBoring + $costConcrete + $costSteel;

        $pileSteelDistribution = [25 => 0.60, 20 => 0.20, 10 => 0.20];
        $rebarBreakdown = $this->rebarService->calculateRebarBreakdown(
            $totalSteelKg,
            $pileSteelDistribution,
            $rateSteelTonne
        );

        return [
            'project_type' => 'Deep Bridge Foundation - Bored Cast-in-Situ RCC Piles',
            'governing_specifications' => [
                'IRC:78-2014 & MoRTH 5th Revision Section 2200',
                'Assam PWD Bridge Works SOR 2025-2026 (Circular CE/PWD(R)/SOR/2025/114)',
                'IRC:112 & IRC:83',
            ],
            'scour_hydraulics' => [
                'peak_discharge_Q_cumecs' => $peakDischargeQ,
                'lacey_silt_factor_f' => $laceySiltFactorF,
                'mean_scour_depth_R_metres' => round($meanScourR, 3),
                'max_scour_depth_under_pier_Dpier_metres' => round($maxScourDPier, 3),
                'min_embedment_depth_metres' => round($minEmbedmentDepth, 3),
                'embedment_criterion' => 'Embedment >= 1/3 D_pier into firm bearing strata with SPT N-value > 35',
            ],
            'pile_geometry' => [
                'pile_diameter_mm' => $pileDiameterMm,
                'number_of_piles' => $numPiles,
                'drilled_depth_per_pile_m' => round($effectiveDrilledLengthPerPileM, 2),
                'total_drilled_linear_metres' => round($totalLinearMetresBoring, 2),
                'total_concrete_m3' => round($totalConcreteM3, 2),
                'clear_cover_mm' => 75,
                'clear_cover_note' => '75mm min for deep piles cast in underwater saline/muddy conditions',
            ],
            'reinforcement_details' => [
                'longitudinal_ratio_pct' => $steelPercentage,
                'total_steel_kg' => round($totalSteelKg, 2),
                'total_steel_tonnes' => round($totalSteelTonnes, 3),
                'grade' => 'Fe 500D TMT Bars (IS 1786)',
                'rebar_breakdown' => $rebarBreakdown,
            ],
            'cost_breakdown' => [
                'boring_piling_inr' => round($costBoring, 2),
                'concrete_m3_inr' => round($costConcrete, 2),
                'steel_rebar_inr' => round($costSteel, 2),
                'total_foundation_cost_inr' => round($totalCost, 2),
            ],
            'seismic_and_bearing_provisions' => [
                'seismic_zone' => 'Assam Seismic Zone V',
                'stirrups' => 'Ductile confining stirrups per IRC:112 with 135-degree seismic hooks and 10d extension',
                'bearings' => 'Pot-PTFE or Elastomeric bearings conforming to IRC:83',
                'transverse_arrestors' => 'Transverse seismic arrestor blocks of minimum 400mm thickness on pier caps (Richter 7.0+ protection)',
            ],
        ];
    }
}

