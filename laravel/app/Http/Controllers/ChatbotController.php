<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __construct(
        protected GroqService $groqService
    ) {}

    /**
     * Process conversational request using Groq
     */
    public function ask(ChatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->groqService->ask(
            messages: $validated['messages'],
            retrievedContext: $validated['context'] ?? null,
            activeEstimate: $validated['active_estimate'] ?? null
        );

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'] ?? 'Failed to communicate with Groq AI service.',
                'details' => $result['details'] ?? null,
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'reply' => $result['content'],
                'model' => $result['model'],
                'usage' => $result['usage'],
            ],
        ]);
    }
}
