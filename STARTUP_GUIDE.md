# 🚀 BuildEstimate AI - Complete Startup & Execution Guide

This step-by-step guide explains how to start, configure, test, and connect the **Construction Estimation Agent API** from start to finish using terminal commands.

---

## 📋 Table of Contents
1. [Prerequisites](#1-prerequisites)
2. [Step 1: Open Terminal & Navigate to Project](#step-1-open-terminal--navigate-to-project)
3. [Step 2: Install Dependencies](#step-2-install-dependencies)
4. [Step 3: Configure Your Environment (.env)](#step-3-configure-your-environment-env)
5. [Step 4: Start the Server](#step-4-start-the-server)
6. [Step 5: Verify the Running Server](#step-5-verify-the-running-server)
7. [Step 6: Test Every Category with Commands](#step-6-test-every-category-with-commands)
8. [Step 7: Run Automated Tests](#step-7-run-automated-tests)
9. [Step 8: Connect Your Frontend App](#step-8-connect-your-frontend-app)
10. [Troubleshooting](#troubleshooting)

---

## 1. Prerequisites

Before starting, ensure you have:
* **Python 3.10+** installed (`python --version`)
* A **Groq API Key** (already configured in `.env`)
* PowerShell, Bash, or Command Prompt

---

## Step 1: Open Terminal & Navigate to Project

Open PowerShell or your command line terminal, and run:

```powershell
cd C:\Users\hp\.gemini\antigravity\scratch\construction-agent-api
```

Verify you are in the correct folder:
```powershell
dir
```
*(You should see `run_server.py`, `app`, `requirements.txt`, `.env`, etc.)*

---

## Step 2: Install Dependencies

Install all required Python packages:

```powershell
pip install -r requirements.txt
```

Packages installed:
* `fastapi`: High-performance asynchronous API framework
* `uvicorn`: ASGI web server with auto-reload
* `groq`: Official high-speed Groq SDK
* `pydantic`: Schema validation & typing
* `python-dotenv`: Environment variable manager
* `pytest` & `httpx`: Automated testing suite

---

## Step 3: Configure Your Environment (.env)

Your `.env` file is located at `C:\Users\hp\.gemini\antigravity\scratch\construction-agent-api\.env`.

Ensure it contains:
```env
# Groq API Key
GROQ_API_KEY=

# Top 120-Billion Parameter Model for Civil Engineering Reasoning & Function Calling
GROQ_MODEL=openai/gpt-oss-120b

# Server Port and Host
HOST=0.0.0.0
PORT=8000
DEBUG=false
```

---

## Step 4: Start the Server

Run the launcher script:

```powershell
python run_server.py
```

You will see output similar to:
```
🚀 Starting Construction Estimation Agent API v1.0.0
📡 API docs available at: http://localhost:8000/docs
💬 Web test playground at: http://localhost:8000/
INFO:     Uvicorn running on http://0.0.0.0:8000 (Press CTRL+C to quit)
INFO:     Started reloader process
INFO:     Application startup complete.
```

> [!TIP]
> Keep this terminal open while using the application. Auto-reload is enabled, so if you modify code, the server refreshes automatically.

---

## Step 5: Verify the Running Server

Open your browser and test these URLs:

1. **Interactive Web Playground**: [http://localhost:8000/](http://localhost:8000/)  
   *(Chat live with the agent, test preset estimators, and view steel rod calculations)*
2. **Interactive Swagger API Documentation**: [http://localhost:8000/docs](http://localhost:8000/docs)  
   *(Directly test every endpoint with a graphical interface)*
3. **Health Check Endpoint**: [http://localhost:8000/health](http://localhost:8000/health)

---

## Step 6: Test Every Category with Commands

Open a **second terminal** to test the endpoints using PowerShell or cURL.

### A. Chatbot Agent with Groq LLM (`POST /api/chat`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/chat" -Method Post -ContentType "application/json" -Body '{
  "messages": [
    {
      "role": "user",
      "content": "Estimate steel rods and concrete for a 1500 sq.ft G+1 house in Guwahati per Assam PWD SOR 2025-2026."
    }
  ]
}' | Select-Object -ExpandProperty reply
```

---

### B. House by Room Counts (`POST /api/estimate/by-room`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/estimate/by-room" -Method Post -ContentType "application/json" -Body '{
  "rooms": 3,
  "toilets": 3,
  "balconies": 1,
  "floors": 1,
  "seismic_zone_v": true
}' | ConvertTo-Json -Depth 5
```

---

### C. House by Total Area (`POST /api/estimate/residential`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/estimate/residential" -Method Post -ContentType "application/json" -Body '{
  "built_up_area_sqft": 1500,
  "floors": 2,
  "seismic_zone_v": true
}' | ConvertTo-Json -Depth 5
```

---

### D. Bridge Deep Foundations & Scour (`POST /api/estimate/bridge-pile`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/estimate/bridge-pile" -Method Post -ContentType "application/json" -Body '{
  "peak_discharge_Q": 4200,
  "lacey_silt_factor_f": 1.0,
  "pile_diameter_mm": 1000,
  "num_piles": 4,
  "superstructure_span_m": 45,
  "water_depth_above_bed_m": 5.0
}' | ConvertTo-Json -Depth 5
```

---

### E. Swimming Pool & Liquid Retaining (`POST /api/estimate/swimming-pool`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/estimate/swimming-pool" -Method Post -ContentType "application/json" -Body '{
  "length_m": 20,
  "width_m": 10,
  "depth_m": 2,
  "wall_thickness_m": 0.25,
  "raft_thickness_m": 0.30
}' | ConvertTo-Json -Depth 5
```

---

### F. Rebar Rod & Bundle Scheduler (`POST /api/estimate/rebar-schedule`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/estimate/rebar-schedule" -Method Post -ContentType "application/json" -Body '{
  "total_steel_kg": 6000,
  "application": "residential"
}' | ConvertTo-Json -Depth 5
```

---

### G. Look up Official Schedule of Rates (`GET /api/rates`)
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/rates?category=all" -Method Get | ConvertTo-Json -Depth 4
```

---

## Step 7: Run Automated Tests

To run the complete automated test suite (verifying math, rebar formulas, scour hydraulics, and endpoints):

```powershell
cd C:\Users\hp\.gemini\antigravity\scratch\construction-agent-api
$env:PYTHONPATH="."
python -m pytest -v
```

Expected output:
```
tests/test_api.py::test_health PASSED
tests/test_api.py::test_rates_endpoint PASSED
tests/test_api.py::test_direct_residential_estimate PASSED
tests/test_api.py::test_direct_rebar_schedule PASSED
tests/test_api.py::test_direct_by_rooms_estimate PASSED
tests/test_api.py::test_chat_endpoint_fallback PASSED
tests/test_calculators.py::test_rebar_weight_calculation PASSED
tests/test_calculators.py::test_number_of_rods PASSED
tests/test_calculators.py::test_residential_estimation PASSED
tests/test_calculators.py::test_bridge_pile_scour_hydraulics PASSED
tests/test_calculators.py::test_water_retaining_pool PASSED

======================== 11 passed in 0.45s ========================
```

---

## Step 8: Connect Your Frontend App

### JavaScript / TypeScript (React, Next.js, Vue, Angular)
```javascript
// Chat with Agent
async function askBot(prompt) {
  const res = await fetch("http://localhost:8000/api/chat", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      messages: [{ role: "user", content: prompt }]
    })
  });
  const data = await res.json();
  console.log("Agent response:", data.reply);
  console.log("Calculated numbers:", data.structured_results);
}

// Estimate House by Rooms
async function estimateByRooms(rooms, toilets, balconies) {
  const res = await fetch("http://localhost:8000/api/estimate/by-room", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      rooms: rooms,
      toilets: toilets,
      balconies: balconies,
      floors: 1
    })
  });
  const data = await res.json();
  console.log("Total 12m steel rods:", data.material_quantities.steel_fe500d.total_12m_rods);
  console.log("Total cost (INR):", data.cost_summary.total_estimated_materials_cost_inr);
}
```

### Flutter / Dart (Mobile App)
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> getHouseEstimate({
  required int rooms,
  required int toilets,
  required int balconies,
}) async {
  final url = Uri.parse('http://YOUR_SERVER_IP:8000/api/estimate/by-room');
  final response = await http.post(
    url,
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'rooms': rooms,
      'toilets': toilets,
      'balconies': balconies,
      'floors': 1,
    }),
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to load estimation');
  }
}
```

---

## Troubleshooting

### Port 8000 is already in use
If you get `Error: [Errno 10048] address already in use`:
1. Find the process occupying port 8000:
   ```powershell
   netstat -ano | findstr 8000
   ```
2. Stop that process (replace `PID` with the number in the right-most column):
   ```powershell
   taskkill /PID <PID> /F
   ```
3. Restart `python run_server.py`.

### Stopping the server
To stop the server at any time, go to the terminal running it and press:
```
Ctrl + C
```
