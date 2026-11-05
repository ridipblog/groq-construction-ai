<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Estimation\BridgePileService;
use App\Services\Estimation\RebarScheduleService;
use App\Services\Estimation\ResidentialEstimator;
use App\Services\Estimation\RoomBasedEstimator;
use App\Services\Estimation\WaterRetainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateController extends Controller
{
    public function __construct(
        protected RoomBasedEstimator $roomEstimator,
        protected ResidentialEstimator $residentialEstimator,
        protected RebarScheduleService $rebarService,
        protected BridgePileService $bridgePileService,
        protected WaterRetainingService $waterRetainingService
    ) {}

    public function byRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rooms' => 'sometimes|integer|min:1',
            'toilets' => 'sometimes|integer|min:0',
            'balconies' => 'sometimes|integer|min:0',
            'floors' => 'sometimes|integer|min:1',
            'include_living_dining' => 'sometimes|boolean',
            'include_kitchen' => 'sometimes|boolean',
            'seismic_zone_v' => 'sometimes|boolean',
        ]);

        $res = $this->roomEstimator->estimate(
            rooms: (int)($validated['rooms'] ?? 3),
            toilets: (int)($validated['toilets'] ?? 3),
            balconies: (int)($validated['balconies'] ?? 1),
            floors: (int)($validated['floors'] ?? 1),
            includeLivingDining: (bool)($validated['include_living_dining'] ?? true),
            includeKitchen: (bool)($validated['include_kitchen'] ?? true),
            seismicZoneV: (bool)($validated['seismic_zone_v'] ?? true)
        );

        return response()->json($res);
    }

    public function residential(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'built_up_area_sqft' => 'required|numeric|min:50',
            'floors' => 'sometimes|integer|min:1',
            'seismic_zone_v' => 'sometimes|boolean',
        ]);

        $res = $this->residentialEstimator->estimate(
            builtUpAreaSqft: (float)$validated['built_up_area_sqft'],
            floors: (int)($validated['floors'] ?? 1),
            seismicZoneV: (bool)($validated['seismic_zone_v'] ?? true)
        );

        return response()->json($res);
    }

    public function rebarSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_steel_kg' => 'required|numeric|min:1',
            'application' => 'sometimes|string',
        ]);

        $rate = strtolower((string)($validated['application'] ?? 'residential')) === 'bridge' ? 86500.0 : 82000.0;
        $res = $this->rebarService->calculateRebarBreakdown(
            totalSteelKg: (float)$validated['total_steel_kg'],
            distribution: null,
            ratePerTonne: $rate
        );

        return response()->json($res);
    }

    public function bridgePile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'peak_discharge_Q' => 'required|numeric|min:1',
            'lacey_silt_factor_f' => 'sometimes|numeric|min:0.1',
            'pile_diameter_mm' => 'sometimes|numeric|min:300',
            'num_piles' => 'sometimes|integer|min:1',
            'superstructure_span_m' => 'sometimes|numeric|min:5',
            'water_depth_above_bed_m' => 'sometimes|numeric|min:0',
        ]);

        $res = $this->bridgePileService->estimate(
            peakDischargeQ: (float)$validated['peak_discharge_Q'],
            laceySiltFactorF: (float)($validated['lacey_silt_factor_f'] ?? 1.0),
            pileDiameterMm: (float)($validated['pile_diameter_mm'] ?? 1000.0),
            numPiles: (int)($validated['num_piles'] ?? 4),
            superstructureSpanM: (float)($validated['superstructure_span_m'] ?? 35.0),
            waterDepthAboveBedM: (float)($validated['water_depth_above_bed_m'] ?? 4.0)
        );

        return response()->json($res);
    }

    public function swimmingPool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length_m' => 'required|numeric|min:1',
            'width_m' => 'required|numeric|min:1',
            'depth_m' => 'required|numeric|min:0.5',
            'wall_thickness_m' => 'sometimes|numeric|min:0.15',
            'raft_thickness_m' => 'sometimes|numeric|min:0.15',
        ]);

        $res = $this->waterRetainingService->estimate(
            lengthM: (float)$validated['length_m'],
            widthM: (float)$validated['width_m'],
            depthM: (float)$validated['depth_m'],
            wallThicknessM: (float)($validated['wall_thickness_m'] ?? 0.25),
            raftThicknessM: (float)($validated['raft_thickness_m'] ?? 0.30)
        );

        return response()->json($res);
    }
}

