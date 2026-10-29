"""
Schedule of Rates & Specifications Lookup API
"""

from fastapi import APIRouter, Query
from typing import Dict, Any, Optional
from app.knowledge_base import KNOWLEDGE_BASE

router = APIRouter(prefix="/rates", tags=["Schedule of Rates (SOR 2025-2026)"])

@router.get("")
async def get_schedule_of_rates(
    category: Optional[str] = Query("all", description="Category: 'residential', 'bridge', 'liquid_retaining', 'standards', or 'all'")
) -> Dict[str, Any]:
    """
    Returns official approved government rates and codes from:
    - Assam PWD Bridge SOR 2025-2026 (Circular CE/PWD(R)/SOR/2025/114)
    - Assam PWD Building SOR 2025-2026 (Ref: PWRD/BLDG/SOR-2025/HSE-01)
    - IS 456, IS 1786, IS 13920, IS 3370, IRC:78-2014 & MoRTH Section 2200
    """
    if category == "residential":
        return {
            "category": "Residential Building Works",
            "rates": KNOWLEDGE_BASE["rates"]["residential"],
            "jurisdiction": KNOWLEDGE_BASE["jurisdiction"]["residential_building"]
        }
    elif category == "bridge":
        return {
            "category": "Bridge and Highway Works",
            "rates": KNOWLEDGE_BASE["rates"]["bridge"],
            "jurisdiction": KNOWLEDGE_BASE["jurisdiction"]["bridge_works"]
        }
    elif category == "liquid_retaining":
        return {
            "category": "Swimming Pools & Liquid Retaining",
            "rates": KNOWLEDGE_BASE["rates"]["liquid_retaining"]
        }
    elif category == "standards":
        return {
            "technical_specifications": KNOWLEDGE_BASE["technical_specifications"],
            "rebar_standards": KNOWLEDGE_BASE["rebar_standards"]
        }
    return KNOWLEDGE_BASE
