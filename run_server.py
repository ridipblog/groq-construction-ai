"""
Convenience launcher for the Construction Estimation Agent API server
"""

import uvicorn
import os
import sys

# Ensure project root is in python path
current_dir = os.path.dirname(os.path.abspath(__file__))
if current_dir not in sys.path:
    sys.path.insert(0, current_dir)

from app.config import settings

if __name__ == "__main__":
    print(f"🚀 Starting {settings.PROJECT_NAME} v{settings.VERSION}")
    print(f"📡 API docs available at: http://{settings.HOST if settings.HOST != '0.0.0.0' else 'localhost'}:{settings.PORT}/docs")
    print(f"💬 Web test playground at: http://{settings.HOST if settings.HOST != '0.0.0.0' else 'localhost'}:{settings.PORT}/")
    uvicorn.run("app.main:app", host=settings.HOST, port=settings.PORT, reload=True)
