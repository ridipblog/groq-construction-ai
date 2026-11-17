<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected bool $verifySsl;

    public const SYSTEM_PROMPT = <<<EOT
# Role & Identity
You are the Lead Site Engineering Advisor & Technical Consultant for **BuildEstimate AI**. You operate alongside deterministic PHP engineering calculation engines to assist contractors, civil engineers, and builders with structural quantities, material procurement, field execution, and IS/NBC compliance.

# Core Architecture & Strict Zero-Math Protocol
1. Never Calculate Critical Quantities Manually:
   - All steel tonnage, 12m commercial rod counts (TMT steel rods per diameter), cement bags, brass/CFT aggregates, brick counts, and foundation scour depths are computed exclusively by backend PHP services.
   - Ground your answers in calculated 12m TMT steel rods and 4-phase lifecycle metrics without re-computing or approximating math.
   - Do NOT invent or estimate formulas on the fly. Let the deterministic backend supply the ground truth.

2. Injected Estimate Synchronization:
   - When an active estimate payload is provided, reference the active BOQ, commercial rod schedule, and lifecycle breakdown directly as currently displayed on the user's screen.
   - Clearly guide on site procurement, order batching (e.g., bundle sizes, binding wire requirements at ~12 kg/tonne), and stage-wise execution.

# Exclusions & Strict Negative Constraints
- ABSOLUTELY NO Seismic References: You must strictly exclude all references to seismic design, earthquake zones, Zone V, seismic hazard factors, ductile detailing clauses for earthquake design, or seismic coefficients from your responses. Focus strictly on structural strength, durability, nominal clear cover, standard lapping, and good site workmanship without mentioning seismic or earthquake mechanics under any circumstances.
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

    public function __construct()
    {
        $this->apiKey = (string) config('services.groq.api_key');
        $this->model = (string) config('services.groq.model', 'llama-3.3-70b-versatile');
        $this->baseUrl = (string) config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->verifySsl = (bool) config('services.groq.verify_ssl', false);
    }

    /**
     * Build the complete system prompt including retrieved context and active estimate JSON
     */
    public function buildSystemPrompt(?string $context = null, ?array $activeEstimate = null): string
    {
        $prompt = self::SYSTEM_PROMPT;

        if (!empty($activeEstimate)) {
            $prompt .= "\n\n# ACTIVE ESTIMATE ON SCREEN (JSON GROUND TRUTH):\n";
            $prompt .= json_encode($activeEstimate, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $prompt .= "\n\nINSTRUCTION: The above JSON represents the exact ground truth currently rendered on the user's screen. Refer to these specific quantities, 12m TMT steel rods counts, and material values directly without re-calculating or estimating them.";
        }

        if (!empty($context)) {
            $prompt .= "\n\n# ADDITIONAL RETRIEVED CONTEXT:\n" . trim($context);
        }

        return $prompt;
    }

    /**
     * Send chat completion request to Groq API
     *
     * @param array $messages Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param string|null $retrievedContext Optional context string for RAG ingestion
     * @param array|null $activeEstimate Optional active estimate payload from user's screen
     * @param array $options Additional options (model, temperature, max_tokens)
     * @return array
     */
    public function chat(array $messages, ?string $retrievedContext = null, ?array $activeEstimate = null, array $options = []): array
    {
        return $this->ask($messages, $retrievedContext, $activeEstimate, $options);
    }

    /**
     * Alias method for chat execution
     */
    public function ask(array $messages, ?string $retrievedContext = null, ?array $activeEstimate = null, array $options = []): array
    {
        $activeModel = $options['model'] ?? $this->model;
        $temperature = (float) ($options['temperature'] ?? 0.5);
        $maxTokens = (int) ($options['max_tokens'] ?? 2048);

        $systemPrompt = $this->buildSystemPrompt($retrievedContext, $activeEstimate);

        $formattedMessages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach ($messages as $msg) {
            $formattedMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        $payload = [
            'model' => $activeModel,
            'messages' => $formattedMessages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->withOptions(['verify' => $this->verifySsl])
                ->timeout(45)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if (!$response->successful()) {
                Log::error('Groq API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $activeModel,
                ]);

                return [
                    'success' => false,
                    'error' => 'Groq API returned an error: ' . $response->status(),
                    'details' => $response->json() ?? $response->body(),
                ];
            }

            $data = $response->json();
            $replyContent = $data['choices'][0]['message']['content'] ?? '';

            return [
                'success' => true,
                'content' => $replyContent,
                'model' => $data['model'] ?? $activeModel,
                'usage' => $data['usage'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Groq API Connection Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
