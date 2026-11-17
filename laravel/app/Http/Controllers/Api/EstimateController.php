<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Estimation\BoundaryWallEstimator;
use App\Services\Estimation\BridgePileService;
use App\Services\Estimation\CommercialBuildingEstimator;
use App\Services\Estimation\RebarScheduleService;
use App\Services\Estimation\ResidentialEstimator;
use App\Services\Estimation\RoadAndDrainageEstimator;
use App\Services\Estimation\RoomBasedEstimator;
use App\Services\Estimation\WarehousePebEstimator;
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
        protected WaterRetainingService $waterRetainingService,
        protected BoundaryWallEstimator $boundaryWallEstimator,
        protected CommercialBuildingEstimator $commercialEstimator,
        protected WarehousePebEstimator $warehousePebEstimator,
        protected RoadAndDrainageEstimator $roadAndDrainageEstimator
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

    public function boundaryWall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length_m' => 'required|numeric|min:1',
            'height_m' => 'sometimes|numeric|min:0.5|max:6.0',
            'wall_thickness_mm' => 'sometimes|numeric|in:115,230',
            'pillar_spacing_m' => 'sometimes|numeric|min:1.5|max:6.0',
            'foundation_depth_m' => 'sometimes|numeric|min:0.4|max:3.0',
            'fencing_type' => 'sometimes|string|in:concertina_razor_coil,barbed_wire_3strand,none',
        ]);

        $res = $this->boundaryWallEstimator->estimate(
            lengthM: (float)$validated['length_m'],
            heightM: (float)($validated['height_m'] ?? 2.0),
            wallThicknessMm: (float)($validated['wall_thickness_mm'] ?? 230.0),
            pillarSpacingM: (float)($validated['pillar_spacing_m'] ?? 3.0),
            foundationDepthM: (float)($validated['foundation_depth_m'] ?? 0.9),
            fencingType: (string)($validated['fencing_type'] ?? 'concertina_razor_coil')
        );

        return response()->json($res);
    }

    public function commercial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'built_up_area_sqft' => 'required|numeric|min:100',
            'floors' => 'sometimes|integer|min:1|max:50',
            'has_basement' => 'sometimes|boolean',
            'occupancy_type' => 'sometimes|string|in:office,retail_mall,hotel,hospital,mixed_use',
        ]);

        $res = $this->commercialEstimator->estimate(
            builtUpAreaSqft: (float)$validated['built_up_area_sqft'],
            floors: (int)($validated['floors'] ?? 3),
            hasBasement: (bool)($validated['has_basement'] ?? false),
            occupancyType: (string)($validated['occupancy_type'] ?? 'office')
        );

        return response()->json($res);
    }

    public function warehousePeb(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length_m' => 'required|numeric|min:5',
            'width_m' => 'required|numeric|min:5',
            'eave_height_m' => 'sometimes|numeric|min:3|max:20',
            'floor_load_grade' => 'sometimes|string|in:medium_duty,heavy_duty,light_duty',
            'bay_spacing_m' => 'sometimes|numeric|min:3|max:12',
            'dado_wall_height_m' => 'sometimes|numeric|min:0|max:5',
        ]);

        $res = $this->warehousePebEstimator->estimate(
            lengthM: (float)$validated['length_m'],
            widthM: (float)$validated['width_m'],
            eaveHeightM: (float)($validated['eave_height_m'] ?? 7.0),
            floorLoadGrade: (string)($validated['floor_load_grade'] ?? 'medium_duty'),
            baySpacingM: (float)($validated['bay_spacing_m'] ?? 6.0),
            dadoWallHeightM: (float)($validated['dado_wall_height_m'] ?? 2.1)
        );

        return response()->json($res);
    }

    public function roadAndDrainage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length_m' => 'required|numeric|min:1',
            'carriageway_width_m' => 'sometimes|numeric|min:2.5|max:30',
            'pavement_type' => 'sometimes|string|in:flexible,rigid',
            'drain_type' => 'sometimes|string|in:rcc_covered_both_sides,rcc_covered_single_side,none',
            'shoulder_width_m' => 'sometimes|numeric|min:0|max:5',
        ]);

        $res = $this->roadAndDrainageEstimator->estimate(
            lengthM: (float)$validated['length_m'],
            carriagewayWidthM: (float)($validated['carriageway_width_m'] ?? 7.0),
            pavementType: (string)($validated['pavement_type'] ?? 'flexible'),
            drainType: (string)($validated['drain_type'] ?? 'rcc_covered_both_sides'),
            shoulderWidthM: (float)($validated['shoulder_width_m'] ?? 1.5)
        );

        return response()->json($res);
    }
}

