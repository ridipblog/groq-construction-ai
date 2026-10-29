"""
Groq Agent Orchestrator with Function Calling & Civil Engineering Persona
"""

import os
import json
from typing import List, Dict, Any, Optional
from groq import Groq
from app.config import settings
from app.tools import AGENT_TOOLS, execute_tool_call
from app.calculators import (
    calculate_residential_estimation,
    calculate_rebar_breakdown,
    calculate_bridge_pile_estimation,
    calculate_water_retaining_structure
)

SYSTEM_PROMPT = """You are 'BuildEstimate AI' - an expert Senior Civil Structural Engineer and Cost Estimation Agent for modern construction apps.

YOUR CORE EXPERTISE:
1. Civil engineering quantity surveying, structural rebar (steel rods) scheduling, and accurate budgeting.
2. Official Government of Assam PWD Schedule of Rates (SOR 2025-2026):
   - Bridge Works SOR (Circular No. CE/PWD(R)/SOR/2025/114)
   - Residential Building Works SOR (Document Ref: PWRD/BLDG/SOR-2025/HSE-01)
3. National Codes and Standards:
   - IS 456:2000 (Plain and Reinforced Concrete)
   - IS 1786 (High ductility Fe 500D TMT reinforcement bars)
   - IS 13920:2016 (Ductile design & detailing of reinforced concrete structures subjected to seismic forces - Assam is in Seismic Zone V)
   - IS 3370 (Part 1-4) & SP 34 (Liquid Retaining Structures & Swimming Pools)
   - IRC:78-2014 & MoRTH 5th Revision Section 2200 (Deep Foundations in Riverbed Alluvial Strata)
   - IRC:112 (Concrete Bridges & Ductile Seismic Detailing) and IRC:83 (Bearings)
   - Assam PWD Bridge Specification 2025 (>60m span river bridges)

GUIDELINES FOR STEEL ROD (REBAR) ESTIMATIONS:
- Always specify rebar in both metric tonnes / kilograms AND standard commercial 12-meter (40 feet) rod counts.
- Explain diameter distribution:
  * 8mm: Confinement ties, stirrups (IS 13920 requires 135-degree seismic hooks and 10d extension)
  * 10mm: Slab distribution bars, staircase reinforcement
  * 12mm: Slab main bars, lintels
  * 16mm: Column longitudinals & beam tensile/compression bars
  * 20mm & 25mm: Heavy columns, bridge pier cages, bored RCC piles
- Use the standard bar weight formula: Weight per metre = d^2 / 162.28 kg/m.
- Include binding wire requirements (~12 kg per metric tonne of steel).

CRITICAL INSTRUCTIONS:
- Whenever the user asks for estimations, material quantities, or steel rod schedules, ALWAYS call the corresponding calculation tools first to ensure 100% mathematical precision.
- Present your final output in clean, professional markdown with organized sections:
  1. Executive Summary & Project Parameters
  2. Steel Rebar Schedule & Rod Count Table (Diameter, Weight, 12m Standard Rods, Bundles, Use)
  3. Concrete & Key Material Quantities (M25/M35/M40, Bricks, Plaster, Tiles)
  4. Cost Breakdown per Assam PWD SOR 2025-2026
  5. Mandatory Seismic (Zone V) & Code Compliance Provisions
- Be polite, technically rigorous, and conversational.
"""

class ConstructionAgent:
    def __init__(self, api_key: Optional[str] = None, model: Optional[str] = None):
        self.api_key = api_key or settings.GROQ_API_KEY
        self.model = model or settings.GROQ_DEFAULT_MODEL
        self.client = Groq(api_key=self.api_key) if self.api_key else None

    def process_chat(
        self,
        messages: List[Dict[str, str]],
        custom_model: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Executes an agent turn with Groq API, supporting tool calling.
        If GROQ_API_KEY is not configured or offline, falls back gracefully
        to deterministic estimation execution with full calculation output.
        """
        active_model = custom_model or self.model
        
        # If no API key is provided, use deterministic calculation fallback
        if not self.client or not self.api_key:
            return self._fallback_deterministic_response(messages)

        formatted_messages = [{"role": "system", "content": SYSTEM_PROMPT}]
        for m in messages:
            formatted_messages.append({"role": m["role"], "content": m["content"]})

        tools_executed = []
        structured_results = []

        try:
            # First LLM call: LLM decides whether to call tools
            response = self.client.chat.completions.create(
                model=active_model,
                messages=formatted_messages,
                tools=AGENT_TOOLS,
                tool_choice="auto",
                temperature=0.2,
                max_tokens=2500
            )

            response_message = response.choices[0].message
            tool_calls = response_message.tool_calls

            # If model made tool calls, execute them and send back results
            if tool_calls:
                formatted_messages.append(response_message)
                for tool_call in tool_calls:
                    fn_name = tool_call.function.name
                    fn_args = json.loads(tool_call.function.arguments or "{}")
                    tool_output = execute_tool_call(fn_name, fn_args)
                    
                    tools_executed.append({
                        "tool": fn_name,
                        "arguments": fn_args
                    })
                    structured_results.append({
                        "tool": fn_name,
                        "result": tool_output
                    })

                    formatted_messages.append({
                        "role": "tool",
                        "tool_call_id": tool_call.id,
                        "name": fn_name,
                        "content": json.dumps(tool_output)
                    })

                # Second LLM call: Model generates final response incorporating tool results
                second_response = self.client.chat.completions.create(
                    model=active_model,
                    messages=formatted_messages,
                    temperature=0.2,
                    max_tokens=2500
                )
                final_content = second_response.choices[0].message.content
            else:
                final_content = response_message.content

            return {
                "reply": final_content,
                "model_used": active_model,
                "tools_executed": tools_executed,
                "structured_results": structured_results,
                "status": "success"
            }

        except Exception as e:
            # If an API or network error occurs, give informative feedback plus fallback calculations
            fallback = self._fallback_deterministic_response(messages)
            return {
                "reply": f"⚠️ Groq API Notice ({str(e)}). Providing calculation results:\n\n" + fallback["reply"],
                "error": str(e),
                "model_used": f"fallback ({active_model} error)",
                "tools_executed": fallback.get("tools_executed", []),
                "structured_results": fallback.get("structured_results", []),
                "status": "fallback"
            }

    def _fallback_deterministic_response(self, messages: List[Dict[str, str]]) -> Dict[str, Any]:
        """
        Graceful local fallback when GROQ_API_KEY is not yet supplied by user in .env.
        Inspects message keywords and executes appropriate calculators.
        """
        last_message = messages[-1]["content"].lower() if messages else ""
        
        # Check if bridge/pier/pile query
        if any(w in last_message for w in ["bridge", "pier", "pile", "scour", "morth", "alluvial"]):
            calc = calculate_bridge_pile_estimation(peak_discharge_Q=3500, lacey_silt_factor_f=1.0)
            reply = (
                "### 🏗️ Deep Bridge Foundation Estimation (MoRTH & Assam PWD SOR 2025-2026)\n\n"
                f"> **Governing Standard:** {', '.join(calc['governing_specifications'])}\n\n"
                f"- **Lacey Mean Scour Depth (R):** {calc['scour_hydraulics']['mean_scour_depth_R_metres']} m\n"
                f"- **Maximum Scour Depth under Pier (D_pier):** {calc['scour_hydraulics']['max_scour_depth_under_pier_Dpier_metres']} m\n"
                f"- **Minimum Pile Embedment into SPT N>35:** {calc['scour_hydraulics']['min_embedment_depth_metres']} m\n"
                f"- **Total Steel Required:** {calc['reinforcement_details']['total_steel_tonnes']} Tonnes ({calc['reinforcement_details']['total_steel_kg']} kg Fe500D)\n"
                f"- **Total Estimated Foundation Cost:** ₹{calc['cost_breakdown']['total_foundation_cost_inr']:,.2f}\n\n"
                "*(Set your `GROQ_API_KEY` in `.env` to enable full autonomous agent conversational responses)*"
            )
            return {
                "reply": reply,
                "model_used": "deterministic-engine (Groq key not configured)",
                "tools_executed": [{"tool": "estimate_bridge_pier_and_piles", "arguments": {"peak_discharge_Q": 3500}}],
                "structured_results": [{"tool": "estimate_bridge_pile_and_piles", "result": calc}],
                "status": "fallback"
            }

        # Check if swimming pool / water retaining query
        elif any(w in last_message for w in ["pool", "swimming", "tank", "is 3370", "waterproof"]):
            calc = calculate_water_retaining_structure(length_m=12.0, width_m=6.0, depth_m=1.8)
            reply = (
                "### 🏊 Swimming Pool / Water Retaining Structure Estimation (IS 3370 & SP 34)\n\n"
                f"- **Concrete (M30 Design Mix):** {calc['concrete_and_waterproofing']['concrete_volume_m3']} m³ (w/c <= 0.45)\n"
                f"- **Crystalline Waterproofing (2%):** {calc['concrete_and_waterproofing']['crystalline_admixture_kg']} kg\n"
                f"- **20x10mm Swellable Waterstop:** {calc['dimensions']['construction_joint_length_m']} linear metres @ ₹450/m\n"
                f"- **Dual-Layer Waterproofing:** {calc['dimensions']['wetted_surface_area_m2']} m² @ ₹650/m²\n"
                f"- **Pool Tiles / Vitrified Mosaic:** {calc['dimensions']['wetted_surface_area_m2']} m² @ ₹1,450/m²\n"
                f"- **Reinforcement Steel (Fe500D):** {calc['reinforcement']['total_steel_tonnes']} Tonnes ({calc['reinforcement']['rebar_breakdown']['total_standard_12m_rods']} standard 12m rods)\n"
                f"- **Total Estimated Cost:** ₹{calc['cost_breakdown']['total_cost_inr']:,.2f}\n\n"
                "*(Set your `GROQ_API_KEY` in `.env` to enable full autonomous agent conversational responses)*"
            )
            return {
                "reply": reply,
                "model_used": "deterministic-engine (Groq key not configured)",
                "tools_executed": [{"tool": "estimate_swimming_pool_water_retaining", "arguments": {"length_m": 12, "width_m": 6, "depth_m": 1.8}}],
                "structured_results": [{"tool": "estimate_swimming_pool_water_retaining", "result": calc}],
                "status": "fallback"
            }

        # Default residential estimation (e.g. 1000 or 1500 sqft G+1)
        else:
            calc = calculate_residential_estimation(built_up_area_sqft=1000, floors=2, seismic_zone_v=True)
            rebar = calc["rebar_breakdown"]
            
            rebar_table = "| Diameter | Weight (kg) | 12m Rods | Typical Bundles | Structural Use |\n|---|---|---|---|---|\n"
            for item in rebar["bar_schedule"]:
                rebar_table += f"| **{item['diameter_mm']} mm** | {item['total_weight_kg']} kg | **{item['standard_12m_rods_count']} rods** | ~{item['approx_bundles']} bundles | {item['typical_use']} |\n"

            reply = (
                "### 🏗️ Residential Construction Estimation (Assam PWD SOR 2025-2026)\n\n"
                f"**Project Scope:** G+1 RCC Framed House (Total Built-Up Area: 2,000 sq.ft) in **Assam Seismic Zone V**.\n\n"
                "#### 1. Steel Rods (Fe500D Rebar) Schedule:\n"
                f"- **Total Steel Required:** {rebar['total_steel_tonnes']} Tonnes ({rebar['total_steel_kg']} kg)\n"
                f"- **Total 12-Metre Rods:** **{rebar['total_standard_12m_rods']} rods**\n"
                f"- **Binding Wire Required:** {rebar['binding_wire_kg']} kg\n"
                f"- **Rate:** ₹82,000 per MT | **Steel Cost:** ₹{rebar['total_cost_inr']:,.2f}\n\n"
                f"{rebar_table}\n"
                "#### 2. Other Core Materials:\n"
                f"- **M25 Structural Concrete:** {calc['material_quantities']['concrete_m25']['volume_m3']} m³ @ ₹7,200/m³ = ₹{calc['cost_summary']['concrete_cost_inr']:,.2f}\n"
                f"- **1st Class Bricks (IS 2212):** {calc['material_quantities']['brickwork_masonry']['total_bricks_count']:,} bricks @ ₹12,500/1k = ₹{calc['cost_summary']['brickwork_cost_inr']:,.2f}\n"
                f"- **Cement Plastering (IS 1661):** {calc['material_quantities']['plastering']['area_m2']} m² @ ₹320/m² = ₹{calc['cost_summary']['plastering_cost_inr']:,.2f}\n"
                f"- **Vitrified Tiles (IS 15622):** {calc['material_quantities']['vitrified_tiles']['area_m2']} m² @ ₹1,150/m² = ₹{calc['cost_summary']['flooring_cost_inr']:,.2f}\n\n"
                f"#### 3. Total Material Cost Estimate:\n"
                f"**₹{calc['cost_summary']['total_estimated_materials_cost_inr']:,.2f}** (~₹{calc['cost_summary']['cost_per_sqft_inr']}/sq.ft)\n\n"
                "#### 4. Mandatory Seismic Zone V Provision:\n"
                "> All residential column-beam junctions must incorporate **IS 13920 ductile confinement links** with **135-degree hooks** and 10d extension.\n\n"
                "*(To enable the full natural language Groq Agent, simply add your `GROQ_API_KEY` in `.env`)*"
            )
            return {
                "reply": reply,
                "model_used": "deterministic-engine (Groq key not configured)",
                "tools_executed": [{"tool": "estimate_residential_construction", "arguments": {"built_up_area_sqft": 1000, "floors": 2}}],
                "structured_results": [{"tool": "estimate_residential_construction", "result": calc}],
                "status": "fallback"
            }
