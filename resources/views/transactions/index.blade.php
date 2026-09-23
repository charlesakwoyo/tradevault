@php use App\Enums\TransactionType; use App\Support\Money; @endphp

<x-layouts.app :title="__('Transactions')">
    <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div>
            <label for="type" class="form-label">{{ __('Type') }}</label>
            <select id="type" name="type" class="form-input sm:w-48">
                <option value="">{{ __('All types') }}</option>
                @foreach (TransactionType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="currency" class="form-label">{{ __('Currency') }}</label>
            <select id="currency" name="currency" class="form-input sm:w-32">
                <option value="">{{ __('All') }}</option>
                @foreach (config('tradevault.currencies') as $currency)
                    <option value="{{ $currency }}" @selected(($filters['currency'] ?? null) === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">{{ __('Filter') }}</button>
    </form>

    <div class="card overflow-hidden">
        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="table-base">
                <thead class="bg-ink-50/60">
                    <tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Description') }}</th><th>{{ __('Reference') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($transactions as $tx)
                        <tr class="hover:bg-ink-50/60">
                            <td class="text-ink-500">{{ $tx->created_at->format('M j, Y H:i') }}</td>
                            <td class="font-medium text-ink-900">{{ $tx->type->label() }}</td>
                            <td class="max-w-xs truncate">{{ $tx->description }}</td>
                            <td><a href="{{ route('transactions.show', $tx) }}" class="font-mono text-xs text-brand-700 hover:underline">{{ $tx->reference }}</a></td>
                            <td class="text-right font-semibold tabular-nums">{{ Money::format($tx->amount, $tx->currency) }}</td>
                            <td><x-status-badge :status="$tx->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-ink-400">{{ __('No transactions found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="divide-y divide-ink-100 md:hidden">
            @forelse ($transactions as $tx)
                <a href="{{ route('transactions.show', $tx) }}" class="block px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-ink-900">{{ $tx->type->label() }}</span>
                        <span class="text-sm font-semibold tabular-nums">{{ Money::format($tx->amount, $tx->currency) }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-ink-400">
                        <span>{{ $tx->created_at->format('M j, H:i') }}</span>
                        <x-status-badge :status="$tx->status" />
                    </div>
                </a>
            @empty
                <p class="py-12 text-center text-sm text-ink-400">{{ __('No transactions found.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</x-layouts.app>
