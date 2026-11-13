<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\GroqConstructionAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected GroqConstructionAgent $agent
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
            'active_estimate' => 'sometimes|nullable|array',
            'api_key' => 'sometimes|nullable|string',
            'model' => 'sometimes|nullable|string',
        ]);

        $result = $this->agent->processChat(
            messages: $validated['messages'],
            activeEstimate: $validated['active_estimate'] ?? null,
            apiKey: $validated['api_key'] ?? null,
            model: $validated['model'] ?? null
        );

        return response()->json($result);
    }
}

