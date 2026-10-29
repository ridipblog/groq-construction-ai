# 🏗️ Construction Estimation Agent API (Groq + Official PWD SOR 2025-2026)

A production-ready civil structural estimation chatbot agent and REST API designed specifically for construction apps. Built with **FastAPI**, **Groq LLM Models** (`llama-3.3-70b-versatile`, `llama-3.1-8b-instant`), and calibrated with official **Schedule of Rates (SOR 2025-2026)** and **Indian Standards (IS Codes / MoRTH / IRC)**.

---

## 🌟 Key Capabilities

1. **Indian Site Engineering & Rebar (Sariya) Scheduler**:
   - Converts structural steel into **standard commercial 12-meter (40-ft) steel rod counts** and **commercial bundles**.
   - Standard Indian site units:
     - **Sariya (TMT Rebar)**: Fe 500D (IS 1786) in Quintals and Metric Tonnes (Tata Tiscon / JSW / SAIL standards).
     - **Cement Bags**: Standard 50kg bags (OPC 43/53 or PPC).
     - **Sand (Reti / M-Sand)**: In **Brass** (1 Brass = 100 cu.ft) and CFT.
     - **Aggregate (Rori / Gitti)**: 20mm & 10mm down graded blue metal in **Brass** and CFT.
     - **Binding wire**: ~12 kg per metric tonne (1.2%).
   - Bar weight formula: $W = \frac{d^2}{162.28}$ kg/m.

2. **Zero-Token Guardrails & Scope Enforcer**:
   - Strictly refuses non-construction queries instantly **without consuming any Groq API tokens**.
   - Output length optimized to strictly under 500 words to minimize token bills and latency.

2. **Official Schedule of Rates (SOR 2025-2026) Calibration**:
   - **Assam PWD Residential Building SOR (PWRD/BLDG/SOR-2025/HSE-01)**:
     - Item 101: Structural Concrete M25 Grade — ₹7,200.00 / m³
     - Item 102: Reinforcement Steel Fe500D (IS 1786) — ₹82,000.00 / MT (₹82.00/kg)
     - Item 103: First Class Brickwork Masonry (IS 2212) — ₹12,500.00 / 1,000 bricks
     - Item 104: Cement Plastering Works (IS 1661) — ₹320.00 / m²
     - Item 105: Vitrified Tile Flooring (IS 15622) — ₹1,150.00 / m²
   - **Assam PWD Bridge Works SOR (Circular No. CE/PWD(R)/SOR/2025/114)**:
     - Item 1.01: Structural Concrete Superstructure (M35 Grade) — ₹8,650.00 / m³
     - Item 1.02: High-Performance Concrete (M40 / M45 Grade) — ₹9,450.00 / m³
     - Item 2.01: Reinforcement Steel Fe500D — ₹86,500.00 / MT
     - Item 3.01: Bored Cast-in-Situ RCC Piles (1000mm Dia) — ₹4,550.00 / linear metre
     - Item 4.01: Centering and Shuttering (MoRTH Section 1500) — ₹820.00 / m²

3. **Seismic & River Hydraulic Standards**:
   - **Seismic Zone V (IS 13920 / IRC:112)**: Mandatory 135-degree ductile hooks with 10d extension at beam-column junctions.
   - **Bridge Scour & Foundation (MoRTH & IRC:78-2014)**:
     - Lacey Mean Scour Depth: $R = 0.473 \times (Q / f)^{1/3}$
     - Maximum Pier Scour: $D_{pier} = 2.0 \times R$
     - Embedment $\ge 1/3 \times D_{pier}$ into strata with SPT $N > 35$.
     - Minimum 75mm clear cover for underwater bored piles.
   - **Water Retaining & Swimming Pools (IS 3370 & SP 34)**:
     - M30 concrete, w/c $\le 0.45$, crystalline waterproofing admixture (2% by cement weight).
     - 20x10mm swellable hydrophilic bentonite-rubber waterstop (₹450/m) along cold joints.
     - Dual-layer waterproofing slurry + 145 gsm mesh (₹650/m²) and vitrified pool tiles (₹1,450/m²).

4. **Groq Function Calling Engine**:
   - Integrates deterministic Python calculation tools with Groq LLM tool calling.
   - Eliminates math hallucinations; ensures 100% calculation accuracy.

---

## 🚀 Quick Start

### 1. Configure Environment
Copy `.env.example` to `.env` and set your Groq API key:
```env
GROQ_API_KEY=gsk_your_actual_groq_api_key_here
GROQ_MODEL=llama-3.3-70b-versatile
PORT=8000
```
*(If no API key is set initially, the agent operates in deterministic calculation fallback mode with full estimation data).*

### 2. Run the Server
```bash
python run_server.py
```
Or with uvicorn directly:
```bash
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

- **Interactive Web Playground**: Visit `http://localhost:8000/`
- **Swagger API Documentation**: Visit `http://localhost:8000/docs`

---

## 📱 Frontend Integration Guide

### 1. Chatbot Endpoint: `POST /api/chat`
Send conversational messages from your frontend chat UI.

**Request:**
```json
{
  "messages": [
    {
      "role": "user",
      "content": "I am building a 1500 sq.ft G+1 residential house in Guwahati. How many steel rods and concrete do I need, and what is the cost?"
    }
  ],
  "model": "llama-3.3-70b-versatile"
}
```

**Response:**
```json
{
  "reply": "### 🏗️ Residential Construction Estimation...\n\n#### 1. Steel Rods (Fe500D Rebar) Schedule...",
  "model_used": "llama-3.3-70b-versatile",
  "tools_executed": [
    {
      "tool": "estimate_residential_construction",
      "arguments": {
        "built_up_area_sqft": 1500,
        "floors": 2,
        "seismic_zone_v": true
      }
    }
  ],
  "structured_results": [
    {
      "tool": "estimate_residential_construction",
      "result": {
        "material_quantities": { ... },
        "rebar_breakdown": {
          "total_steel_kg": 13500.0,
          "total_steel_tonnes": 13.5,
          "total_standard_12m_rods": 1284,
          "bar_schedule": [
            { "diameter_mm": 8, "total_weight_kg": 2970.0, "standard_12m_rods_count": 627, "approx_bundles": 63 },
            { "diameter_mm": 10, "total_weight_kg": 2430.0, "standard_12m_rods_count": 329, "approx_bundles": 47 },
            { "diameter_mm": 12, "total_weight_kg": 4050.0, "standard_12m_rods_count": 380, "approx_bundles": 76 },
            { "diameter_mm": 16, "total_weight_kg": 3375.0, "standard_12m_rods_count": 179, "approx_bundles": 60 },
            { "diameter_mm": 20, "total_weight_kg": 675.0, "standard_12m_rods_count": 23, "approx_bundles": 12 }
          ]
        },
        "cost_summary": {
          "steel_cost_inr": 1107000.0,
          "concrete_cost_inr": 864000.0,
          "total_estimated_materials_cost_inr": 2762016.75
        }
      }
    }
  ],
  "status": "success"
}
```

---

### 2. Direct Calculator Endpoints
Perfect for dedicated estimation tabs, forms, or chart generation:

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/estimate/residential` | `POST` | Calculates rebar, concrete, bricks, plaster, tiles & cost for G, G+1, G+2 houses |
| `/api/estimate/rebar-schedule` | `POST` | Converts any steel weight into exact 12m commercial rod counts & bundles |
| `/api/estimate/bridge-pile` | `POST` | Lacey river scour depth, bored RCC pile length, M35/M40/M45 concrete & Fe500D cage |
| `/api/estimate/swimming-pool` | `POST` | IS 3370 waterstop length, dual waterproofing, M30 mix & mosaic tiles |
| `/api/rates` | `GET` | Fetches verified Assam PWD SOR 2025-2026 rates and code specifications |

---

### 3. Code Examples for Frontend Apps

#### JavaScript (React / Next.js / Vue):
```javascript
// Function to send user prompt to Construction Agent
async function askConstructionBot(userPrompt, conversationHistory = []) {
  const response = await fetch("http://localhost:8000/api/chat", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      messages: [
        ...conversationHistory,
        { role: "user", content: userPrompt }
      ]
    })
  });
  const data = await response.json();
  return {
    markdownReply: data.reply,
    structuredData: data.structured_results
  };
}
```

#### Flutter / Dart:
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> getResidentialEstimation({
  required double builtUpSqft,
  required int floors,
}) async {
  final url = Uri.parse('http://YOUR_SERVER_IP:8000/api/estimate/residential');
  final response = await http.post(
    url,
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'built_up_area_sqft': builtUpSqft,
      'floors': floors,
      'seismic_zone_v': true,
    }),
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to fetch construction estimation');
  }
}
```

---

## 🧪 Running Tests
```bash
# Run all unit tests for calculators and API endpoints
python -m pytest -v
```
All 10 tests verify exact formulas, rebar unit weights, SOR rates, and API routing.
