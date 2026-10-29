"""
Pydantic Request & Response Schemas for API Endpoints
"""

from typing import List, Dict, Any, Optional
from pydantic import BaseModel, Field

# --- Chat Models ---
class ChatMessage(BaseModel):
    role: str = Field(..., description="Message author role: 'user', 'assistant', or 'system'")
    content: str = Field(..., description="Text content of the message")

class ChatRequest(BaseModel):
    messages: List[ChatMessage] = Field(..., description="Conversation history with the latest user query")
    model: Optional[str] = Field(None, description="Groq model override (e.g. 'llama-3.3-70b-versatile', 'llama-3.1-8b-instant')")
    api_key: Optional[str] = Field(None, description="Optional custom Groq API key (defaults to server environment key)")

class ToolExecutionInfo(BaseModel):
    tool: str
    arguments: Dict[str, Any]

class ChatResponse(BaseModel):
    reply: str
    model_used: str
    tools_executed: List[ToolExecutionInfo] = []
    structured_results: List[Dict[str, Any]] = []
    status: str

# --- Direct Estimation Models (for frontend forms / dashboard) ---
class RoomBasedHouseEstimateRequest(BaseModel):
    category: Optional[str] = Field("house", description="Category: 'house', 'building', or 'residential'")
    rooms: int = Field(3, ge=1, le=20, description="Number of bedrooms / main rooms (e.g., 3)")
    toilets: int = Field(3, ge=1, le=10, description="Number of bathrooms / toilets (e.g., 3)")
    balconies: int = Field(1, ge=0, le=10, description="Number of balconies (e.g., 1)")
    floors: int = Field(1, ge=1, le=10, description="Number of storeys (1 = G, 2 = G+1, 3 = G+2)")
    include_living_dining: bool = Field(True, description="Whether to include living and dining hall")
    include_kitchen: bool = Field(True, description="Whether to include kitchen")
    seismic_zone_v: bool = Field(True, description="Strict IS 13920 Seismic Zone V detailing (Assam standard)")

class ResidentialEstimateRequest(BaseModel):
    built_up_area_sqft: float = Field(..., gt=0, description="Floor plate built-up area in square feet")
    floors: int = Field(1, ge=1, le=10, description="Number of storeys (1 = G, 2 = G+1, 3 = G+2)")
    seismic_zone_v: bool = Field(True, description="Strict IS 13920 Seismic Zone V detailing (Assam standard)")

class RebarScheduleRequest(BaseModel):
    total_steel_kg: float = Field(..., gt=0, description="Total steel reinforcement in kilograms")
    application: str = Field("residential", description="'residential' or 'bridge'")

class BridgePileEstimateRequest(BaseModel):
    peak_discharge_Q: float = Field(..., gt=0, description="Peak flood discharge Q in cumecs (m3/s)")
    lacey_silt_factor_f: float = Field(1.0, gt=0, description="Lacey's silt factor f (typically 1.0 for sand bed)")
    pile_diameter_mm: float = Field(1000.0, gt=300, description="Diameter of bored piles in mm (Item 3.01 = 1000mm)")
    num_piles: int = Field(4, ge=1, description="Number of bored piles in the pier group")
    superstructure_span_m: float = Field(35.0, gt=0, description="Span of bridge superstructure in metres")
    water_depth_above_bed_m: float = Field(4.0, ge=0, description="Water depth to pile cut-off level in metres")

class SwimmingPoolEstimateRequest(BaseModel):
    length_m: float = Field(..., gt=0, description="Internal length in metres")
    width_m: float = Field(..., gt=0, description="Internal width in metres")
    depth_m: float = Field(..., gt=0, description="Water depth in metres")
    wall_thickness_m: float = Field(0.25, gt=0.1, description="Retaining wall thickness in metres")
    raft_thickness_m: float = Field(0.30, gt=0.1, description="Base raft slab thickness in metres")
