<?php

namespace App\Services\Assistant;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Client;

/**
 * Claude via the official Anthropic PHP SDK. Low effort suits short support
 * chat; server-side fallbacks keep answering if the model declines a turn.
 */
class ClaudeAssistantModel implements AssistantModel
{
    public function __construct(
        private Client $client,
        private string $model,
    ) {}

    public function respond(array $system, array $messages, array $tools): BetaMessage
    {
        return $this->client->beta->messages->create(
            maxTokens: 16000,
            messages: $messages,
            model: $this->model,
            system: $system,
            tools: $tools,
            outputConfig: ['effort' => 'low'],
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );
    }
}
