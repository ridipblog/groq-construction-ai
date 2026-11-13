<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    /**
     * Test valid 200 JSON response on well-formed chat payloads without active_estimate.
     */
    public function test_valid_chat_payload_returns_200_and_assistant_reply(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-mock-123',
                'object' => 'chat.completion',
                'created' => 1731234567,
                'model' => 'llama-3.3-70b-versatile',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Per IS 456:2000 Clause 26.4.1, the minimum nominal clear cover for moderate exposure should be 30 mm.',
                        ],
                        'finish_reason' => 'stop',
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 120,
                    'completion_tokens' => 45,
                    'total_tokens' => 165,
                ],
            ], 200),
        ]);

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'What is the required clear cover for moderate exposure conditions under IS 456?',
                ]
            ],
            'context' => 'Site location: typical inland urban building.'
        ];

        $response = $this->postJson('/api/chat', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'reply' => 'Per IS 456:2000 Clause 26.4.1, the minimum nominal clear cover for moderate exposure should be 30 mm.',
                    'model' => 'llama-3.3-70b-versatile',
                    'usage' => [
                        'total_tokens' => 165,
                    ],
                ],
            ]);

        $reply = $response->json('data.reply');
        $this->assertStringNotContainsStringIgnoringCase('seismic', $reply);
        $this->assertStringNotContainsStringIgnoringCase('zone v', $reply);
    }

    /**
     * Test sending payload with active_estimate injects JSON estimate into Groq prompt and returns clean non-seismic reply.
     */
    public function test_active_estimate_injection_executes_and_passes_payload_to_groq(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-mock-active-456',
                'object' => 'chat.completion',
                'created' => 1731234588,
                'model' => 'llama-3.3-70b-versatile',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Reviewing your active BOQ: The structure requires 5.4 tonnes of Fe500D steel, representing 456 standard 12m commercial rods, alongside 620 cement bags. For Phase 1 structural casting, prioritize pre-pour formwork alignment and chair spacers.',
                        ],
                        'finish_reason' => 'stop',
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 250,
                    'completion_tokens' => 60,
                    'total_tokens' => 310,
                ],
            ], 200),
        ]);

        $activeEstimate = [
            'project_type' => 'RCC Framed Residential Building',
            'inputs' => [
                'total_built_up_area_sqft' => 1200,
                'number_of_floors' => 1,
            ],
            'cost_summary' => [
                'total_estimated_materials_cost_inr' => 1485000,
                'cost_per_sqft_inr' => 1237.5,
            ],
            'rebar_breakdown' => [
                'total_steel_tonnes' => 5.4,
                'total_standard_12m_rods' => 456,
            ],
            'indian_market_materials' => [
                'cement_bags_50kg' => ['total_bags' => 620],
                'sand_reti_msand' => ['volume_brass' => 9.8],
                'coarse_aggregate_rori_gitti' => ['volume_brass' => 14.4],
                'first_class_bricks' => ['total_bricks' => 10200],
            ],
        ];

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Review the materials on screen and guide me on site procurement.',
                ]
            ],
            'active_estimate' => $activeEstimate,
        ];

        $response = $this->postJson('/api/chat', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'model' => 'llama-3.3-70b-versatile',
                ],
            ]);

        $reply = $response->json('data.reply');
        $this->assertStringNotContainsStringIgnoringCase('seismic', $reply);
        $this->assertStringNotContainsStringIgnoringCase('zone v', $reply);

        // Verify that the prompt sent to Groq contains the serialized active estimate JSON
        Http::assertSent(function ($request) {
            $systemContent = $request['messages'][0]['content'] ?? '';
            return str_contains($request->url(), 'api.groq.com/openai/v1/chat/completions') &&
                   str_contains($systemContent, 'RCC Framed Residential Building') &&
                   str_contains($systemContent, '1485000') &&
                   str_contains($systemContent, 'total_standard_12m_rods');
        });
    }

    /**
     * Test 422 Unprocessable Entity on validation failures.
     */
    public function test_invalid_payload_returns_422_validation_error(): void
    {
        // 1. Missing messages array
        $response = $this->postJson('/api/chat', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['messages']);

        // 2. Empty messages array
        $response = $this->postJson('/api/chat', ['messages' => []]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['messages']);

        // 3. Invalid role inside messages
        $response = $this->postJson('/api/chat', [
            'messages' => [
                [
                    'role' => 'invalid_role',
                    'content' => 'Hello engineer',
                ]
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['messages.0.role']);

        // 4. Missing content inside message item
        $response = $this->postJson('/api/chat', [
            'messages' => [
                [
                    'role' => 'user',
                ]
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['messages.0.content']);

        // 5. Invalid active_estimate format (must be an array)
        $response = $this->postJson('/api/chat', [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Check this',
                ]
            ],
            'active_estimate' => 'not_an_array'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['active_estimate']);
    }

    /**
     * Test graceful error handling when the external Groq API returns 500.
     */
    public function test_groq_api_failure_handled_gracefully_with_502(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Internal server error from upstream model provider.',
                    'type' => 'server_error',
                ]
            ], 500),
        ]);

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Calculate beam deflection for 6m clear span.',
                ]
            ]
        ];

        $response = $this->postJson('/api/chat', $payload);

        $response->assertStatus(502)
            ->assertJson([
                'status' => 'error',
                'message' => 'Groq API returned an error: 500',
            ]);
    }
}
