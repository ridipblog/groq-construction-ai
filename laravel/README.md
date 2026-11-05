# 🚀 Laravel Civil Structural Estimation API & Blade Studio

This directory contains the production-ready Laravel implementation of **BuildEstimate AI**, replicating the Python civil engineering calculation engines and Groq autonomous agent loop.

---

## 🏗️ Architecture Overview

- **Core Framework**: Laravel 11+ with pure PHP 8.3 typing
- **Calculators & Services**:
  - [`app/Services/Estimation/RoomBasedEstimator.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Estimation/RoomBasedEstimator.php): NBC room dimensions, +22% circulation, and full material breakdown.
  - [`app/Services/Estimation/ResidentialEstimator.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Estimation/ResidentialEstimator.php): IS 456 M25 concrete, Fe500D steel, brickwork, plaster, tiles.
  - [`app/Services/Estimation/RebarScheduleService.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Estimation/RebarScheduleService.php): Standard 12-meter commercial rods per diameter (8mm-32mm) & manufacturer bundles.
  - [`app/Services/Estimation/BridgePileService.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Estimation/BridgePileService.php): Lacey scour depth \(R = 0.473 \cdot (Q/f)^{1/3}\), pier scour \(2R\), MoRTH Section 2200 bored piles.
  - [`app/Services/Estimation/WaterRetainingService.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Estimation/WaterRetainingService.php): IS 3370 crack control, swellable hydrophilic waterstops, dual-layer waterproofing.
  - [`app/Services/Ai/GroqConstructionAgent.php`](file:///d:/Development/groq-construction-ai/laravel/app/Services/Ai/GroqConstructionAgent.php): Groq function calling agent with zero-token domain guardrail.
- **REST Endpoints**: [`routes/api.php`](file:///d:/Development/groq-construction-ai/laravel/routes/api.php)
- **Blade SPA Testing Studio**: [`resources/views/dashboard.blade.php`](file:///d:/Development/groq-construction-ai/laravel/resources/views/dashboard.blade.php) served at `GET /`.
- **Dart Client SDK**: [`dart_client_example.dart`](file:///d:/Development/groq-construction-ai/laravel/dart_client_example.dart) ready for Flutter/Dart mobile or web integration.

---

## ⚡ Quick Start

### 1. Configure Environment
Make sure your `.env` contains:
```env
GROQ_API_KEY=your_actual_groq_api_key_here
GROQ_MODEL=llama-3.3-70b-versatile
```

### 2. Run Tests
```powershell
php artisan test
```

### 3. Start Development Server
```powershell
php artisan serve
```

Access the **Blade Single Page App Studio** directly in your browser:
* **Interactive Studio**: [http://localhost:8000/](http://localhost:8000/)
* **Health Check**: [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health)
