<?php

namespace App\Http\Controllers;

use App\Services\Assistant\TradeVaultAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat endpoint for the in-app assistant. The conversation lives in the
 * session, trimmed to the most recent turns.
 */
class AssistantController extends Controller
{
    public const SESSION_KEY = 'assistant.history';

    /** Messages kept in the conversation (user + assistant). */
    private const MAX_HISTORY = 20;

    public function store(Request $request, TradeVaultAssistant $assistant): JsonResponse
    {
        abort_unless(TradeVaultAssistant::isConfigured(), 404);

        $validated = $request->validate(['message' => ['required', 'string', 'max:1000']]);

        $history = $request->session()->get(self::SESSION_KEY, []);
        $reply = $assistant->reply($request->user(), $history, $validated['message']);

        $history = [...$history, ['role' => 'user', 'content' => $validated['message']], ['role' => 'assistant', 'content' => $reply]];
        $request->session()->put(self::SESSION_KEY, array_slice($history, -self::MAX_HISTORY));

        return response()->json(['reply' => $reply]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['cleared' => true]);
    }
}
