"""
Agent Tool Declarations and Tool Dispatcher for Groq Function Calling
"""

import json
from typing import Dict, Any, List
from app.calculators import (
    calculate_residential_estimation,
    calculate_room_based_house_estimation,
    calculate_rebar_breakdown,
    calculate_number_of_rods,
    calculate_bridge_pile_estimation,
    calculate_water_retaining_structure,
    REBAR_UNIT_WEIGHTS
)
from app.knowledge_base import KNOWLEDGE_BASE

# Groq tool schema declarations
AGENT_TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "estimate_residential_construction",
            "description": "Calculates detailed quantities and costs for residential construction per Assam PWD Building SOR 2025-2026. Computes steel rebar (Fe500D) with 12m standard rod counts per diameter, M25 concrete, bricks, plastering, flooring, and IS 13920 Seismic Zone V provisions.",
            "parameters": {
                "type": "object",
                "properties": {
                    "built_up_area_sqft": {
                        "type": "number",
                        "description": "Built-up area per floor in square feet (e.g., 1000, 1500, 2000)."
                    },
                    "rooms": {
                        "type": "integer",
                        "description": "Number of bedrooms / main rooms (e.g., 3). If specified along with toilets, calculates room dimensions automatically."
                    },
                    "toilets": {
                        "type": "integer",
                        "description": "Number of bathrooms / toilets (e.g., 3)."
                    },
                    "balconies": {
                        "type": "integer",
                        "description": "Number of balconies (e.g., 1)."
                    },
                    "floors": {
                        "type": "integer",
                        "description": "Number of floors (e.g., 1 for Ground floor only G, 2 for G+1, 3 for G+2). Defaults to 1.",
                        "default": 1
                    },
                    "seismic_zone_v": {
                        "type": "boolean",
                        "description": "Whether the structure is in Seismic Zone V requiring IS 13920 ductile detailing (Assam standard). Defaults to True.",
                        "default": True
                    }
                }
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "estimate_rebar_schedule_and_rods",
            "description": "Generates a complete bar bending schedule and rod count for a given total steel weight. Calculates 12-meter commercial steel rods per diameter (8mm, 10mm, 12mm, 16mm, 20mm, 25mm), weights, bundles, binding wire, and costs using Assam PWD SOR rates.",
            "parameters": {
                "type": "object",
                "properties": {
                    "total_steel_kg": {
                        "type": "number",
                        "description": "Total steel rebar requirement in kilograms (kg)."
                    },
                    "application": {
                        "type": "string",
                        "enum": ["residential", "bridge", "foundation", "general"],
                        "description": "Application type to apply appropriate SOR rate (residential: Rs. 82,000/MT, bridge: Rs. 86,500/MT).",
                        "default": "residential"
                    }
                },
                "required": ["total_steel_kg"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "estimate_bridge_pier_and_piles",
            "description": "Calculates bridge deep foundation requirements (Lacey scour depth R, pier scour D_pier, pile embedment into SPT N>35, 1000mm bored cast-in-situ RCC pile length & volume, longitudinal steel cage, and Assam PWD Bridge Works SOR 2025-2026 rates).",
            "parameters": {
                "type": "object",
                "properties": {
                    "peak_discharge_Q": {
                        "type": "number",
                        "description": "Design flood peak discharge Q in cumecs (m3/s) (e.g. 2500, 5000)."
                    },
                    "lacey_silt_factor_f": {
                        "type": "number",
                        "description": "Lacey's silt factor f (typically 1.0 for medium sand riverbeds). Defaults to 1.0.",
                        "default": 1.0
                    },
                    "pile_diameter_mm": {
                        "type": "number",
                        "description": "Diameter of bored RCC piles in mm. Standard SOR Item 3.01 is 1000mm.",
                        "default": 1000.0
                    },
                    "num_piles": {
                        "type": "integer",
                        "description": "Number of piles in the foundation group. Defaults to 4.",
                        "default": 4
                    },
                    "superstructure_span_m": {
                        "type": "number",
                        "description": "Bridge span in metres. If >30m, high-performance concrete M40/M45 is specified. If >60m, Assam PWD Bridge Spec 2025 requires M40 pier / M45 girder.",
                        "default": 35.0
                    },
                    "water_depth_above_bed_m": {
                        "type": "number",
                        "description": "Depth of water column above riverbed to pile cap/ground level in metres. Defaults to 4.0.",
                        "default": 4.0
                    }
                },
                "required": ["peak_discharge_Q"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "estimate_swimming_pool_water_retaining",
            "description": "Calculates materials and costs for swimming pools and liquid retaining structures per IS 3370 and SP 34. Includes M30 concrete volume, crystalline waterproofing admixture (2%), 20x10mm swellable hydrophilic bentonite-rubber waterstop, dual-layer waterproofing slurry + 145 gsm mesh, vitrified mosaic tiles, and steel rebar.",
            "parameters": {
                "type": "object",
                "properties": {
                    "length_m": {
                        "type": "number",
                        "description": "Internal length of pool/tank in metres."
                    },
                    "width_m": {
                        "type": "number",
                        "description": "Internal width of pool/tank in metres."
                    },
                    "depth_m": {
                        "type": "number",
                        "description": "Water depth in metres."
                    },
                    "wall_thickness_m": {
                        "type": "number",
                        "description": "Retaining wall thickness in metres. Defaults to 0.25m.",
                        "default": 0.25
                    },
                    "raft_thickness_m": {
                        "type": "number",
                        "description": "Base raft slab thickness in metres. Defaults to 0.30m.",
                        "default": 0.30
                    }
                },
                "required": ["length_m", "width_m", "depth_m"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "lookup_schedule_of_rates",
            "description": "Retrieves official approved base rates, circular references, and code provisions from Assam PWD SOR 2025-2026, IS 456, IS 13920, IS 3370, MoRTH, and IRC standards.",
            "parameters": {
                "type": "object",
                "properties": {
                    "category": {
                        "type": "string",
                        "enum": ["residential", "bridge", "liquid_retaining", "standards", "all"],
                        "description": "Category of rates or technical specifications to inspect.",
                        "default": "all"
                    }
                }
            }
        }
    }
]

def execute_tool_call(tool_name: str, arguments: Dict[str, Any]) -> Any:
    """Executes a function call from the Groq Agent and returns the computed result."""
    if tool_name == "estimate_residential_construction":
        floors = int(arguments.get("floors", 1))
        zone_v = bool(arguments.get("seismic_zone_v", True))
        
        # If user provided room counts instead of raw sqft:
        if "rooms" in arguments and arguments.get("rooms"):
            rooms = int(arguments.get("rooms", 3))
            toilets = int(arguments.get("toilets", 2))
            balconies = int(arguments.get("balconies", 1))
            return calculate_room_based_house_estimation(
                rooms=rooms,
                toilets=toilets,
                balconies=balconies,
                floors=floors,
                seismic_zone_v=zone_v
            )

        area = float(arguments.get("built_up_area_sqft", 1000))
        return calculate_residential_estimation(built_up_area_sqft=area, floors=floors, seismic_zone_v=zone_v)

    elif tool_name == "estimate_rebar_schedule_and_rods":
        total_steel_kg = float(arguments.get("total_steel_kg", 1000))
        application = arguments.get("application", "residential")
        rate = 86500.0 if application == "bridge" else 82000.0
        return calculate_rebar_breakdown(total_steel_kg=total_steel_kg, rate_per_tonne=rate)

    elif tool_name == "estimate_bridge_pier_and_piles":
        q = float(arguments.get("peak_discharge_Q", 3000))
        f = float(arguments.get("lacey_silt_factor_f", 1.0))
        dia = float(arguments.get("pile_diameter_mm", 1000.0))
        num = int(arguments.get("num_piles", 4))
        span = float(arguments.get("superstructure_span_m", 35.0))
        water_depth = float(arguments.get("water_depth_above_bed_m", 4.0))
        return calculate_bridge_pile_estimation(
            peak_discharge_Q=q,
            lacey_silt_factor_f=f,
            pile_diameter_mm=dia,
            num_piles=num,
            superstructure_span_m=span,
            water_depth_above_bed_m=water_depth
        )

    elif tool_name == "estimate_swimming_pool_water_retaining":
        l = float(arguments.get("length_m", 15))
        w = float(arguments.get("width_m", 8))
        d = float(arguments.get("depth_m", 2))
        wall_t = float(arguments.get("wall_thickness_m", 0.25))
        raft_t = float(arguments.get("raft_thickness_m", 0.30))
        return calculate_water_retaining_structure(
            length_m=l,
            width_m=w,
            depth_m=d,
            wall_thickness_m=wall_t,
            raft_thickness_m=raft_t
        )

    elif tool_name == "lookup_schedule_of_rates":
        cat = arguments.get("category", "all")
        if cat in KNOWLEDGE_BASE["rates"]:
            return {cat: KNOWLEDGE_BASE["rates"][cat], "jurisdiction": KNOWLEDGE_BASE["jurisdiction"]}
        elif cat == "standards":
            return KNOWLEDGE_BASE["technical_specifications"]
        return KNOWLEDGE_BASE

    else:
        return {"error": f"Unknown tool name: {tool_name}"}
