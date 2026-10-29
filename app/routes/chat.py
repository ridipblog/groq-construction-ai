"""
Chat API Route for Construction Agent
"""

from fastapi import APIRouter, HTTPException
from app.models import ChatRequest, ChatResponse
from app.agent import ConstructionAgent

router = APIRouter(prefix="/chat", tags=["Agent Chat"])

@router.post("", response_model=ChatResponse)
async def chat_with_agent(req: ChatRequest):
    """
    Main endpoint for Frontend App chat interface.
    Accepts conversational history, leverages Groq LLM (e.g. llama-3.3-70b-versatile)
    and executes civil engineering estimation tools autonomously.
    """
    agent = ConstructionAgent(api_key=req.api_key, model=req.model)
    msg_dicts = [{"role": m.role, "content": m.content} for m in req.messages]
    
    result = agent.process_chat(messages=msg_dicts, custom_model=req.model)
    return ChatResponse(**result)
