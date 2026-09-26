<?php

namespace App\Services\Assistant;

use Anthropic\Beta\Messages\BetaMessage;

/**
 * One round-trip to the language model. Kept behind an interface so the
 * conversation loop can be tested without calling the real API.
 */
interface AssistantModel
{
    /**
     * @param  array<int, array<string, mixed>>  $system
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     */
    public function respond(array $system, array $messages, array $tools): BetaMessage;
}
