@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Transaction details')">
    <a href="{{ route('transactions.index') }}" class="mb-4 inline-block text-sm font-medium text-brand-700 hover:text-brand-800">&larr; {{ __('All transactions') }}</a>

    <div class="card p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm text-ink-500">{{ $transaction->type->label() }}</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">{{ Money::format($transaction->amount, $transaction->currency) }}</p>
            </div>
            <x-status-badge :status="$transaction->status" class="self-start" />
        </div>

        <dl class="mt-6 grid grid-cols-1 gap-4 border-t border-ink-100 pt-6 text-sm sm:grid-cols-2">
            <div><dt class="text-ink-400">{{ __('Description') }}</dt><dd class="mt-0.5 text-ink-900">{{ $transaction->description }}</dd></div>
            <div><dt class="text-ink-400">{{ __('Reference') }}</dt><dd class="mt-0.5 font-mono text-xs text-ink-900">{{ $transaction->reference }}</dd></div>
            <div><dt class="text-ink-400">{{ __('Transaction ID') }}</dt><dd class="mt-0.5 font-mono text-xs text-ink-900">{{ $transaction->uuid }}</dd></div>
            <div><dt class="text-ink-400">{{ __('Date') }}</dt><dd class="mt-0.5 text-ink-900">{{ $transaction->created_at->format('M j, Y H:i:s T') }}</dd></div>
        </dl>

        <h3 class="mt-8 text-sm font-semibold text-ink-800">{{ __('Effect on your wallets') }}</h3>
        <div class="mt-3 overflow-x-auto rounded-xl border border-ink-100">
            <table class="table-base">
                <thead class="bg-ink-50/60"><tr><th>{{ __('Wallet') }}</th><th class="text-right">{{ __('Change') }}</th><th class="text-right">{{ __('Balance after') }}</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($transaction->entries as $entry)
                        <tr>
                            <td>{{ $entry->wallet->type->label() }} ({{ $entry->wallet->currency }})</td>
                            <td class="text-right tabular-nums {{ Money::isNegative($entry->amount) ? 'text-rose-600' : 'text-emerald-600' }}">{{ Money::isPositive($entry->amount) ? '+' : '' }}{{ Money::format($entry->amount) }}</td>
                            <td class="text-right tabular-nums">{{ Money::format($entry->balance_after) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
