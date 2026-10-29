"""
Unit tests for deterministic civil engineering calculators
"""

import pytest
from app.calculators import (
    calculate_rebar_weight,
    calculate_number_of_rods,
    calculate_rebar_breakdown,
    calculate_residential_estimation,
    calculate_bridge_pile_estimation,
    calculate_water_retaining_structure
)
from app.knowledge_base import KNOWLEDGE_BASE

def test_rebar_weight_calculation():
    # 8mm bar: 0.395 kg/m * 100m = 39.5 kg
    w_8mm = calculate_rebar_weight(8, 100.0)
    assert abs(w_8mm - 39.5) < 0.5

    # 12mm bar: 0.888 kg/m * 12m = 10.66 kg
    w_12mm = calculate_rebar_weight(12, 12.0)
    assert abs(w_12mm - 10.66) < 0.2

def test_number_of_rods():
    # 1000 kg of 12mm steel (10.66 kg per 12m rod) -> ~94 rods
    result = calculate_number_of_rods(12, 1000.0)
    assert result["diameter_mm"] == 12
    assert result["standard_12m_rods_count"] == 94
    assert result["approx_bundles"] == 19  # 94 / 5 rods per bundle

def test_residential_estimation():
    # 1000 sq.ft ground floor (G) in Assam Seismic Zone V
    result = calculate_residential_estimation(1000.0, floors=1, seismic_zone_v=True)
    
    assert result["inputs"]["floor_plate_sqft"] == 1000.0
    assert result["inputs"]["number_of_floors"] == 1
    # Steel = 1000 * 4.5 = 4500 kg (4.5 tonnes)
    assert result["material_quantities"]["steel_fe500d"]["total_kg"] == 4500.0
    assert result["material_quantities"]["steel_fe500d"]["total_tonnes"] == 4.5
    # Steel rate = Rs. 82,000 / MT (Item 102)
    assert result["material_quantities"]["steel_fe500d"]["subtotal_cost_inr"] == 4.5 * 82000.0
    # Concrete M25 = 1000 * 0.04 = 40 m3 @ Rs. 7,200/m3 (Item 101)
    assert result["material_quantities"]["concrete_m25"]["volume_m3"] == 40.0
    assert result["material_quantities"]["concrete_m25"]["subtotal_cost_inr"] == 40.0 * 7200.0
    # Total rods calculated
    assert result["material_quantities"]["steel_fe500d"]["total_12m_rods"] > 0
    # Seismic Zone V provisions present
    assert "IS 13920" in result["seismic_safety_provisions"]["code"]

def test_bridge_pile_scour_hydraulics():
    # Q = 3500 cumecs, f = 1.0
    result = calculate_bridge_pile_estimation(peak_discharge_Q=3500.0, lacey_silt_factor_f=1.0)
    
    # R = 0.473 * (3500)^(1/3) = 0.473 * 15.1829 = ~7.181m
    assert abs(result["scour_hydraulics"]["mean_scour_depth_R_metres"] - 7.181) < 0.05
    # D_pier = 2 * R = ~14.363m
    assert abs(result["scour_hydraulics"]["max_scour_depth_under_pier_Dpier_metres"] - 14.363) < 0.1
    # Embedment >= 1/3 D_pier
    assert abs(result["scour_hydraulics"]["min_embedment_depth_metres"] - (14.363 / 3)) < 0.1
    # Check clear cover is 75mm min for underwater piles per MoRTH Section 2200
    assert result["pile_geometry"]["clear_cover_mm"] == 75

def test_water_retaining_pool():
    # 15m x 8m x 2m pool
    result = calculate_water_retaining_structure(length_m=15.0, width_m=8.0, depth_m=2.0)
    
    # Waterstop length = 2 * (15 + 8) = 46m
    assert result["dimensions"]["construction_joint_length_m"] == 46.0
    # Waterstop rate = Rs. 450 / m
    assert result["cost_breakdown"]["waterstop_joint_sealing_inr"] == 46.0 * 450.0
    # Dual-layer waterproofing rate = Rs. 650 / m2
    wetted_area = 15*8 + 2*(15+8)*2  # 120 + 92 = 212 m2
    assert result["dimensions"]["wetted_surface_area_m2"] == 212.0
    assert result["cost_breakdown"]["dual_layer_waterproofing_inr"] == 212.0 * 650.0
    # Pool tiles rate = Rs. 1,450 / m2
    assert result["cost_breakdown"]["pool_finishes_and_tiles_inr"] == 212.0 * 1450.0
    # Crystalline admixture at 2% cement
    assert result["concrete_and_waterproofing"]["crystalline_admixture_kg"] > 0
