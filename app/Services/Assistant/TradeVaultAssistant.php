<?php

namespace App\Services\Assistant;

use Anthropic\Beta\Messages\BetaToolUseBlock;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Log;

/**
 * The in-app help assistant: answers questions about the platform, live
 * prices and the customer's own account using read-only tools.
 */
class TradeVaultAssistant
{
    /** Upper bound on tool round-trips per question, so a confused turn cannot loop forever. */
    private const MAX_TOOL_ROUNDS = 5;

    public function __construct(
        private AssistantModel $model,
        private AssistantTools $tools,
    ) {}

    public static function isConfigured(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    /**
     * @param  list<array{role: string, content: string}>  $history  earlier turns, oldest first
     */
    public function reply(User $user, array $history, string $question): string
    {
        $messages = [...$history, ['role' => 'user', 'content' => $question]];

        try {
            for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
                $response = $this->model->respond($this->system(), $messages, $this->tools->definitions($user));

                if ($response->stopReason === 'refusal') {
                    return __('Sorry, I can’t help with that. For account questions, contact :email.', ['email' => config('tradevault.support_email')]);
                }

                if ($response->stopReason !== 'tool_use') {
                    return $this->text($response->content) ?: __('Sorry, I don’t have an answer for that. Could you rephrase?');
                }

                $results = [];
                foreach ($response->content as $block) {
                    if ($block instanceof BetaToolUseBlock) {
                        $results[] = [
                            'type' => 'tool_result',
                            'toolUseID' => $block->id,
                            'content' => $this->tools->run($user, $block->name, is_array($block->input) ? $block->input : []),
                        ];
                    }
                }

                $messages[] = ['role' => 'assistant', 'content' => $response->content];
                $messages[] = ['role' => 'user', 'content' => $results];
            }
        } catch (APIStatusException|APIConnectionException $e) {
            Log::warning('Assistant request failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return __('The assistant is unavailable right now. Please try again in a moment.');
        }

        return __('Sorry, that took too many steps. Could you ask a more specific question?');
    }

    /**
     * Stable across requests (no timestamps or per-user data) so it can be cached.
     *
     * @return array<int, array<string, mixed>>
     */
    private function system(): array
    {
        $currency = config('tradevault.base_currency');
        $limits = config('tradevault.limits');

        $prompt = implode("\n", [
            'You are the help assistant inside '.config('app.name').', an online platform where customers follow live crypto-asset prices and manage a verified account.',
            '',
            'What you can do: explain how the platform works, look up live prices with get_market_prices, and look up the signed-in customer\'s own balances, verification status and transactions with the account tools. Use the tools rather than guessing numbers.',
            'If the account tools are not available, you are talking to a staff member (for example an administrator) who has no customer wallet: help with prices and platform questions, and explain that account figures are only available to customers.',
            '',
            'Platform facts:',
            '- Listed markets are crypto-assets quoted in USD. Prices come from Binance public data (USDT pairs) and refresh every minute; they are indicative.',
            '- Deposits, withdrawals and trading are not open yet. They are coming soon for verified customers. You cannot move money or place orders.',
            '- Customers must be '.config('tradevault.registration.minimum_age').' or older, and verify their email, phone and identity (KYC).',
            '- Deposit limits: '.Money::format($limits['deposit_min'], $currency).' to '.Money::format($limits['deposit_max'], $currency).' per transaction. Withdrawals: up to '.Money::format($limits['withdrawal_daily_max'], $currency).' per day.',
            '- Customers can enable two-factor authentication under Security, and sign in with Google.',
            '- Support email: '.config('tradevault.support_email').'.',
            '',
            'Rules:',
            '- Do not give personalised investment advice or tell the customer to buy, sell or hold anything. You may explain concepts and describe price movements factually. When relevant, remind them that crypto-assets are volatile and they can lose money.',
            '- Only discuss the signed-in customer\'s own account. Never ask for passwords, 2FA codes or full card or ID numbers.',
            '- If you cannot help, point them to the relevant page or to support.',
            '- Reply in plain text without Markdown formatting. Keep answers short and friendly, and reply in the language the customer writes in.',
        ]);

        return [['type' => 'text', 'text' => $prompt, 'cacheControl' => ['type' => 'ephemeral']]];
    }

    /**
     * @param  array<int, mixed>  $content
     */
    private function text(array $content): string
    {
        $parts = [];
        foreach ($content as $block) {
            if (($block->type ?? null) === 'text') {
                $parts[] = $block->text;
            }
        }

        return trim(implode("\n\n", $parts));
    }
}
