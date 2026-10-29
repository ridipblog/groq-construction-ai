"""
Civil Engineering Estimation Calculators
Calibrated with:
- Assam PWD Bridge SOR 2025-2026
- Assam PWD Residential SOR 2025-2026
- IS 456:2000, IS 1786, IS 13920 (Zone V Ductile Details)
- IS 3370 (Liquid Retaining Structures)
- MoRTH 5th Revision & IRC:78-2014 (Deep Foundations)
"""

import math
from typing import Dict, Any, List, Optional
from app.knowledge_base import KNOWLEDGE_BASE

# Standard Rebar Nominal Weights (kg per linear metre)
REBAR_UNIT_WEIGHTS = {
    8: 0.395,   # d^2 / 162
    10: 0.617,
    12: 0.888,
    16: 1.578,
    20: 2.466,
    25: 3.853,
    32: 6.313
}

STANDARD_ROD_LENGTH_M = 12.0  # Standard commercial steel rod length is 12 metres (40 feet)

def calculate_rebar_weight(diameter_mm: int, total_length_m: float) -> float:
    """Calculates weight in kg from diameter in mm and length in metres using d^2 / 162.28."""
    unit_wt = REBAR_UNIT_WEIGHTS.get(diameter_mm, (diameter_mm ** 2) / 162.28)
    return round(unit_wt * total_length_m, 2)

def calculate_number_of_rods(diameter_mm: int, total_weight_kg: float) -> Dict[str, Any]:
    """Calculates standard 12-meter rod counts from total weight."""
    unit_wt = REBAR_UNIT_WEIGHTS.get(diameter_mm, (diameter_mm ** 2) / 162.28)
    weight_per_rod = unit_wt * STANDARD_ROD_LENGTH_M
    rods_count = math.ceil(total_weight_kg / weight_per_rod) if weight_per_rod > 0 else 0
    return {
        "diameter_mm": diameter_mm,
        "weight_per_rod_kg": round(weight_per_rod, 2),
        "total_weight_kg": round(total_weight_kg, 2),
        "total_weight_tonnes": round(total_weight_kg / 1000.0, 3),
        "standard_12m_rods_count": rods_count,
        "approx_bundles": math.ceil(rods_count / get_typical_bundle_capacity(diameter_mm))
    }

def get_typical_bundle_capacity(diameter_mm: int) -> int:
    """Standard commercial manufacturer bundle rod counts in India."""
    bundle_map = {8: 10, 10: 7, 12: 5, 16: 3, 20: 2, 25: 1, 32: 1}
    return bundle_map.get(diameter_mm, 1)

def calculate_rebar_breakdown(
    total_steel_kg: float,
    distribution: Optional[Dict[int, float]] = None,
    rate_per_tonne: float = 82000.0
) -> Dict[str, Any]:
    """
    Distributes total steel requirement across standard bar diameters (8mm, 10mm, 12mm, 16mm, 20mm).
    Default distribution reflects typical RCC residential / building structural cage.
    """
    if not distribution:
        # Standard structural distribution for RCC frames in Seismic Zone V:
        # 8mm (confinement ties & stirrups): 22%
        # 10mm (slab secondary/distribution): 18%
        # 12mm (slab main & lintels): 30%
        # 16mm (column longitudinals & beam tensile/compression): 25%
        # 20mm (ground columns / heavy spans): 5%
        distribution = {8: 0.22, 10: 0.18, 12: 0.30, 16: 0.25, 20: 0.05}

    items = []
    total_rods = 0
    total_cost = (total_steel_kg / 1000.0) * rate_per_tonne
    binding_wire_kg = round(total_steel_kg * 0.012, 2)  # ~12 kg binding wire per tonne

    for dia, pct in distribution.items():
        dia_weight = total_steel_kg * pct
        res = calculate_number_of_rods(dia, dia_weight)
        res["percentage"] = round(pct * 100, 1)
        res["typical_use"] = KNOWLEDGE_BASE["rebar_standards"].get(dia, {}).get("typical_use", "Structural reinforcement")
        total_rods += res["standard_12m_rods_count"]
        items.append(res)

    return {
        "total_steel_kg": round(total_steel_kg, 2),
        "total_steel_tonnes": round(total_steel_kg / 1000.0, 3),
        "total_standard_12m_rods": total_rods,
        "binding_wire_kg": binding_wire_kg,
        "rate_per_tonne": rate_per_tonne,
        "total_cost_inr": round(total_cost, 2),
        "bar_schedule": items
    }

def calculate_residential_estimation(
    built_up_area_sqft: float,
    floors: int = 1,
    seismic_zone_v: bool = True
) -> Dict[str, Any]:
    """
    Comprehensive residential estimation per Assam PWD Building SOR 2025-2026 (PWRD/BLDG/SOR-2025/HSE-01).
    Accommodates low-rise (G, G+1, G+2) RCC framed residential houses.
    Includes IS 13920 ductile detailing for Seismic Zone V.
    """
    total_built_up_sqft = built_up_area_sqft * floors
    total_built_up_sqm = total_built_up_sqft * 0.092903

    # Steel consumption:
    # Standard residential: ~3.8 - 4.0 kg/sq.ft
    # Seismic Zone V with IS 13920 ductile hooks (135 deg with 10d extension) and confinement links: ~4.5 kg/sq.ft
    steel_per_sqft = 4.5 if seismic_zone_v else 3.8
    total_steel_kg = total_built_up_sqft * steel_per_sqft
    steel_tonne = total_steel_kg / 1000.0

    # Concrete volume (IS 456 M25): footings, plinth beam, columns, lintels, roof beams, 130mm slabs
    # Typical factor: ~0.04 m3 concrete per sq.ft of total built-up area
    concrete_m3 = round(total_built_up_sqft * 0.040, 2)

    # First Class Bricks (IS 2212): 9" exterior + 4.5" interior walls
    # ~8.5 bricks per sq.ft of built-up area
    total_bricks = int(round(total_built_up_sqft * 8.5))

    # Plastering (IS 1661): 12mm internal + 15mm external sand-faced waterproof sponge
    # Total wall & ceiling plastered surface is ~2.6 times floor area in m2
    plaster_m2 = round(total_built_up_sqm * 2.6, 2)

    # Vitrified tile flooring (IS 15622): ~75% usable floor area (net of walls)
    flooring_m2 = round(total_built_up_sqm * 0.75, 2)

    # Approved Rates from SOR 2025-2026:
    rate_concrete_m3 = KNOWLEDGE_BASE["rates"]["residential"]["Item 101"]["rate"]       # 7,200 / m3
    rate_steel_tonne = KNOWLEDGE_BASE["rates"]["residential"]["Item 102"]["rate"]       # 82,000 / tonne
    rate_bricks_1000 = KNOWLEDGE_BASE["rates"]["residential"]["Item 103"]["rate"]       # 12,500 / 1000 bricks
    rate_plaster_m2 = KNOWLEDGE_BASE["rates"]["residential"]["Item 104"]["rate"]        # 320 / m2
    rate_flooring_m2 = KNOWLEDGE_BASE["rates"]["residential"]["Item 105"]["rate"]       # 1,150 / m2

    cost_concrete = concrete_m3 * rate_concrete_m3
    cost_steel = steel_tonne * rate_steel_tonne
    cost_bricks = (total_bricks / 1000.0) * rate_bricks_1000
    cost_plaster = plaster_m2 * rate_plaster_m2
    cost_flooring = flooring_m2 * rate_flooring_m2

    total_base_cost = cost_concrete + cost_steel + cost_bricks + cost_plaster + cost_flooring

    rebar_details = calculate_rebar_breakdown(total_steel_kg, rate_per_tonne=rate_steel_tonne)

    # Indian Site Quantities (Bags, Brass, CFT, Quintals)
    cement_bags = math.ceil((concrete_m3 * 8.4) + (plaster_m2 * 0.18) + (total_bricks * 0.024))
    sand_cft = round((concrete_m3 * 15.9) + (plaster_m2 * 0.70) + (total_bricks * 0.08), 1)
    aggregate_cft = round(concrete_m3 * 30.0, 1)

    return {
        "project_type": "RCC Framed Residential Building (Assam PWD Building SOR 2025-2026)",
        "scope": f"Low-rise G+{floors-1 if floors > 1 else 0} ({floors} level(s)) across Kamrup Metro/Dibrugarh/Silchar/Jorhat",
        "inputs": {
            "floor_plate_sqft": built_up_area_sqft,
            "number_of_floors": floors,
            "total_built_up_area_sqft": round(total_built_up_sqft, 2),
            "total_built_up_area_sqm": round(total_built_up_sqm, 2),
            "seismic_zone_v": seismic_zone_v
        },
        "indian_market_materials": {
            "sariya_tmt_steel": {
                "total_tonnes": round(steel_tonne, 3),
                "total_quintals": round(total_steel_kg / 100.0, 2),
                "total_kg": round(total_steel_kg, 2),
                "total_12m_commercial_rods": rebar_details["total_standard_12m_rods"],
                "binding_wire_kg": rebar_details["binding_wire_kg"],
                "grade": "Fe 500D (IS 1786 High Ductility)",
                "approx_cost_inr": round(cost_steel, 2)
            },
            "cement_bags_50kg": {
                "total_bags": cement_bags,
                "description": "Standard 50kg bags (UltraTech, ACC, Ambuja, Dalmia or equivalent OPC 43/53 or PPC)",
                "application": "Concrete, brick masonry mortar & plastering"
            },
            "sand_reti_msand": {
                "volume_cft": sand_cft,
                "volume_brass": round(sand_cft / 100.0, 2),
                "note": "1 Brass = 100 cu.ft (Zone II river sand or washed M-Sand)"
            },
            "coarse_aggregate_rori_gitti": {
                "volume_cft": aggregate_cft,
                "volume_brass": round(aggregate_cft / 100.0, 2),
                "note": "20mm down (60%) & 10mm down (40%) graded blue granite / basalt"
            },
            "first_class_bricks": {
                "total_bricks": total_bricks,
                "description": "Red clay bricks / fly ash bricks (IS 2212)"
            }
        },
        "material_quantities": {
            "steel_fe500d": {
                "total_kg": round(total_steel_kg, 2),
                "total_tonnes": round(steel_tonne, 3),
                "total_12m_rods": rebar_details["total_standard_12m_rods"],
                "binding_wire_kg": rebar_details["binding_wire_kg"],
                "standard": "IS 1786 Fe500D (high ductility)",
                "unit_rate_inr": rate_steel_tonne,
                "subtotal_cost_inr": round(cost_steel, 2)
            },
            "concrete_m25": {
                "volume_m3": concrete_m3,
                "description": "Footings, plinth beams, columns, lintels, 130mm slabs",
                "standard": "IS 456:2000 (1:1.5:3 or design mix)",
                "unit_rate_inr": rate_concrete_m3,
                "subtotal_cost_inr": round(cost_concrete, 2)
            },
            "brickwork_masonry": {
                "total_bricks_count": total_bricks,
                "description": "9\" exterior and 4.5\" interior in CM 1:6",
                "standard": "IS 2212 1st Class Bricks",
                "unit_rate_per_1000_inr": rate_bricks_1000,
                "subtotal_cost_inr": round(cost_bricks, 2)
            },
            "plastering": {
                "area_m2": plaster_m2,
                "description": "12mm internal (1:6) & 15mm external waterproof (1:4)",
                "standard": "IS 1661",
                "unit_rate_inr": rate_plaster_m2,
                "subtotal_cost_inr": round(cost_plaster, 2)
            },
            "vitrified_tiles": {
                "area_m2": flooring_m2,
                "description": "600x600mm double-charged tiles over 20mm mortar",
                "standard": "IS 15622",
                "unit_rate_inr": rate_flooring_m2,
                "subtotal_cost_inr": round(cost_flooring, 2)
            }
        },
        "rebar_breakdown": rebar_details,
        "cost_summary": {
            "steel_cost_inr": round(cost_steel, 2),
            "concrete_cost_inr": round(cost_concrete, 2),
            "brickwork_cost_inr": round(cost_bricks, 2),
            "plastering_cost_inr": round(cost_plaster, 2),
            "flooring_cost_inr": round(cost_flooring, 2),
            "total_estimated_materials_cost_inr": round(total_base_cost, 2),
            "cost_per_sqft_inr": round(total_base_cost / total_built_up_sqft, 2)
        },
        "seismic_safety_provisions": {
            "zone": "Seismic Zone V",
            "code": "IS 13920:2016",
            "requirement": "Ductile confinement links with 135-degree hooks and 10d extension at all beam-column junctions."
        },
        "architectural_configuration": {
            "rooms_count": max(1, int(round(total_built_up_sqft / 400.0))),
            "toilets_count": max(1, int(round(total_built_up_sqft / 600.0))),
            "balconies_count": 1 if total_built_up_sqft >= 800 else 0,
            "floors_count": floors,
            "room_size_details": {
                "Master Bedroom": "150.0 sq.ft",
                "Living/Dining": "220.0 sq.ft",
                "Kitchen": "90.0 sq.ft"
            },
            "carpet_area_per_floor_sqft": round(total_built_up_sqft * 0.80, 2),
            "walls_and_circulation_sqft": round(total_built_up_sqft * 0.20, 2),
            "calculated_built_up_area_per_floor_sqft": built_up_area_sqft,
            "total_built_up_area_all_floors_sqft": total_built_up_sqft
        }
    }

def calculate_room_based_house_estimation(
    rooms: int = 3,
    toilets: int = 3,
    balconies: int = 1,
    floors: int = 1,
    include_living_dining: bool = True,
    include_kitchen: bool = True,
    seismic_zone_v: bool = True
) -> Dict[str, Any]:
    """
    Computes residential construction and steel estimation based on room configurations
    (rooms, toilets, balconies, kitchen, living/dining) using National Building Code (NBC)
    and IS 8888 standard architectural dimensions, then calculates quantities per Assam PWD SOR 2025-2026.
    """
    # Standard Indian architectural dimensions (Carpet area in sq.ft)
    living_dining_sqft = 220.0 if include_living_dining else 0.0
    kitchen_sqft = 90.0 if include_kitchen else 0.0
    toilet_unit_sqft = 45.0
    balcony_unit_sqft = 50.0

    # Bedrooms sizing (Master: 150 sqft, 2nd: 130 sqft, 3rd+: 120 sqft)
    bedroom_area_sqft = 0.0
    room_breakdown = {}
    for i in range(1, rooms + 1):
        if i == 1:
            area = 150.0
            name = "Master Bedroom"
        elif i == 2:
            area = 130.0
            name = "Bedroom 2"
        else:
            area = 120.0
            name = f"Bedroom {i}"
        bedroom_area_sqft += area
        room_breakdown[name] = f"{area} sq.ft"

    toilets_total_sqft = toilets * toilet_unit_sqft
    balconies_total_sqft = balconies * balcony_unit_sqft

    # Net carpet area per floor
    carpet_area_per_floor_sqft = (
        bedroom_area_sqft +
        living_dining_sqft +
        kitchen_sqft +
        toilets_total_sqft +
        balconies_total_sqft
    )

    # Circulation factor for passages, lobby, staircase & walls (NBC standard: +22% over carpet area)
    wall_and_circulation_sqft = round(carpet_area_per_floor_sqft * 0.22, 2)
    built_up_area_per_floor_sqft = round(carpet_area_per_floor_sqft + wall_and_circulation_sqft, 2)

    # Run the comprehensive Assam PWD SOR 2025-2026 structural estimator
    estimation = calculate_residential_estimation(
        built_up_area_sqft=built_up_area_per_floor_sqft,
        floors=floors,
        seismic_zone_v=seismic_zone_v
    )

    # Attach detailed architectural area analysis to response
    estimation["architectural_configuration"] = {
        "rooms_count": rooms,
        "toilets_count": toilets,
        "balconies_count": balconies,
        "floors_count": floors,
        "room_size_details": room_breakdown,
        "living_dining_sqft": living_dining_sqft,
        "kitchen_sqft": kitchen_sqft,
        "toilets_total_sqft": toilets_total_sqft,
        "balconies_total_sqft": balconies_total_sqft,
        "carpet_area_per_floor_sqft": round(carpet_area_per_floor_sqft, 2),
        "walls_and_circulation_sqft": wall_and_circulation_sqft,
        "calculated_built_up_area_per_floor_sqft": built_up_area_per_floor_sqft,
        "total_built_up_area_all_floors_sqft": round(built_up_area_per_floor_sqft * floors, 2)
    }

    return estimation


def calculate_bridge_pile_estimation(
    peak_discharge_Q: float,
    lacey_silt_factor_f: float = 1.0,
    pile_diameter_mm: float = 1000.0,
    num_piles: int = 4,
    superstructure_span_m: float = 35.0,
    water_depth_above_bed_m: float = 4.0
) -> Dict[str, Any]:
    """
    Bridge bored pile & scour calculations per:
    - Assam PWD Bridge Works SOR 2025-2026 (Circular CE/PWD(R)/SOR/2025/114)
    - MoRTH-IRC/2025/FND-BRG & IRC:78-2014 & MoRTH Section 2200
    - Assam PWD Bridge Spec 2025 (>60m span criteria)
    """
    # Lacey's Regime Equation:
    # Mean Scour Depth: R = 0.473 * (Q / f)^(1/3)
    mean_scour_R = 0.473 * math.pow(peak_discharge_Q / max(lacey_silt_factor_f, 0.1), 1.0 / 3.0)
    
    # Maximum Scour Depth under Pier: D_pier = 2.0 * R
    max_scour_D_pier = 2.0 * mean_scour_R

    # Minimum Pile Embedment: At least 1/3 of max scour depth into firm bearing strata with SPT N > 35
    min_embedment_depth = max_scour_D_pier / 3.0

    # Total bored depth per pile below bed level
    total_pile_depth_below_bed_m = max_scour_D_pier + min_embedment_depth
    # Include depth through water column to pile cap if cast in situ from pier cap level
    effective_drilled_length_per_pile_m = total_pile_depth_below_bed_m + water_depth_above_bed_m

    dia_m = pile_diameter_mm / 1000.0
    pile_area_m2 = (math.pi / 4.0) * (dia_m ** 2)
    concrete_volume_per_pile_m3 = pile_area_m2 * effective_drilled_length_per_pile_m
    total_concrete_m3 = concrete_volume_per_pile_m3 * num_piles

    # MoRTH Reinforcement Ratio for bored RCC piles: 0.4% to 0.8% of gross cross-sectional area
    steel_percentage = 0.6  # 0.6% standard longitudinal steel
    steel_area_m2 = (steel_percentage / 100.0) * pile_area_m2
    # Density of steel = 7850 kg/m3
    steel_kg_per_m_longitudinal = steel_area_m2 * 7850.0
    # Add ~25% for circular confining stirrups / helical ties with 135 deg seismic hooks per IRC:112
    total_steel_kg_per_pile = (steel_kg_per_m_longitudinal * 1.25) * effective_drilled_length_per_pile_m
    total_steel_kg = total_steel_kg_per_pile * num_piles
    total_steel_tonnes = total_steel_kg / 1000.0

    # Recommended Concrete Grade per specifications:
    # Submerged piles: min M35 with min 400 kg/m3 cement (MoRTH Section 2200)
    # If span > 60m: M40 for piers and M45 for superstructure girders (Assam PWD Bridge Spec 2025)
    # If span > 30m: M40/M45 High-Performance Concrete (SOR Item 1.02)
    if superstructure_span_m > 60:
        concrete_item = "Item 1.02"
        concrete_desc = "High-Performance Concrete M45 for superstructure & M40 for piers (>60m span)"
        concrete_rate = KNOWLEDGE_BASE["rates"]["bridge"]["Item 1.02"]["rate"]
    elif superstructure_span_m > 30:
        concrete_item = "Item 1.02"
        concrete_desc = "High-Performance Concrete M40/M45 (>30m span river bridges)"
        concrete_rate = KNOWLEDGE_BASE["rates"]["bridge"]["Item 1.02"]["rate"]
    else:
        concrete_item = "Item 1.01"
        concrete_desc = "Structural Concrete M35 Grade"
        concrete_rate = KNOWLEDGE_BASE["rates"]["bridge"]["Item 1.01"]["rate"]

    rate_boring_linear_m = KNOWLEDGE_BASE["rates"]["bridge"]["Item 3.01"]["rate"]  # Rs. 4,550 / linear m (1000mm bored pile)
    rate_steel_tonne = KNOWLEDGE_BASE["rates"]["bridge"]["Item 2.01"]["rate"]       # Rs. 86,500 / metric tonne

    total_linear_metres_boring = effective_drilled_length_per_pile_m * num_piles
    cost_boring = total_linear_metres_boring * rate_boring_linear_m
    cost_concrete = total_concrete_m3 * concrete_rate
    cost_steel = total_steel_tonnes * rate_steel_tonne
    total_cost = cost_boring + cost_concrete + cost_steel

    # Breakdown of rebar for deep piles: predominantly 25mm / 20mm main vertical bars and 10mm spiral/ties
    pile_steel_distribution = {25: 0.60, 20: 0.20, 10: 0.20}
    rebar_breakdown = calculate_rebar_breakdown(
        total_steel_kg,
        distribution=pile_steel_distribution,
        rate_per_tonne=rate_steel_tonne
    )

    return {
        "project_type": "Deep Bridge Foundation - Bored Cast-in-Situ RCC Piles",
        "governing_specifications": [
            "IRC:78-2014 & MoRTH 5th Revision Section 2200",
            "Assam PWD Bridge Works SOR 2025-2026 (Circular CE/PWD(R)/SOR/2025/114)",
            "IRC:112 & IRC:83"
        ],
        "scour_hydraulics": {
            "peak_discharge_Q_cumecs": peak_discharge_Q,
            "lacey_silt_factor_f": lacey_silt_factor_f,
            "mean_scour_depth_R_metres": round(mean_scour_R, 3),
            "max_scour_depth_under_pier_Dpier_metres": round(max_scour_D_pier, 3),
            "min_embedment_depth_metres": round(min_embedment_depth, 3),
            "embedment_criterion": "Embedment >= 1/3 D_pier into firm bearing strata with SPT N-value > 35"
        },
        "pile_geometry": {
            "pile_diameter_mm": pile_diameter_mm,
            "number_of_piles": num_piles,
            "drilled_depth_per_pile_m": round(effective_drilled_length_per_pile_m, 2),
            "total_drilled_linear_metres": round(total_linear_metres_boring, 2),
            "total_concrete_m3": round(total_concrete_m3, 2),
            "clear_cover_mm": 75,
            "clear_cover_note": "75mm min for deep piles cast in underwater saline/muddy conditions"
        },
        "reinforcement_details": {
            "longitudinal_ratio_pct": steel_percentage,
            "total_steel_kg": round(total_steel_kg, 2),
            "total_steel_tonnes": round(total_steel_tonnes, 3),
            "grade": "Fe 500D TMT Bars (IS 1786)",
            "rebar_breakdown": rebar_breakdown
        },
        "cost_breakdown": {
            "boring_piling_inr": round(cost_boring, 2),
            "concrete_m3_inr": round(cost_concrete, 2),
            "steel_rebar_inr": round(cost_steel, 2),
            "total_foundation_cost_inr": round(total_cost, 2)
        },
        "seismic_and_bearing_provisions": {
            "seismic_zone": "Assam Seismic Zone V",
            "stirrups": "Ductile confining stirrups per IRC:112 with 135-degree seismic hooks and 10d extension",
            "bearings": "Pot-PTFE or Elastomeric bearings conforming to IRC:83",
            "transverse_arrestors": "Transverse seismic arrestor blocks of minimum 400mm thickness on pier caps (Richter 7.0+ protection)"
        }
    }

def calculate_water_retaining_structure(
    length_m: float,
    width_m: float,
    depth_m: float,
    wall_thickness_m: float = 0.25,
    raft_thickness_m: float = 0.30
) -> Dict[str, Any]:
    """
    Swimming Pool and Liquid Retaining Structures per IS 3370 (Part 1-4) & SP 34:
    - M30 concrete with max w/c 0.45
    - Integral crystalline waterproofing admixture @ 2% by weight of cement
    - Crack width limit <= 0.1mm
    - 20mm x 10mm swellable hydrophilic bentonite-rubber waterstop along cold joints
    - Dual-layer waterproofing slurry + 145 gsm mesh
    - Vitrified mosaic / ceramic pool tiles
    - Hydrostatic relief valve safety
    """
    # Internal wetted surface area: Floor + 4 walls
    floor_area = length_m * width_m
    perimeter = 2.0 * (length_m + width_m)
    wall_wetted_area = perimeter * depth_m
    total_wetted_area_m2 = round(floor_area + wall_wetted_area, 2)

    # Waterstop length = perimeter cold construction joint between base raft slab & perimeter walls
    waterstop_linear_m = round(perimeter, 2)

    # Concrete volume:
    # Raft footprint = (length + 2*wall_thickness + 0.3 overhang) * (width + 2*wall_thickness + 0.3)
    raft_L = length_m + (2 * wall_thickness_m) + 0.4
    raft_W = width_m + (2 * wall_thickness_m) + 0.4
    raft_volume_m3 = raft_L * raft_W * raft_thickness_m
    wall_volume_m3 = perimeter * wall_thickness_m * depth_m
    total_concrete_m3 = round(raft_volume_m3 + wall_volume_m3, 2)

    # Cement content @ 380 kg/m3 for M30 design mix
    cement_kg = total_concrete_m3 * 380.0
    crystalline_admixture_kg = round(cement_kg * 0.02, 2)  # 2% by weight of cement

    # Steel for crack width control (IS 3370): ~115 kg per m3 of concrete
    total_steel_kg = round(total_concrete_m3 * 115.0, 2)
    total_steel_tonnes = round(total_steel_kg / 1000.0, 3)

    # Rates
    rate_waterstop = KNOWLEDGE_BASE["rates"]["liquid_retaining"]["waterstop"]["rate"]           # 450 / m
    rate_dual_waterproof = KNOWLEDGE_BASE["rates"]["liquid_retaining"]["dual_waterproofing"]["rate"] # 650 / m2
    rate_tiles = KNOWLEDGE_BASE["rates"]["liquid_retaining"]["pool_tiles"]["rate"]              # 1,450 / m2
    rate_steel_tonne = 82000.0  # Fe500D
    rate_m30_concrete = 8000.0  # M30 mix estimation

    cost_waterstop = waterstop_linear_m * rate_waterstop
    cost_waterproofing = total_wetted_area_m2 * rate_dual_waterproof
    cost_tiles = total_wetted_area_m2 * rate_tiles
    cost_concrete = total_concrete_m3 * rate_m30_concrete
    cost_steel = total_steel_tonnes * rate_steel_tonne

    total_cost = cost_waterstop + cost_waterproofing + cost_tiles + cost_concrete + cost_steel

    # Steel breakdown (12mm and 10mm bars dominate crack-control two-way meshes)
    rebar_distribution = {12: 0.50, 10: 0.35, 8: 0.15}
    rebar_breakdown = calculate_rebar_breakdown(
        total_steel_kg,
        distribution=rebar_distribution,
        rate_per_tonne=rate_steel_tonne
    )

    return {
        "project_type": "Swimming Pool / Water Retaining Structure",
        "governing_standard": "IS 3370 (Part 1-4) & SP 34 Concrete Storage Structures",
        "dimensions": {
            "length_m": length_m,
            "width_m": width_m,
            "depth_m": depth_m,
            "capacity_litres": round(length_m * width_m * depth_m * 1000.0, 1),
            "wetted_surface_area_m2": total_wetted_area_m2,
            "construction_joint_length_m": waterstop_linear_m
        },
        "concrete_and_waterproofing": {
            "concrete_volume_m3": total_concrete_m3,
            "concrete_grade": "M30 Design Mix (IS 3370)",
            "water_cement_ratio": "<= 0.45",
            "crystalline_admixture_kg": crystalline_admixture_kg,
            "crack_width_limit": "<= 0.1mm under design hydrostatic head",
            "waterstop_spec": "20mm x 10mm bentonite-rubber swellable waterstop bar",
            "dual_layer_waterproofing_spec": "2-coat polymer-modified cementitious slurry + 145 gsm mesh (1.5-2.0mm DFT)"
        },
        "reinforcement": {
            "total_steel_kg": total_steel_kg,
            "total_steel_tonnes": total_steel_tonnes,
            "grade": "Fe500D (IS 1786)",
            "rebar_breakdown": rebar_breakdown
        },
        "cost_breakdown": {
            "waterstop_joint_sealing_inr": round(cost_waterstop, 2),
            "dual_layer_waterproofing_inr": round(cost_waterproofing, 2),
            "pool_finishes_and_tiles_inr": round(cost_tiles, 2),
            "concrete_inr": round(cost_concrete, 2),
            "steel_inr": round(cost_steel, 2),
            "total_cost_inr": round(total_cost, 2)
        },
        "groundwater_precaution": "In high groundwater regions, pressure relief valves (hydrostatic relief valves) must be installed in main drains to prevent pool floor flotation during emptying."
    }
