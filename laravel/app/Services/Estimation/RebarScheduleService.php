<?php

namespace App\Services\Estimation;

class RebarScheduleService
{
    public const STANDARD_ROD_LENGTH_M = 12.0;

    public const REBAR_UNIT_WEIGHTS = [
        8 => 0.395,
        10 => 0.617,
        12 => 0.888,
        16 => 1.578,
        20 => 2.466,
        25 => 3.853,
        32 => 6.313,
    ];

    public function calculateRebarWeight(int $diameterMm, float $totalLengthM): float
    {
        $unitWt = self::REBAR_UNIT_WEIGHTS[$diameterMm] ?? (($diameterMm ** 2) / 162.28);
        return round($unitWt * $totalLengthM, 2);
    }

    public function calculateNumberOfRods(int $diameterMm, float $totalWeightKg): array
    {
        $unitWt = self::REBAR_UNIT_WEIGHTS[$diameterMm] ?? (($diameterMm ** 2) / 162.28);
        $weightPerRod = $unitWt * self::STANDARD_ROD_LENGTH_M;
        $rodsCount = $weightPerRod > 0 ? (int) ceil($totalWeightKg / $weightPerRod) : 0;

        return [
            'diameter_mm' => $diameterMm,
            'weight_per_rod_kg' => round($weightPerRod, 2),
            'total_weight_kg' => round($totalWeightKg, 2),
            'total_weight_tonnes' => round($totalWeightKg / 1000.0, 3),
            'standard_12m_rods_count' => $rodsCount,
            'approx_bundles' => (int) ceil($rodsCount / $this->getTypicalBundleCapacity($diameterMm)),
        ];
    }

    public function getTypicalBundleCapacity(int $diameterMm): int
    {
        $bundleMap = [8 => 10, 10 => 7, 12 => 5, 16 => 3, 20 => 2, 25 => 1, 32 => 1];
        return $bundleMap[$diameterMm] ?? 1;
    }

    public function calculateRebarBreakdown(
        float $totalSteelKg,
        ?array $distribution = null,
        float $ratePerTonne = 82000.0
    ): array {
        if (empty($distribution)) {
            // Standard structural distribution for RCC frames in Seismic Zone V:
            $distribution = [
                8 => 0.22,
                10 => 0.18,
                12 => 0.30,
                16 => 0.25,
                20 => 0.05,
            ];
        }

        $items = [];
        $totalRods = 0;
        $totalCost = ($totalSteelKg / 1000.0) * $ratePerTonne;
        $bindingWireKg = round($totalSteelKg * 0.012, 2);

        $rebarStandards = config('construction.rebar_standards', []);

        foreach ($distribution as $dia => $pct) {
            $dia = (int) $dia;
            $diaWeight = $totalSteelKg * $pct;
            $res = $this->calculateNumberOfRods($dia, $diaWeight);
            $res['percentage'] = round($pct * 100, 1);
            $res['typical_use'] = $rebarStandards[$dia]['typical_use'] ?? 'Structural reinforcement';
            $totalRods += $res['standard_12m_rods_count'];
            $items[] = $res;
        }

        return [
            'total_steel_kg' => round($totalSteelKg, 2),
            'total_steel_tonnes' => round($totalSteelKg / 1000.0, 3),
            'total_standard_12m_rods' => $totalRods,
            'binding_wire_kg' => $bindingWireKg,
            'rate_per_tonne' => $ratePerTonne,
            'total_cost_inr' => round($totalCost, 2),
            'bar_schedule' => $items,
        ];
    }
}

