"""
Construction Estimation Agent API Entrypoint
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse
import os
from pathlib import Path

from app.config import settings
from app.routes import chat, estimate, rates

app = FastAPI(
    title=settings.PROJECT_NAME,
    version=settings.VERSION,
    description="""
Civil Structural & Rebar Estimation Agent API powered by Groq and official Assam PWD Schedule of Rates (SOR 2025-2026).
- Autonomous Tool Calling for precise civil engineering math
- Steel rod (rebar) scheduling per diameter (8mm-32mm) and 12-meter commercial rod counts
- IS 456, IS 1786, IS 13920 (Seismic Zone V Ductile Details)
- MoRTH & IRC:78-2014 Deep Foundation and River Scour Calculations
- IS 3370 Swimming Pools and Liquid Retaining Structures
    """
)

# Enable CORS for all frontend apps (React, Next.js, Vue, Flutter, React Native, etc.)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount API routers
app.include_router(chat.router, prefix=settings.API_PREFIX)
app.include_router(estimate.router, prefix=settings.API_PREFIX)
app.include_router(rates.router, prefix=settings.API_PREFIX)

# Static files for browser test client
STATIC_DIR = Path(__file__).resolve().parent / "static"
if STATIC_DIR.exists():
    app.mount("/static", StaticFiles(directory=str(STATIC_DIR)), name="static")

@app.get("/", tags=["Root"])
async def root():
    """Serves the interactive web test playground or basic info."""
    index_file = STATIC_DIR / "index.html"
    if index_file.exists():
        return FileResponse(index_file)
    return {
        "name": settings.PROJECT_NAME,
        "version": settings.VERSION,
        "docs_url": "/docs",
        "api_chat_endpoint": f"{settings.API_PREFIX}/chat"
    }

@app.get("/health", tags=["Health"])
async def health_check():
    return {
        "status": "healthy",
        "groq_configured": bool(settings.GROQ_API_KEY),
        "groq_model": settings.GROQ_DEFAULT_MODEL
    }
