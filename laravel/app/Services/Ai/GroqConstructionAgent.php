<?php

namespace App\Services\Ai;

use App\Services\Estimation\BridgePileService;
use App\Services\Estimation\RebarScheduleService;
use App\Services\Estimation\ResidentialEstimator;
use App\Services\Estimation\RoomBasedEstimator;
use App\Services\Estimation\WaterRetainingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqConstructionAgent
{
    protected const CONSTRUCTION_KEYWORDS = [
        'house', 'building', 'home', 'ghar', 'makaan', 'room', 'kamra', 'bhk', 'bedroom',
        'toilet', 'bathroom', 'balcony', 'kitchen', 'flat', 'plot', 'steel', 'sariya', 'rebar',
        'rod', 'tmt', 'fe500', 'fe500d', 'fe550', 'cement', 'bora', 'bag', 'concrete', 'rcc',
        'm20', 'm25', 'm30', 'm35', 'm40', 'm45', 'brick', 'eent', 'plaster', 'plastering',
        'flooring', 'tile', 'slab', 'chhat', 'dhalai', 'beam', 'column', 'pillar', 'footing',
        'plinth', 'lintel', 'foundation', 'bored', 'pile', 'bridge', 'pier', 'scour', 'lacey',
        'pool', 'tank', 'swimming', 'waterstop', 'waterproof', 'waterproofing', 'cost', 'estimate',
        'estimation', 'budget', 'rate', 'sor', 'pwd', 'cpwd', 'is 456', 'is 13920', 'is 1786',
        'is 3370', 'morth', 'irc', 'assam', 'guwahati', 'kamrup', 'silchar', 'dibrugarh',
        'jorhat', 'sqft', 'sq ft', 'square feet', 'sqm', 'gaj', 'brass', 'cft', 'tonne', 'ton',
        'quintal', 'kg', 'shuttering', 'centering', 'stirrup', 'tie', 'hook', 'seismic'
    ];

    protected const GREETING_WORDS = ['hi', 'hello', 'hey', 'namaste', 'pranam', 'good morning', 'good evening', 'good afternoon'];

    public const SYSTEM_PROMPT = <<<EOT
# Role & Identity
You are the Lead Site Engineering Advisor & Technical Consultant for **BuildEstimate AI**. You operate alongside deterministic PHP engineering calculation engines to assist contractors, civil engineers, and builders with structural quantities, material procurement, field execution, and IS/NBC compliance.

# Core Architecture & Strict Zero-Math Protocol
1. Never Calculate Critical Quantities Manually:
   - All steel tonnage, 12m commercial rod counts (sariya numbers per diameter), cement bags, brass/CFT aggregates, brick counts, and foundation scour depths are computed exclusively by backend PHP services.
   - If the user asks for an estimate, bill of quantities (BOQ), or steel breakdown, identify their parameters (built-up area, room counts, foundation type, soil/water parameters) and invoke the relevant estimation tool.
   - Do NOT invent or approximate numbers or formulas on the fly. The deterministic backend supplies the ground truth.

2. Injected Estimate Synchronization (active_estimate):
   - The user's screen displays a live BOQ, commercial rod schedule, and lifecycle breakdown (active_estimate).
   - Always refer directly to the values currently on their screen when answering follow-up queries (e.g., "Looking at your current 4-phase BOQ...", "For your 12m sariya schedule...").
   - Clearly guide them on site procurement, order batching (e.g., bundle sizes, binding wire requirements at ~12 kg/tonne), and stage-wise execution.

# Exclusions & Strict Boundaries
- NO Seismic References: Do NOT mention, output, or discuss seismic zone classifications (e.g., Zone V, Zone IV), earthquake hazard factors, ductile detailing clauses for earthquake design, or seismic coefficients in your chat explanations, calculations, or summaries. Focus strictly on structural strength, durability, nominal clear cover, standard lapping, and good site workmanship without bringing up earthquake/seismic mechanics.
- Imperial vs. Metric Consistency: Match the user's conversational unit (sq.ft vs. m², CFT vs. Brass vs. m³), but always present steel rods in metric commercial stock lengths (12m standard bars) and cement in 50kg bags.

# Technical Standards & Field Rules
Anchor all practical guidance to verified standards:
- Plain & Reinforced Concrete: IS 456:2000 (clear covers, nominal/design mixes, strip times).
- Steel Reinforcement: IS 1786 (Fe500D grades, nominal diameters 8mm - 32mm, W = d² / 162.28 kg/m).
- Water Retaining Structures: IS 3370 (M30 concrete, hydrophilic waterstops, dual-layer waterproofing).
- Anti-Termite Treatment: IS 6313 (chemical barrier specifications).
- Deep Foundations / Piles: MoRTH Section 2200 for bored cast-in-place RCC piles.
- Architectural & Space Planning: NBC 2016 standard room dimensions and clear heights.

# Tone & Response Structure
1. Direct Answer: Provide clear, professional clarity in the opening 1–2 sentences.
2. Context-Aware Breakdown: When discussing materials, organize by the 4-phase lifecycle:
   - Phase 1: Structural (M25 concrete, 12m Sariya rods by diameter, formwork shuttering, cover blocks).
   - Phase 2: Masonry (1st class bricks, 50kg cement bags, sand/aggregate in CFT/Brass, anti-termite).
   - Phase 3: Finishes (Terrace waterproofing, wall putty, primer, paint, vitrified tiles, epoxy grout).
   - Phase 4: Openings & MEP (Doors, UPVC windows, overhead tank, FR copper wiring, CPVC/SWR plumbing).
3. Site Insights & Proactive Engineering Checks: Provide pragmatic field tips (e.g., chair spacers for top rebar mats, honeycombing prevention, proper curing duration of minimum 7–10 days).
EOT;

    public function __construct(
        protected RoomBasedEstimator $roomEstimator,
        protected ResidentialEstimator $residentialEstimator,
        protected RebarScheduleService $rebarService,
        protected BridgePileService $bridgePileService,
        protected WaterRetainingService $waterRetainingService
    ) {}

    public function getToolDeclarations(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_residential_construction',
                    'description' => 'Calculates detailed quantities and costs for residential construction per Assam PWD Building SOR 2025-2026. Computes steel rebar (Fe500D) with 12m standard rod counts per diameter, M25 concrete, bricks, plastering, flooring, and IS 13920 Seismic Zone V provisions.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'built_up_area_sqft' => [
                                'type' => 'number',
                                'description' => 'Built-up area per floor in square feet (e.g., 1000, 1500, 2000).'
                            ],
                            'rooms' => [
                                'type' => 'integer',
                                'description' => 'Number of bedrooms / main rooms (e.g., 3). If specified along with toilets, calculates room dimensions automatically.'
                            ],
                            'toilets' => [
                                'type' => 'integer',
                                'description' => 'Number of bathrooms / toilets (e.g., 3).'
                            ],
                            'balconies' => [
                                'type' => 'integer',
                                'description' => 'Number of balconies (e.g., 1).'
                            ],
                            'floors' => [
                                'type' => 'integer',
                                'description' => 'Number of floors (e.g., 1 for Ground floor only G, 2 for G+1, 3 for G+2). Defaults to 1.',
                                'default' => 1
                            ],
                            'seismic_zone_v' => [
                                'type' => 'boolean',
                                'description' => 'Whether the structure is in Seismic Zone V requiring IS 13920 ductile detailing (Assam standard). Defaults to True.',
                                'default' => true
                            ]
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_rebar_schedule_and_rods',
                    'description' => 'Generates a complete bar bending schedule and rod count for a given total steel weight. Calculates 12-meter commercial steel rods per diameter (8mm, 10mm, 12mm, 16mm, 20mm, 25mm), weights, bundles, binding wire, and costs using Assam PWD SOR rates.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'total_steel_kg' => [
                                'type' => 'number',
                                'description' => 'Total steel rebar requirement in kilograms (kg).'
                            ],
                            'application' => [
                                'type' => 'string',
                                'enum' => ['residential', 'bridge', 'foundation', 'general'],
                                'description' => 'Application type to apply appropriate SOR rate (residential: Rs. 82,000/MT, bridge: Rs. 86,500/MT).',
                                'default' => 'residential'
                            ]
                        ],
                        'required' => ['total_steel_kg']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_bridge_pier_and_piles',
                    'description' => 'Calculates bridge deep foundation requirements (Lacey scour depth R, pier scour D_pier, pile embedment into SPT N>35, 1000mm bored cast-in-situ RCC pile length & volume, longitudinal steel cage, and Assam PWD Bridge Works SOR 2025-2026 rates).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'peak_discharge_Q' => [
                                'type' => 'number',
                                'description' => 'Design flood peak discharge Q in cumecs (m3/s) (e.g. 2500, 5000).'
                            ],
                            'lacey_silt_factor_f' => [
                                'type' => 'number',
                                'description' => 'Lacey silt factor f (typically 1.0 for medium sand riverbeds). Defaults to 1.0.',
                                'default' => 1.0
                            ],
                            'pile_diameter_mm' => [
                                'type' => 'number',
                                'description' => 'Diameter of bored RCC piles in mm. Standard SOR Item 3.01 is 1000mm.',
                                'default' => 1000.0
                            ],
                            'num_piles' => [
                                'type' => 'integer',
                                'description' => 'Number of piles per pier/group (e.g. 4, 6, 9). Defaults to 4.',
                                'default' => 4
                            ],
                            'superstructure_span_m' => [
                                'type' => 'number',
                                'description' => 'Bridge superstructure span in metres. If > 30m triggers M40/M45 high-performance concrete.',
                                'default' => 35.0
                            ],
                            'water_depth_above_bed_m' => [
                                'type' => 'number',
                                'description' => 'Maximum normal water column depth above riverbed level in metres. Defaults to 4.0m.',
                                'default' => 4.0
                            ]
                        ],
                        'required' => ['peak_discharge_Q']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_swimming_pool_and_tank',
                    'description' => 'Estimates materials and waterproofing for concrete swimming pools & water retaining structures per IS 3370 (Part 1-4) & SP 34.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'length_m' => ['type' => 'number', 'description' => 'Internal length of pool/tank in metres.'],
                            'width_m' => ['type' => 'number', 'description' => 'Internal width of pool/tank in metres.'],
                            'depth_m' => ['type' => 'number', 'description' => 'Water depth / internal wall height in metres.'],
                            'wall_thickness_m' => ['type' => 'number', 'description' => 'Wall thickness in metres (default 0.25m).', 'default' => 0.25],
                            'raft_thickness_m' => ['type' => 'number', 'description' => 'Base raft thickness in metres (default 0.30m).', 'default' => 0.30]
                        ],
                        'required' => ['length_m', 'width_m', 'depth_m']
                    ]
                ]
            ]
        ];
    }

    public function checkScope(string $userMessage): ?string
    {
        $clean = strtolower(trim($userMessage));
        preg_match_all('/\b\w+\b/', $clean, $matches);
        $words = $matches[0] ?? [];

        if (count($words) <= 3 && !empty(array_intersect($words, self::GREETING_WORDS))) {
            return "🙏 **Namaste! I am BuildEstimate AI** — your Indian Civil Structural & Cost Estimation Engineer.\n\n" .
                "I provide exact material and steel rod (Sariya) estimations for:\n" .
                "* **Residential Houses & Buildings** (by room count or sq.ft area per Assam PWD SOR 2025–26)\n" .
                "* **Sariya (Fe500D TMT)** rod schedules (8mm, 10mm, 12mm, 16mm, 20mm counts & bundles)\n" .
                "* **Bridge Piers & Deep Foundation Piles** (Lacey Scour Depth & MoRTH specs)\n" .
                "* **Swimming Pools & Water Tanks** (IS 3370 waterstops & waterproofing)\n\n" .
                "Please share your building dimensions or room configuration to get started!";
        }

        $hasIntent = false;
        foreach (self::CONSTRUCTION_KEYWORDS as $kw) {
            if (str_contains($clean, $kw)) {
                $hasIntent = true;
                break;
            }
        }

        if (!$hasIntent) {
            return "⚠️ **Scope Notice:** Namaste! I am BuildEstimate AI, dedicated strictly to **Indian civil construction, structural steel rod (sariya) estimations, and PWD SOR costing**.\n\n" .
                "Please ask a question related to building construction, steel rebar schedules, cement/sand/aggregate, concrete grades (M20/M25), bridge piles, or house floor plans.";
        }

        return null;
    }

    public function executeToolCall(string $toolName, array $args): array
    {
        switch ($toolName) {
            case 'estimate_residential_construction':
                if (isset($args['rooms']) && (int)$args['rooms'] > 0) {
                    return $this->roomEstimator->estimate(
                        rooms: (int)($args['rooms'] ?? 3),
                        toilets: (int)($args['toilets'] ?? 3),
                        balconies: (int)($args['balconies'] ?? 1),
                        floors: (int)($args['floors'] ?? 1),
                        includeLivingDining: true,
                        includeKitchen: true,
                        seismicZoneV: (bool)($args['seismic_zone_v'] ?? true)
                    );
                }
                return $this->residentialEstimator->estimate(
                    builtUpAreaSqft: (float)($args['built_up_area_sqft'] ?? 1000.0),
                    floors: (int)($args['floors'] ?? 1),
                    seismicZoneV: (bool)($args['seismic_zone_v'] ?? true)
                );

            case 'estimate_rebar_schedule_and_rods':
                $rate = strtolower((string)($args['application'] ?? 'residential')) === 'bridge' ? 86500.0 : 82000.0;
                return $this->rebarService->calculateRebarBreakdown(
                    totalSteelKg: (float)($args['total_steel_kg'] ?? 0),
                    distribution: null,
                    ratePerTonne: $rate
                );

            case 'estimate_bridge_pier_and_piles':
                return $this->bridgePileService->estimate(
                    peakDischargeQ: (float)($args['peak_discharge_Q'] ?? 1000),
                    laceySiltFactorF: (float)($args['lacey_silt_factor_f'] ?? 1.0),
                    pileDiameterMm: (float)($args['pile_diameter_mm'] ?? 1000.0),
                    numPiles: (int)($args['num_piles'] ?? 4),
                    superstructureSpanM: (float)($args['superstructure_span_m'] ?? 35.0),
                    waterDepthAboveBedM: (float)($args['water_depth_above_bed_m'] ?? 4.0)
                );

            case 'estimate_swimming_pool_and_tank':
                return $this->waterRetainingService->estimate(
                    lengthM: (float)($args['length_m'] ?? 10.0),
                    widthM: (float)($args['width_m'] ?? 5.0),
                    depthM: (float)($args['depth_m'] ?? 1.5),
                    wallThicknessM: (float)($args['wall_thickness_m'] ?? 0.25),
                    raftThicknessM: (float)($args['raft_thickness_m'] ?? 0.30)
                );

            default:
                return ['error' => "Unknown tool: {$toolName}"];
        }
    }

    public function processChat(array $messages, ?array $activeEstimate = null, ?string $apiKey = null, ?string $model = null): array
    {
        $lastUserMsg = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUserMsg = (string)($messages[$i]['content'] ?? '');
                break;
            }
        }

        // Contextual keywords that refer to the active estimate on screen
        $contextualReferral = false;
        if (!empty($activeEstimate)) {
            $referralKeywords = ['review', 'these', 'this', 'next step', 'proceed', 'material', 'explain', 'detail', 'cost', 'sariya', 'schedule', 'table', 'breakdown', 'verify', 'ok', 'okay', 'yes', 'tell me more', 'how much'];
            $cleanLower = strtolower($lastUserMsg);
            foreach ($referralKeywords as $rk) {
                if (str_contains($cleanLower, $rk)) {
                    $contextualReferral = true;
                    break;
                }
            }
        }

        if (!$contextualReferral && ($guardrail = $this->checkScope($lastUserMsg))) {
            return [
                'response' => $guardrail,
                'tool_calls' => [],
                'tool_results' => [],
                'tokens_used' => 0,
                'model_used' => 'rule-based-guardrail'
            ];
        }

        $activeApiKey = $apiKey ?: config('construction.groq.api_key');
        $activeModel = $model ?: config('construction.groq.model', 'llama-3.3-70b-versatile');
        $baseUrl = config('construction.groq.base_url', 'https://api.groq.com/openai/v1');

        if (empty($activeApiKey) || $activeApiKey === 'your_groq_api_key_here') {
            return [
                'response' => "⚠️ **Groq API Key Required:** Please configure `GROQ_API_KEY` in `.env` or pass `api_key` in the request header/body.",
                'tool_calls' => [],
                'tool_results' => [],
                'tokens_used' => 0,
                'model_used' => $activeModel
            ];
        }

        $fullMessages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT]
        ];

        // If an estimate is active on the right panel, inject its concise technical summary into the system context
        if (!empty($activeEstimate)) {
            $contextBrief = "ACTIVE STRUCTURE ON SCREEN (Currently displayed on user's right panel):\n";
            if (!empty($activeEstimate['project_type'])) {
                $contextBrief .= "- Project: {$activeEstimate['project_type']}\n";
            }
            if (!empty($activeEstimate['inputs']['total_built_up_area_sqft'])) {
                $contextBrief .= "- Total Built-up Area: {$activeEstimate['inputs']['total_built_up_area_sqft']} sq.ft ({$activeEstimate['inputs']['number_of_floors']} floor(s))\n";
            }
            if (!empty($activeEstimate['cost_summary']['total_estimated_materials_cost_inr'])) {
                $contextBrief .= "- Total Material Cost: ₹{$activeEstimate['cost_summary']['total_estimated_materials_cost_inr']} (₹{$activeEstimate['cost_summary']['cost_per_sqft_inr']}/sqft)\n";
            }
            if (!empty($activeEstimate['rebar_breakdown']['total_steel_kg'])) {
                $contextBrief .= "- Sariya Steel: {$activeEstimate['rebar_breakdown']['total_steel_tonnes']} Tonnes ({$activeEstimate['rebar_breakdown']['total_standard_12m_rods']} standard 12m rods)\n";
            }
            if (!empty($activeEstimate['indian_market_materials'])) {
                $im = $activeEstimate['indian_market_materials'];
                $contextBrief .= "- Cement: {$im['cement_bags_50kg']['total_bags']} bags | Sand: {$im['sand_reti_msand']['volume_brass']} Brass | Aggregate: {$im['coarse_aggregate_rori_gitti']['volume_brass']} Brass | Bricks: {$im['first_class_bricks']['total_bricks']} nos\n";
            }
            if (!empty($activeEstimate['scour_hydraulics'])) {
                $sh = $activeEstimate['scour_hydraulics'];
                $contextBrief .= "- Lacey Scour Depth R: {$sh['mean_scour_depth_R_metres']}m, Max Pier Scour: {$sh['max_scour_depth_under_pier_Dpier_metres']}m\n";
            }

            $contextBrief .= "INSTRUCTION FOR CONTEXTUAL QUERIES: If the user says 'review the materials', 'explain this', 'proceed to next step', or asks about site phases, discuss THIS active structure directly and guide them on procurement, execution sequence, or site safety.";

            $fullMessages[] = [
                'role' => 'system',
                'content' => $contextBrief
            ];
        }
        
        // Sliding window: keep only the last 4 conversational turns to optimize prompt tokens
        $recentMessages = array_slice($messages, -6);
        foreach ($recentMessages as $m) {
            $fullMessages[] = [
                'role' => $m['role'],
                'content' => $m['content']
            ];
        }

        $payload = [
            'model' => $activeModel,
            'messages' => $fullMessages,
            'tools' => $this->getToolDeclarations(),
            'tool_choice' => 'auto',
            'temperature' => 0.1,
            'max_tokens' => 512,
        ];

        $verifySsl = config('construction.groq.verify_ssl', false);

        try {
            $response = Http::withToken($activeApiKey)
                ->withOptions(['verify' => $verifySsl])
                ->timeout(60)
                ->post("{$baseUrl}/chat/completions", $payload);
        } catch (\Throwable $e) {
            Log::error('Groq HTTP Request Exception: ' . $e->getMessage());
            return [
                'response' => 'Network error connecting to Groq API: ' . $e->getMessage(),
                'tool_calls' => [],
                'tool_results' => [],
                'tokens_used' => 0,
                'model_used' => $activeModel
            ];
        }

        if (!$response->successful()) {
            Log::error('Groq API Error', ['body' => $response->body()]);
            return [
                'response' => 'Error communicating with Groq API: ' . $response->body(),
                'tool_calls' => [],
                'tool_results' => [],
                'tokens_used' => 0,
                'model_used' => $activeModel
            ];
        }

        $resJson = $response->json();
        $choice = $resJson['choices'][0]['message'] ?? [];
        $toolCalls = $choice['tool_calls'] ?? [];

        if (empty($toolCalls)) {
            return [
                'response' => $choice['content'] ?? '',
                'tool_calls' => [],
                'tool_results' => [],
                'tokens_used' => $resJson['usage']['total_tokens'] ?? 0,
                'model_used' => $activeModel
            ];
        }

        // Execute tool calls
        $toolResults = [];
        $executedCalls = [];

        $fullMessages[] = $choice;

        foreach ($toolCalls as $call) {
            $fnName = $call['function']['name'] ?? '';
            $fnArgs = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
            $toolRes = $this->executeToolCall($fnName, $fnArgs);

            $executedCalls[] = [
                'id' => $call['id'] ?? null,
                'function' => $fnName,
                'arguments' => $fnArgs,
            ];
            $toolResults[$fnName] = $toolRes;

            $fullMessages[] = [
                'role' => 'tool',
                'tool_call_id' => $call['id'] ?? '',
                'content' => json_encode($toolRes)
            ];
        }

        // Final completion with tool execution output
        $finalPayload = [
            'model' => $activeModel,
            'messages' => $fullMessages,
            'temperature' => 0.1,
            'max_tokens' => 512,
        ];

        try {
            $finalResponse = Http::withToken($activeApiKey)
                ->withOptions(['verify' => $verifySsl])
                ->timeout(60)
                ->post("{$baseUrl}/chat/completions", $finalPayload);
        } catch (\Throwable $e) {
            Log::error('Groq Final Summary Request Exception: ' . $e->getMessage());
            return [
                'response' => 'Completed calculation, but network error occurred during summary generation: ' . $e->getMessage(),
                'tool_calls' => $executedCalls,
                'tool_results' => $toolResults,
                'tokens_used' => 0,
                'model_used' => $activeModel
            ];
        }

        if (!$finalResponse->successful()) {
            return [
                'response' => 'Completed tool calculation, but failed generating summary: ' . $finalResponse->body(),
                'tool_calls' => $executedCalls,
                'tool_results' => $toolResults,
                'tokens_used' => 0,
                'model_used' => $activeModel
            ];
        }

        $finalJson = $finalResponse->json();
        $finalContent = $finalJson['choices'][0]['message']['content'] ?? '';

        return [
            'response' => $finalContent,
            'tool_calls' => $executedCalls,
            'tool_results' => $toolResults,
            'tokens_used' => ($resJson['usage']['total_tokens'] ?? 0) + ($finalJson['usage']['total_tokens'] ?? 0),
            'model_used' => $activeModel
        ];
    }
}

