<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category', 'all');
        $allRates = config('construction.rates', []);
        $jurisdiction = config('construction.jurisdiction', []);
        $standards = [
            'technical_specifications' => config('construction.technical_specifications', []),
            'rebar_standards' => config('construction.rebar_standards', [])
        ];

        if ($category === 'residential') {
            return response()->json([
                'category' => 'Residential Building Works',
                'rates' => $allRates['residential'] ?? [],
                'jurisdiction' => $jurisdiction['residential_building'] ?? ''
            ]);
        }

        if ($category === 'bridge') {
            return response()->json([
                'category' => 'Bridge and Highway Works',
                'rates' => $allRates['bridge'] ?? [],
                'jurisdiction' => $jurisdiction['bridge_works'] ?? ''
            ]);
        }

        if ($category === 'liquid_retaining') {
            return response()->json([
                'category' => 'Swimming Pools & Liquid Retaining',
                'rates' => $allRates['liquid_retaining'] ?? []
            ]);
        }

        if ($category === 'boundary_wall') {
            return response()->json([
                'category' => 'Boundary Wall & Fencing Works',
                'rates' => $allRates['boundary_wall'] ?? [],
                'jurisdiction' => 'Assam PWD (Building) SOR 2025-2026'
            ]);
        }

        if ($category === 'commercial') {
            return response()->json([
                'category' => 'Commercial Building Works',
                'rates' => $allRates['commercial'] ?? [],
                'jurisdiction' => $jurisdiction['residential_building'] ?? 'Assam PWD & NBC 2016'
            ]);
        }

        if ($category === 'warehouse_peb') {
            return response()->json([
                'category' => 'Industrial Pre-Engineered Building (PEB) Warehouse',
                'rates' => $allRates['warehouse_peb'] ?? [],
                'jurisdiction' => 'Assam PWD Industrial Infrastructure & IS 800'
            ]);
        }

        if ($category === 'road_and_drainage') {
            return response()->json([
                'category' => 'Highway Pavements & Stormwater Drainage',
                'rates' => $allRates['road_and_drainage'] ?? [],
                'jurisdiction' => 'Assam PWRD Roads SOR 2025-2026 & MoRTH'
            ]);
        }

        if ($category === 'standards') {
            return response()->json($standards);
        }

        return response()->json([
            'jurisdiction' => $jurisdiction,
            'rates' => $allRates,
            'rebar_standards' => config('construction.rebar_standards', [])
        ]);
    }
}

