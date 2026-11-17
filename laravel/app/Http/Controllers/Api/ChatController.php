<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\GroqConstructionAgent;
use App\Services\Ai\RagRetrieverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected GroqConstructionAgent $agent,
        protected RagRetrieverService $ragRetriever
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
            'retrieved_context' => 'sometimes|nullable|string',
            'context' => 'sometimes|nullable|string',
            'active_estimate' => 'sometimes|nullable|array',
            'api_key' => 'sometimes|nullable|string',
            'model' => 'sometimes|nullable|string',
        ]);

        $retrievedContext = $validated['retrieved_context'] ?? $validated['context'] ?? null;

        // If explicit context was not provided, automatically retrieve top relevant knowledge chunks
        if (empty($retrievedContext)) {
            $lastUserMessage = '';
            for ($i = count($validated['messages']) - 1; $i >= 0; $i--) {
                if (($validated['messages'][$i]['role'] ?? '') === 'user') {
                    $lastUserMessage = (string)($validated['messages'][$i]['content'] ?? '');
                    break;
                }
            }

            if (!empty($lastUserMessage)) {
                $retrievedContext = $this->ragRetriever->getFormattedContext($lastUserMessage, 3);
            }
        }

        $result = $this->agent->processChat(
            messages: $validated['messages'],
            activeEstimate: $validated['active_estimate'] ?? null,
            apiKey: $validated['api_key'] ?? null,
            model: $validated['model'] ?? null,
            retrievedContext: $retrievedContext
        );

        return response()->json($result);
    }
}

