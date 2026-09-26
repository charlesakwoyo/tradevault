<?php

namespace App\Services\Assistant;

use App\Models\Market;
use App\Models\User;
use App\Services\Dashboard\UserDashboardService;
use App\Support\Money;

/**
 * Read-only tools the assistant may call. Every account tool is scoped to the
 * signed-in user on the server side; the model never chooses whose data it
 * sees, and no tool can move money or place orders.
 */
class AssistantTools
{
    private const MAX_TRANSACTIONS = 20;

    public function __construct(private UserDashboardService $dashboard) {}

    /** Tools that read the signed-in user's own wallet data; only customers have any. */
    private const ACCOUNT_TOOLS = ['get_my_account_summary', 'get_my_recent_transactions'];

    /**
     * Tools offered to this user: everyone gets prices, customers also get their account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(User $user): array
    {
        return array_values(array_filter(
            $this->allDefinitions(),
            fn (array $tool) => $user->isCustomer() || ! in_array($tool['name'], self::ACCOUNT_TOOLS, true),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allDefinitions(): array
    {
        return [
            [
                'name' => 'get_market_prices',
                'description' => 'Latest price, 24-hour change, high and low for crypto markets listed on TradeVault. Pass an empty list to get every market.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'symbols' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Asset symbols such as BTC or ETH. Empty for all markets.',
                        ],
                    ],
                    'required' => ['symbols'],
                ],
            ],
            [
                'name' => 'get_my_account_summary',
                'description' => "The signed-in customer's balances, totals deposited and withdrawn, and verification status (email, phone, identity/KYC, two-factor).",
                // No inputs. `properties` is left out: an empty PHP array would serialize as [] rather than {}.
                'inputSchema' => ['type' => 'object'],
            ],
            [
                'name' => 'get_my_recent_transactions',
                'description' => "The signed-in customer's most recent ledger transactions, newest first.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'How many to return, 1 to '.self::MAX_TRANSACTIONS.'.'],
                    ],
                    'required' => ['limit'],
                ],
            ],
        ];
    }

    /**
     * Run a tool and return its result as a JSON string for the model.
     *
     * @param  array<string, mixed>  $input
     */
    public function run(User $user, string $name, array $input): string
    {
        if (in_array($name, self::ACCOUNT_TOOLS, true) && ! $user->isCustomer()) {
            return json_encode(['error' => 'Account tools are only available to customers.']);
        }

        $result = match ($name) {
            'get_market_prices' => $this->marketPrices(is_array($input['symbols'] ?? null) ? $input['symbols'] : []),
            'get_my_account_summary' => $this->accountSummary($user),
            'get_my_recent_transactions' => $this->recentTransactions($user, (int) ($input['limit'] ?? 5)),
            default => ['error' => "Unknown tool [{$name}]."],
        };

        return json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<int, mixed>  $symbols
     * @return array<string, mixed>
     */
    private function marketPrices(array $symbols): array
    {
        $wanted = collect($symbols)->filter(fn ($s) => is_string($s))->map(fn (string $s) => strtoupper(trim($s)))->filter()->values();

        $markets = Market::query()
            ->active()
            ->with('latestPrice')
            ->when($wanted->isNotEmpty(), fn ($q) => $q->whereIn('base_asset', $wanted))
            ->orderBy('id')
            ->get();

        return [
            'source' => 'Binance public market data (USD prices from USDT pairs, indicative only)',
            'markets' => $markets->map(fn (Market $market) => [
                'symbol' => $market->base_asset,
                'name' => $market->name,
                'price' => $market->latestPrice ? Money::format($market->latestPrice->last, $market->quote_currency, $market->price_precision) : null,
                'change_24h_percent' => $market->latestPrice?->changePercent(),
                'high_24h' => $market->latestPrice?->high,
                'low_24h' => $market->latestPrice?->low,
                'updated' => $market->latestPrice?->recorded_at?->diffForHumans(),
                'delayed' => $market->latestPrice?->isStale() ?? true,
            ])->all(),
            'not_listed' => $wanted->diff($markets->pluck('base_asset'))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountSummary(User $user): array
    {
        $summary = $this->dashboard->summary($user);
        $currency = $summary['currency'];

        return [
            'name' => $user->name,
            'member_since' => $user->created_at?->toDateString(),
            'available_balance' => Money::format($summary['available'], $currency),
            'reserved_balance' => Money::format($summary['reserved'], $currency),
            'total_deposited' => Money::format($summary['total_deposited'], $currency),
            'total_withdrawn' => Money::format($summary['total_withdrawn'], $currency),
            'email_verified' => $user->hasVerifiedEmail(),
            'phone_verified' => $user->hasVerifiedPhone(),
            'identity_verification' => $summary['kyc_status']->label(),
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recentTransactions(User $user, int $limit): array
    {
        $limit = max(1, min(self::MAX_TRANSACTIONS, $limit));

        return [
            'transactions' => $user->walletTransactions()->latest()->limit($limit)->get()->map(fn ($tx) => [
                'date' => $tx->created_at->toDateTimeString(),
                'type' => $tx->type->label(),
                'amount' => Money::format($tx->amount, $tx->currency),
                'status' => $tx->status->label(),
                'description' => $tx->description,
                'reference' => $tx->reference,
            ])->all(),
        ];
    }
}
