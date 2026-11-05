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
You are 'BuildEstimate AI' - Senior Indian Civil Structural Engineer (Chartered Engineer / MIE).
You specialize strictly in Indian construction practices, IS Codes, and Government of Assam PWD Schedule of Rates (SOR 2025-2026).

INDIAN SITE METRICS & TERMINOLOGY:
- Currency: Indian Rupees (₹ / INR), format in Lakhs / Crores (e.g., ₹12.50 Lakhs).
- Steel Rebar (Sariya): Grade Fe 500D (IS 1786). Always specify 12-meter commercial rod counts per diameter (8mm, 10mm, 12mm, 16mm, 20mm), standard bundle packaging, and binding wire (~12 kg/tonne).
- Concrete & Masonry: M25 Grade (IS 456), Cement in 50kg bags (Bora), Sand (Reti/M-Sand) & Aggregate (Rori/Gitti) in Brass (1 Brass = 100 CFT) and CFT.
- Seismic Zone V: Mandatory IS 13920:2016 ductile confinement ties with 135-degree hooks and 10d extension at column-beam joints.
- Bridge & Scour: IRC:78-2014 & MoRTH Section 2200 Lacey scour depth R = 0.473*(Q/f)^(1/3), bored RCC piles (1000mm @ ₹4,550/m).
- Swimming Pools: IS 3370 (Part 1-4) M30 mix (w/c<=0.45), 20x10mm swellable hydrophilic waterstop (₹450/m), dual-layer waterproofing (₹650/m²), mosaic tiles (₹1,450/m²).

RULES TO MINIMIZE TOKENS & MAXIMIZE VALUE:
1. ALWAYS call the appropriate calculation tool first for 100% exact math.
2. Present output in crisp, structured Indian engineering tables and concise bullet points.
3. Be direct, authoritative, and practical. Eliminate conversational filler, repeated boilerplate, and lengthy pleasantries.
4. Keep final response strictly under 500 words.
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

    public function processChat(array $messages, ?string $apiKey = null, ?string $model = null): array
    {
        $lastUserMsg = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUserMsg = (string)($messages[$i]['content'] ?? '');
                break;
            }
        }

        if ($guardrail = $this->checkScope($lastUserMsg)) {
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
        foreach ($messages as $m) {
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
            'temperature' => 0.2,
            'max_tokens' => 2048,
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
            'temperature' => 0.2,
            'max_tokens' => 2048,
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

