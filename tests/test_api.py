"""
API Endpoint Integration Tests using FastAPI TestClient
"""

import pytest
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_health():
    res = client.get("/health")
    assert res.status_code == 200
    data = res.json()
    assert data["status"] == "healthy"

def test_rates_endpoint():
    res = client.get("/api/rates?category=residential")
    assert res.status_code == 200
    data = res.json()
    assert "Item 101" in data["rates"]
    assert data["rates"]["Item 102"]["rate"] == 82000.0  # Fe500D rebar

def test_direct_residential_estimate():
    payload = {
        "built_up_area_sqft": 1500,
        "floors": 2,
        "seismic_zone_v": True
    }
    res = client.post("/api/estimate/residential", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert "material_quantities" in data
    assert data["material_quantities"]["steel_fe500d"]["total_kg"] == 13500.0
    assert data["rebar_breakdown"]["total_standard_12m_rods"] > 0

def test_direct_rebar_schedule():
    payload = {
        "total_steel_kg": 5000,
        "application": "residential"
    }
    res = client.post("/api/estimate/rebar-schedule", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert data["total_steel_kg"] == 5000.0
    assert len(data["bar_schedule"]) > 0

def test_direct_by_rooms_estimate():
    payload = {
        "category": "house",
        "rooms": 3,
        "toilets": 3,
        "balconies": 1,
        "floors": 1,
        "seismic_zone_v": True
    }
    res = client.post("/api/estimate/by-rooms", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert "architectural_configuration" in data
    assert data["architectural_configuration"]["rooms_count"] == 3
    assert data["architectural_configuration"]["toilets_count"] == 3
    assert data["architectural_configuration"]["balconies_count"] == 1
    assert data["material_quantities"]["steel_fe500d"]["total_12m_rods"] > 0
    assert data["cost_summary"]["total_estimated_materials_cost_inr"] > 0

def test_chat_endpoint_fallback():
    payload = {
        "messages": [
            {"role": "user", "content": "Estimate steel rods for a 1200 sqft G+1 house"}
        ]
    }
    res = client.post("/api/chat", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert "reply" in data
    assert len(data["reply"]) > 0
