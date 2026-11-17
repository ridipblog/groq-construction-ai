<?php

namespace App\Services\Estimation;

class RoadAndDrainageEstimator
{
    public function __construct(
        protected RebarScheduleService $rebarService
    ) {}

    /**
     * Estimate complete quantities and costs for Highway / Road pavements and stormwater drainage
     *
     * @param float $lengthM Length of road in linear metres
     * @param float $carriagewayWidthM Width of pavement carriageway in metres (e.g., 3.75m single lane, 7.0m 2-lane, 14m 4-lane)
     * @param string $pavementType 'flexible' (GSB + WMM + DBM + BC per IRC:37) or 'rigid' (DLC + PQC M40 per IRC:58)
     * @param string $drainType 'rcc_covered_both_sides', 'rcc_covered_single_side', or 'none'
     * @param float $shoulderWidthM Width of earthen/paved shoulders on each side in metres (default 1.5m)
     * @return array
     */
    public function estimate(
        float $lengthM,
        float $carriagewayWidthM = 7.0,
        string $pavementType = 'flexible',
        string $drainType = 'rcc_covered_both_sides',
        float $shoulderWidthM = 1.5
    ): array {
        $carriagewayAreaM2 = round($lengthM * $carriagewayWidthM, 2);
        $totalFormationWidthM = $carriagewayWidthM + (2 * $shoulderWidthM);
        $totalFormationAreaM2 = round($lengthM * $totalFormationWidthM, 2);

        // 1. Earthwork Subgrade Preparation (MoRTH Section 300)
        // 500mm compacted subgrade layer across entire formation width
        $subgradeDepthM = 0.50;
        $subgradeVolumeM3 = round($totalFormationAreaM2 * $subgradeDepthM, 2);

        // 2. Pavement Layers based on Type
        $pavementQuantities = [];
        $costPavement = 0.0;

        $rates = config('construction.rates.road_and_drainage', []);
        $rateSubgrade = $rates['subgrade_earthwork']['rate'] ?? 240.0;
        $rateGsb = $rates['gsb_crushed_stone']['rate'] ?? 1850.0;
        $rateWmm = $rates['wmm_base_course']['rate'] ?? 2450.0;
        $rateDbm = $rates['dbm_dense_bituminous']['rate'] ?? 8800.0;
        $rateBc = $rates['bc_bituminous_concrete']['rate'] ?? 9600.0;
        $ratePqc = $rates['pqc_rigid_pavement']['rate'] ?? 8400.0;
        $rateDlc = $rates['dlc_dry_lean_concrete']['rate'] ?? 4800.0;
        $rateDrain = $rates['rcc_storm_drain']['rate'] ?? 3850.0;

        $costSubgrade = $subgradeVolumeM3 * $rateSubgrade;

        if ($pavementType === 'rigid') {
            // Rigid Pavement (IRC:58 / MoRTH Section 600)
            // Dry Lean Concrete (DLC M10): 150mm thick, extends 0.5m beyond carriageway on each side
            $dlcWidthM = $carriagewayWidthM + 1.0;
            $dlcThicknessM = 0.15;
            $dlcVolumeM3 = round($lengthM * $dlcWidthM * $dlcThicknessM, 2);
            $costDlc = $dlcVolumeM3 * $rateDlc;

            // Pavement Quality Concrete (PQC M40): 250mm thick on carriageway
            $pqcThicknessM = 0.25;
            $pqcVolumeM3 = round($carriagewayAreaM2 * $pqcThicknessM, 2);
            $costPqc = $pqcVolumeM3 * $ratePqc;

            // Dowel & Tie bars (IS 1786 Fe500D) across contraction/longitudinal joints: ~2.8 kg/m2 of slab
            $jointSteelKg = round($carriagewayAreaM2 * 2.8, 2);
            $jointSteelTonnes = round($jointSteelKg / 1000.0, 3);
            $rateSteelTonne = config('construction.rates.bridge.Item 2.01.rate', 86500.0);
            $costJointSteel = $jointSteelTonnes * $rateSteelTonne;

            $costPavement = $costDlc + $costPqc + $costJointSteel;

            $pavementQuantities = [
                'dlc_m10_subbase_m3' => $dlcVolumeM3,
                'pqc_m40_slab_m3' => $pqcVolumeM3,
                'dowel_and_tie_steel_tonnes' => $jointSteelTonnes,
                'pavement_specification' => '250mm PQC M40 over 150mm Dry Lean Concrete (DLC) sub-base per IRC:58-2015',
            ];
        } else {
            // Flexible Bituminous Pavement (IRC:37 / MoRTH Section 400 & 500)
            // Granular Sub-Base (GSB Grade-I): 200mm across formation width
            $gsbThicknessM = 0.20;
            $gsbVolumeM3 = round($totalFormationAreaM2 * $gsbThicknessM, 2);
            $costGsb = $gsbVolumeM3 * $rateGsb;

            // Wet Mix Macadam (WMM): 250mm across carriageway + 0.5m paved shoulder
            $wmmWidthM = $carriagewayWidthM + 0.5;
            $wmmThicknessM = 0.25;
            $wmmVolumeM3 = round($lengthM * $wmmWidthM * $wmmThicknessM, 2);
            $costWmm = $wmmVolumeM3 * $rateWmm;

            // Dense Bituminous Macadam (DBM): 60mm across carriageway
            $dbmThicknessM = 0.06;
            $dbmVolumeM3 = round($carriagewayAreaM2 * $dbmThicknessM, 2);
            $costDbm = $dbmVolumeM3 * $rateDbm;

            // Bituminous Concrete (BC): 40mm wearing course across carriageway
            $bcThicknessM = 0.04;
            $bcVolumeM3 = round($carriagewayAreaM2 * $bcThicknessM, 2);
            $costBc = $bcVolumeM3 * $rateBc;

            $costPavement = $costGsb + $costWmm + $costDbm + $costBc;

            $pavementQuantities = [
                'gsb_grade1_volume_m3' => $gsbVolumeM3,
                'wmm_base_volume_m3' => $wmmVolumeM3,
                'dbm_bituminous_volume_m3' => $dbmVolumeM3,
                'bc_wearing_course_volume_m3' => $bcVolumeM3,
                'pavement_specification' => '40mm BC + 60mm DBM + 250mm WMM + 200mm GSB per IRC:37-2018',
            ];
        }

        // 3. Stormwater Drainage (CPHEEO Drainage Manual)
        $drainLinearMetres = 0.0;
        if ($drainType === 'rcc_covered_both_sides') {
            $drainLinearMetres = 2.0 * $lengthM;
        } elseif ($drainType === 'rcc_covered_single_side') {
            $drainLinearMetres = $lengthM;
        }

        $costDrain = $drainLinearMetres * $rateDrain;

        // Drain structural concrete & rebar estimates:
        // 600x750mm internal U-trough (125mm walls & bed + 100mm cover): ~0.38 cu.m concrete per linear metre
        $drainConcreteM3 = round($drainLinearMetres * 0.38, 2);
        // Steel in drain ~75 kg/m3
        $drainSteelKg = round($drainConcreteM3 * 75.0, 2);
        $drainSteelTonnes = round($drainSteelKg / 1000.0, 3);

        $totalEstimatedCost = $costSubgrade + $costPavement + $costDrain;

        return [
            'project_type' => 'Highway Roadway & Stormwater Drainage (Assam PWRD & IRC/MoRTH)',
            'scope' => ucfirst($pavementType) . " pavement of length {$lengthM}m, carriageway {$carriagewayWidthM}m (" . ($carriagewayWidthM >= 7.0 ? 'Two Lane' : 'Single Lane') . ") with {$drainType} drainage",
            'inputs' => [
                'length_m' => $lengthM,
                'carriageway_width_m' => $carriagewayWidthM,
                'shoulder_width_m' => $shoulderWidthM,
                'total_formation_width_m' => $totalFormationWidthM,
                'carriageway_area_sqm' => $carriagewayAreaM2,
                'pavement_type' => $pavementType,
                'drain_type' => $drainType,
                'drain_length_m' => $drainLinearMetres,
            ],
            'subgrade_earthwork' => [
                'subgrade_compaction_m3' => $subgradeVolumeM3,
                'specification' => 'MoRTH Section 300 500mm subgrade compacted to 97% MDD (CBR >= 8%)',
            ],
            'pavement_layers' => $pavementQuantities,
            'stormwater_drainage' => [
                'drain_type' => $drainType,
                'total_drain_length_m' => $drainLinearMetres,
                'drain_concrete_m25_m3' => $drainConcreteM3,
                'drain_reinforcement_tonnes' => $drainSteelTonnes,
                'specification' => 'IS 456 M25 RCC U-channel (600x750mm) with 100mm heavy precast slotted cover slabs per CPHEEO Manual',
            ],
            'cost_breakdown' => [
                'subgrade_earthwork_inr' => round($costSubgrade, 2),
                'pavement_layers_total_inr' => round($costPavement, 2),
                'rcc_stormwater_drainage_inr' => round($costDrain, 2),
                'total_estimated_cost_inr' => round($totalEstimatedCost, 2),
                'cost_per_linear_metre_inr' => round($totalEstimatedCost / max(1.0, $lengthM), 2),
                'cost_per_sqm_carriageway_inr' => round($totalEstimatedCost / max(1.0, $carriagewayAreaM2), 2),
            ],
            'highway_standards' => [
                'governing_codes' => $pavementType === 'rigid' ? 'IRC:58-2015, MoRTH Section 600, IS 456' : 'IRC:37-2018, MoRTH Section 400 & 500',
                'drainage_code' => 'CPHEEO Manual on Stormwater Drainage & IRC:SP:42',
            ]
        ];
    }
}

