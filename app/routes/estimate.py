"""
Direct Estimation Endpoints (For UI Calculators, Dashboard Widgets, and Forms)
"""

from fastapi import APIRouter
from typing import Dict, Any
from app.models import (
    ResidentialEstimateRequest,
    RoomBasedHouseEstimateRequest,
    RebarScheduleRequest,
    BridgePileEstimateRequest,
    SwimmingPoolEstimateRequest
)
from app.calculators import (
    calculate_residential_estimation,
    calculate_room_based_house_estimation,
    calculate_rebar_breakdown,
    calculate_bridge_pile_estimation,
    calculate_water_retaining_structure
)

router = APIRouter(prefix="/estimate", tags=["Direct Estimations"])

@router.post("/by-rooms")
@router.post("/by-room")
async def estimate_house_by_rooms(req: RoomBasedHouseEstimateRequest) -> Dict[str, Any]:
    """
    Direct endpoint for room-based house estimation.
    Accepts: rooms (e.g. 3), toilets (e.g. 3), balconies (e.g. 1), floors (e.g. 1).
    Computes carpet area, wall & circulation allowance (+22%), total built-up area,
    and full steel rod counts (8mm-20mm) and materials per Assam PWD SOR 2025-2026.
    """
    return calculate_room_based_house_estimation(
        rooms=req.rooms,
        toilets=req.toilets,
        balconies=req.balconies,
        floors=req.floors,
        include_living_dining=req.include_living_dining,
        include_kitchen=req.include_kitchen,
        seismic_zone_v=req.seismic_zone_v
    )

@router.post("/residential")
async def estimate_residential(req: ResidentialEstimateRequest) -> Dict[str, Any]:
    """
    Direct endpoint for low-rise residential house estimation (IS 456, IS 13920, Assam PWD SOR 2025-2026).
    Returns complete steel rod counts, concrete volume, brickwork, plaster, tiles, and cost items.
    """
    return calculate_residential_estimation(
        built_up_area_sqft=req.built_up_area_sqft,
        floors=req.floors,
        seismic_zone_v=req.seismic_zone_v
    )

@router.post("/rebar-schedule")
async def estimate_rebar_schedule(req: RebarScheduleRequest) -> Dict[str, Any]:
    """
    Direct endpoint to calculate standard 12-meter commercial steel rod count per diameter
    (8mm, 10mm, 12mm, 16mm, 20mm), bundles, binding wire, and total cost.
    """
    rate = 86500.0 if req.application.lower() == "bridge" else 82000.0
    return calculate_rebar_breakdown(total_steel_kg=req.total_steel_kg, rate_per_tonne=rate)

@router.post("/bridge-pile")
async def estimate_bridge_pile(req: BridgePileEstimateRequest) -> Dict[str, Any]:
    """
    Direct endpoint for bridge bored cast-in-situ piles and scour depth (MoRTH & Assam PWD Bridge SOR 2025-2026).
    """
    return calculate_bridge_pile_estimation(
        peak_discharge_Q=req.peak_discharge_Q,
        lacey_silt_factor_f=req.lacey_silt_factor_f,
        pile_diameter_mm=req.pile_diameter_mm,
        num_piles=req.num_piles,
        superstructure_span_m=req.superstructure_span_m,
        water_depth_above_bed_m=req.water_depth_above_bed_m
    )

@router.post("/swimming-pool")
async def estimate_swimming_pool(req: SwimmingPoolEstimateRequest) -> Dict[str, Any]:
    """
    Direct endpoint for swimming pools & liquid retaining structures per IS 3370 & SP 34.
    """
    return calculate_water_retaining_structure(
        length_m=req.length_m,
        width_m=req.width_m,
        depth_m=req.depth_m,
        wall_thickness_m=req.wall_thickness_m,
        raft_thickness_m=req.raft_thickness_m
    )
