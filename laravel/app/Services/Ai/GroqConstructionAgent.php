<?php

namespace App\Services\Ai;

use App\Services\Estimation\BoundaryWallEstimator;
use App\Services\Estimation\BridgePileService;
use App\Services\Estimation\CommercialBuildingEstimator;
use App\Services\Estimation\RebarScheduleService;
use App\Services\Estimation\ResidentialEstimator;
use App\Services\Estimation\RoadAndDrainageEstimator;
use App\Services\Estimation\RoomBasedEstimator;
use App\Services\Estimation\WarehousePebEstimator;
use App\Services\Estimation\WaterRetainingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqConstructionAgent
{
    protected const CONSTRUCTION_KEYWORDS = [
        'house',
        'building',
        'home',
        'ghar',
        'makaan',
        'room',
        'kamra',
        'bhk',
        'bedroom',
        'toilet',
        'bathroom',
        'balcony',
        'kitchen',
        'flat',
        'plot',
        'steel',
        'sariya',
        'rebar',
        'rod',
        'tmt',
        'fe500',
        'fe500d',
        'fe550',
        'cement',
        'bora',
        'bag',
        'concrete',
        'rcc',
        'm20',
        'm25',
        'm30',
        'm35',
        'm40',
        'm45',
        'brick',
        'eent',
        'plaster',
        'plastering',
        'flooring',
        'tile',
        'slab',
        'chhat',
        'dhalai',
        'beam',
        'column',
        'pillar',
        'footing',
        'plinth',
        'lintel',
        'foundation',
        'bored',
        'pile',
        'bridge',
        'pier',
        'scour',
        'lacey',
        'pool',
        'tank',
        'swimming',
        'waterstop',
        'waterproof',
        'waterproofing',
        'cost',
        'estimate',
        'estimation',
        'budget',
        'rate',
        'sor',
        'pwd',
        'cpwd',
        'is 456',
        'is 13920',
        'is 1786',
        'is 3370',
        'morth',
        'irc',
        'assam',
        'guwahati',
        'kamrup',
        'silchar',
        'dibrugarh',
        'jorhat',
        'sqft',
        'sq ft',
        'square feet',
        'sqm',
        'gaj',
        'brass',
        'cft',
        'tonne',
        'ton',
        'quintal',
        'kg',
        'shuttering',
        'centering',
        'stirrup',
        'tie',
        'hook',
        'seismic',
        'boundary',
        'wall',
        'boundary wall',
        'fencing',
        'concertina',
        'barbed wire',
        'coping',
        'commercial',
        'office',
        'mall',
        'retail',
        'basement',
        'fire tank',
        'fire reserve',
        'multi-storey',
        'peb',
        'warehouse',
        'shed',
        'purlin',
        'girt',
        'rafter',
        'cladding',
        'galvalume',
        'vdf',
        'pedestal',
        'road',
        'highway',
        'pavement',
        'drain',
        'storm drain',
        'gsb',
        'wmm',
        'dbm',
        'pqc',
        'dlc',
        'subgrade'
    ];

    protected const GREETING_WORDS = ['hi', 'hello', 'hey', 'namaste', 'pranam', 'good morning', 'good evening', 'good afternoon'];

    public const SYSTEM_PROMPT = <<<EOT
# Role & Identity
You are the Lead Site Engineering Advisor & Technical Consultant for **BuildEstimate AI**. You operate alongside deterministic PHP engineering calculation engines to assist contractors, civil engineers, and builders with structural quantities, material procurement, field execution, and IS/NBC compliance.

# Core Architecture & Strict Zero-Math Protocol
1. Never Calculate Critical Quantities Manually:
   - All steel tonnage, 12m commercial rod counts (TMT steel rods per diameter), cement bags, brass/CFT aggregates, brick counts, and foundation scour depths are computed exclusively by backend PHP services.
   - If the user asks for an estimate, bill of quantities (BOQ), or steel breakdown, identify their parameters (built-up area, room counts, foundation type, soil/water parameters) and invoke the relevant estimation tool.
   - Do NOT invent or approximate numbers or formulas on the fly. The deterministic backend supplies the ground truth.

2. Injected Estimate Synchronization (active_estimate):
   - The user's screen displays a live BOQ, commercial rod schedule, and lifecycle breakdown (active_estimate).
   - Always refer directly to the values currently on their screen when answering follow-up queries (e.g., "Looking at your current 4-phase BOQ...", "For your 12m TMT steel rods schedule...").
   - Clearly guide them on site procurement, order batching (e.g., bundle sizes, binding wire requirements at ~12 kg/tonne), and stage-wise execution.

# Exclusions & Strict Boundaries
- NO Seismic References: Do NOT mention, output, or discuss seismic zone classifications (e.g., Zone V, Zone IV), earthquake hazard factors, ductile detailing clauses for earthquake design, or seismic coefficients in your chat explanations, calculations, or summaries. Focus strictly on structural strength, durability, nominal clear cover, standard lapping, and good site workmanship without bringing up earthquake/seismic mechanics.
- Imperial vs. Metric Consistency: Match the user's conversational unit (sq.ft vs. sq.m, CFT vs. Brass vs. cu.m), but always present steel rods in metric commercial stock lengths (12m standard bars) and cement in 50kg bags.

# Technical Standards & Field Rules
Anchor all practical guidance to verified standards:
- Plain & Reinforced Concrete: IS 456:2000 (clear covers, nominal/design mixes, strip times).
- Steel Reinforcement: IS 1786 (Fe500D grades, nominal diameters 8mm - 32mm, W = d^2 / 162.28 kg/m).
- Water Retaining Structures: IS 3370 (M30 concrete, hydrophilic waterstops, dual-layer waterproofing).
- Anti-Termite Treatment: IS 6313 (chemical barrier specifications).
- Deep Foundations / Piles: MoRTH Section 2200 for bored cast-in-place RCC piles.
- Architectural & Space Planning: NBC 2016 standard room dimensions and clear heights.

# Tone & Response Structure (Strict 3-Section Format)
To ensure maximum token efficiency and rapid readability on site, deliver responses strictly structured in three concise sections:
1. **Executive Cost & Material Summary**: 2–3 sentences stating the project scope, total estimated material cost in INR, cost per sq.ft / unit, and core structural grades (e.g. M25/M30 concrete).
2. **Key Quantities & TMT Steel Rod Schedule**: Concise bullet points highlighting:
   - Fe500D TMT Steel Rods (Total tonnes, commercial 12m bar counts by diameter, binding wire).
   - Major bulk materials (Cement 50kg bags, Sand in Brass/CFT, Coarse Aggregate in Brass/CFT, Bricks count).
   - Point user to the live side panel for the full itemized 4-phase BOQ breakdown.
3. **Site Precautions & Workmanship Rules**: 2–3 bullet points on essential execution standards (e.g., minimum clear cover per IS 456, vibrator compaction, 7–10 days water curing, or joint detailing).
Do NOT output repetitive boilerplate or replicate full multi-page tables already displayed on the user's dashboard screen. Keep explanations punchy and practical.
EOT;

    public function __construct(
        protected RoomBasedEstimator $roomEstimator,
        protected ResidentialEstimator $residentialEstimator,
        protected RebarScheduleService $rebarService,
        protected BridgePileService $bridgePileService,
        protected WaterRetainingService $waterRetainingService,
        protected BoundaryWallEstimator $boundaryWallEstimator,
        protected CommercialBuildingEstimator $commercialEstimator,
        protected WarehousePebEstimator $warehousePebEstimator,
        protected RoadAndDrainageEstimator $roadAndDrainageEstimator
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
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_boundary_wall',
                    'description' => 'Estimates earthwork, PCC, brickwork, RCC tie pillars/coping, 12m TMT steel rods, and security fencing for perimeter boundary walls per Assam PWD SOR and IS 1905 / IS 456.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'length_m' => ['type' => 'number', 'description' => 'Perimeter length of boundary wall in metres.'],
                            'height_m' => ['type' => 'number', 'description' => 'Height of boundary wall above ground in metres (default 2.0m).', 'default' => 2.0],
                            'wall_thickness_mm' => ['type' => 'number', 'description' => 'Brick wall thickness in mm: 115 (4.5 inch) or 230 (9 inch). Default 230mm.', 'default' => 230.0],
                            'pillar_spacing_m' => ['type' => 'number', 'description' => 'Spacing of RCC tie pillars in metres (default 3.0m).', 'default' => 3.0],
                            'foundation_depth_m' => ['type' => 'number', 'description' => 'Foundation trench depth in metres (default 0.9m).', 'default' => 0.9],
                            'fencing_type' => [
                                'type' => 'string',
                                'enum' => ['concertina_razor_coil', 'barbed_wire_3strand', 'none'],
                                'description' => 'Type of top security fencing. Defaults to concertina_razor_coil.',
                                'default' => 'concertina_razor_coil'
                            ]
                        ],
                        'required' => ['length_m']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_commercial_building',
                    'description' => 'Calculates detailed material quantities, Fe500D steel schedule, M30 concrete, and NBC 2016 Part 4 static fire-water storage for multi-storey commercial complexes per Assam PWD SOR.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'built_up_area_sqft' => ['type' => 'number', 'description' => 'Built-up area per floor in square feet (e.g., 2500, 5000, 10000).'],
                            'floors' => ['type' => 'integer', 'description' => 'Number of above-ground storeys (default 3).', 'default' => 3],
                            'has_basement' => ['type' => 'boolean', 'description' => 'Whether building includes an underground basement level (default false).', 'default' => false],
                            'occupancy_type' => [
                                'type' => 'string',
                                'enum' => ['office', 'retail_mall', 'hotel', 'hospital', 'mixed_use'],
                                'description' => 'Occupancy category per NBC 2016. Defaults to office.',
                                'default' => 'office'
                            ]
                        ],
                        'required' => ['built_up_area_sqft']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_warehouse_peb',
                    'description' => 'Estimates primary/secondary structural steel tonnage (IS 800/IS 801), AZ150 galvalume sheeting (IS 14246), RCC stub pedestals, brick dado walls, and industrial VDF/FM2 floor slabs for Pre-Engineered Steel Buildings (PEB).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'length_m' => ['type' => 'number', 'description' => 'Clear length of PEB warehouse shed in metres.'],
                            'width_m' => ['type' => 'number', 'description' => 'Clear span width of PEB warehouse shed in metres.'],
                            'eave_height_m' => ['type' => 'number', 'description' => 'Eave height in metres (default 7.0m).', 'default' => 7.0],
                            'floor_load_grade' => [
                                'type' => 'string',
                                'enum' => ['medium_duty', 'heavy_duty', 'light_duty'],
                                'description' => 'Flooring grade for VDF industrial slab. Defaults to medium_duty.',
                                'default' => 'medium_duty'
                            ],
                            'bay_spacing_m' => ['type' => 'number', 'description' => 'Bay spacing between portal frames in metres (default 6.0m).', 'default' => 6.0],
                            'dado_wall_height_m' => ['type' => 'number', 'description' => 'Perimeter brick dado wall height in metres (default 2.1m).', 'default' => 2.1]
                        ],
                        'required' => ['length_m', 'width_m']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'estimate_road_and_drainage',
                    'description' => 'Estimates highway pavement layers (flexible DBM+BC per IRC:37 or rigid PQC M40+DLC per IRC:58), subgrade earthwork compaction, and roadside RCC stormwater drains with precast covers (CPHEEO Manual) per Assam PWRD SOR.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'length_m' => ['type' => 'number', 'description' => 'Length of roadway in linear metres.'],
                            'carriageway_width_m' => ['type' => 'number', 'description' => 'Carriageway width in metres (e.g. 3.75m single lane, 7.0m 2-lane, 14m 4-lane). Default 7.0m.', 'default' => 7.0],
                            'pavement_type' => [
                                'type' => 'string',
                                'enum' => ['flexible', 'rigid'],
                                'description' => 'Pavement category: flexible (GSB+WMM+DBM+BC) or rigid (DLC+PQC M40). Default flexible.',
                                'default' => 'flexible'
                            ],
                            'drain_type' => [
                                'type' => 'string',
                                'enum' => ['rcc_covered_both_sides', 'rcc_covered_single_side', 'none'],
                                'description' => 'Stormwater drainage configuration. Defaults to rcc_covered_both_sides.',
                                'default' => 'rcc_covered_both_sides'
                            ],
                            'shoulder_width_m' => ['type' => 'number', 'description' => 'Shoulder width on each side in metres (default 1.5m).', 'default' => 1.5]
                        ],
                        'required' => ['length_m']
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
            return "🙏 **Namaste! I am BuildEstimate AI** - your Indian Civil Structural & Cost Estimation Engineer.\n\n" .
                "I provide exact material and steel rod (TMT steel rods) estimations for:\n" .
                "* **Residential Houses & Buildings** (by room count or sq.ft area per Assam PWD SOR 2025-26)\n" .
                "* **TMT steel rods (Fe500D)** schedules (8mm, 10mm, 12mm, 16mm, 20mm counts & bundles)\n" .
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
            return "Note: Namaste! I am BuildEstimate AI, dedicated strictly to **Indian civil construction, structural steel rod (TMT steel rods) estimations, and PWD SOR costing**.\n\n" .
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

            case 'estimate_boundary_wall':
                return $this->boundaryWallEstimator->estimate(
                    lengthM: (float)($args['length_m'] ?? 100.0),
                    heightM: (float)($args['height_m'] ?? 2.0),
                    wallThicknessMm: (float)($args['wall_thickness_mm'] ?? 230.0),
                    pillarSpacingM: (float)($args['pillar_spacing_m'] ?? 3.0),
                    foundationDepthM: (float)($args['foundation_depth_m'] ?? 0.9),
                    fencingType: (string)($args['fencing_type'] ?? 'concertina_razor_coil')
                );

            case 'estimate_commercial_building':
                return $this->commercialEstimator->estimate(
                    builtUpAreaSqft: (float)($args['built_up_area_sqft'] ?? 3000.0),
                    floors: (int)($args['floors'] ?? 3),
                    hasBasement: (bool)($args['has_basement'] ?? false),
                    occupancyType: (string)($args['occupancy_type'] ?? 'office')
                );

            case 'estimate_warehouse_peb':
                return $this->warehousePebEstimator->estimate(
                    lengthM: (float)($args['length_m'] ?? 40.0),
                    widthM: (float)($args['width_m'] ?? 20.0),
                    eaveHeightM: (float)($args['eave_height_m'] ?? 7.0),
                    floorLoadGrade: (string)($args['floor_load_grade'] ?? 'medium_duty'),
                    baySpacingM: (float)($args['bay_spacing_m'] ?? 6.0),
                    dadoWallHeightM: (float)($args['dado_wall_height_m'] ?? 2.1)
                );

            case 'estimate_road_and_drainage':
                return $this->roadAndDrainageEstimator->estimate(
                    lengthM: (float)($args['length_m'] ?? 500.0),
                    carriagewayWidthM: (float)($args['carriageway_width_m'] ?? 7.0),
                    pavementType: (string)($args['pavement_type'] ?? 'flexible'),
                    drainType: (string)($args['drain_type'] ?? 'rcc_covered_both_sides'),
                    shoulderWidthM: (float)($args['shoulder_width_m'] ?? 1.5)
                );

            default:
                return ['error' => "Unknown tool: {$toolName}"];
        }
    }

    public function processChat(array $messages, ?array $activeEstimate = null, ?string $apiKey = null, ?string $model = null, ?string $retrievedContext = null): array
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
                'response' => "Groq API Key Required: Please configure `GROQ_API_KEY` in `.env` or pass `api_key` in the request header/body.",
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
                $contextBrief .= "- Total Material Cost: INR {$activeEstimate['cost_summary']['total_estimated_materials_cost_inr']} (INR {$activeEstimate['cost_summary']['cost_per_sqft_inr']}/sqft)\n";
            }
            if (!empty($activeEstimate['rebar_breakdown']['total_steel_kg'])) {
                $contextBrief .= "- TMT Steel Rods: {$activeEstimate['rebar_breakdown']['total_steel_tonnes']} Tonnes ({$activeEstimate['rebar_breakdown']['total_standard_12m_rods']} standard 12m rods)\n";
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

        if (!empty($retrievedContext)) {
            $fullMessages[] = [
                'role' => 'system',
                'content' => "# RETRIEVED CONTEXT / KNOWLEDGE BASE:\n" . trim($retrievedContext)
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
            'max_tokens' => 1800,
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

            // Sanitize and compress tool output serialized to LLM:
            // 1. Strip seismic references to maintain negative constraint consistency
            // 2. Extract only critical engineering facts and totals to reduce token footprint by ~60%
            $llmToolRes = $this->compressToolResultForLlm($toolRes);

            $fullMessages[] = [
                'role' => 'tool',
                'tool_call_id' => $call['id'] ?? '',
                'content' => json_encode($llmToolRes)
            ];
        }

        // Final completion with tool execution output
        $finalPayload = [
            'model' => $activeModel,
            'messages' => $fullMessages,
            'temperature' => 0.1,
            'max_tokens' => 850,
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

    /**
     * Compress full estimation tool payload for LLM consumption.
     * Strips nested itemized arrays, UI boilerplate, and long notes while preserving
     * exact engineering metrics (costs, TMT 12m rods, cement bags, CFT/Brass quantities).
     */
    protected function compressToolResultForLlm(array $result): array
    {
        if (isset($result['error'])) {
            return $result;
        }

        // Always strip negative constraint keys
        unset($result['seismic_safety_provisions'], $result['seismic_and_bearing_provisions']);

        $compressed = [
            'project_type' => $result['project_type'] ?? 'Civil Construction Estimation',
            'scope' => $result['scope'] ?? ($result['project_type'] ?? ''),
            'inputs' => $result['inputs'] ?? [],
        ];

        // 1. Costs & Totals
        if (!empty($result['cost_summary'])) {
            $compressed['cost_summary'] = $result['cost_summary'];
        } elseif (!empty($result['cost_breakdown'])) {
            $compressed['cost_breakdown'] = $result['cost_breakdown'];
        }

        // 2. TMT Steel Rods (Fe500D) metrics
        if (!empty($result['rebar_breakdown'])) {
            $rb = $result['rebar_breakdown'];
            $compressed['tmt_steel_rods'] = [
                'total_steel_kg' => $rb['total_steel_kg'] ?? 0,
                'total_steel_tonnes' => $rb['total_steel_tonnes'] ?? 0,
                'total_12m_rods' => $rb['total_standard_12m_rods'] ?? 0,
                'binding_wire_kg' => $rb['binding_wire_kg'] ?? 0,
                'summary_schedule' => $rb['bar_schedule'] ?? []
            ];
        } elseif (!empty($result['indian_market_materials']['sariya_tmt_steel'])) {
            $compressed['tmt_steel_rods'] = $result['indian_market_materials']['sariya_tmt_steel'];
        }

        // 3. Essential Indian Market Bulk Materials
        if (!empty($result['indian_market_materials'])) {
            $im = $result['indian_market_materials'];
            $compressed['bulk_materials'] = [
                'cement_bags_50kg' => $im['cement_bags_50kg']['total_bags'] ?? ($result['civil_and_flooring']['total_cement_bags_50kg'] ?? null),
                'sand_brass' => $im['sand_reti_msand']['volume_brass'] ?? null,
                'aggregate_brass' => $im['coarse_aggregate_rori_gitti']['volume_brass'] ?? null,
                'bricks_count' => $im['first_class_bricks']['total_bricks'] ?? ($result['structural_elements']['total_bricks_count'] ?? null),
            ];
        }

        // 4. Domain-specific concise keys
        if (!empty($result['scour_hydraulics'])) {
            $compressed['scour_hydraulics'] = $result['scour_hydraulics'];
            $compressed['pile_geometry'] = $result['pile_geometry'] ?? [];
        }

        if (!empty($result['concrete_and_waterproofing'])) {
            $compressed['concrete_and_waterproofing'] = $result['concrete_and_waterproofing'];
        }

        if (!empty($result['fire_safety_provisions'])) {
            $compressed['fire_safety_provisions'] = $result['fire_safety_provisions'];
        }

        if (!empty($result['peb_structural_steel'])) {
            $compressed['peb_structural_steel'] = $result['peb_structural_steel'];
            $compressed['cladding_and_envelope'] = $result['cladding_and_envelope'] ?? [];
        }

        if (!empty($result['pavement_layers'])) {
            $compressed['pavement_layers'] = $result['pavement_layers'];
            $compressed['subgrade_earthwork'] = $result['subgrade_earthwork'] ?? [];
            $compressed['stormwater_drainage'] = $result['stormwater_drainage'] ?? [];
        }

        return $compressed;
    }
}
