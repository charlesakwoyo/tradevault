@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Markets')">
    {{-- Prices refresh server-side every minute; reload to pick them up. --}}
    <div x-data x-init="setTimeout(() => window.location.reload(), 60000)"></div>

    <p class="mb-4 text-sm text-ink-500">
        {{ __('Indicative prices, refreshed every minute. USD markets are priced from USDT pairs.') }}
    </p>

    @php
        $change = fn ($price) => $price?->changePercent();
        $changeClass = fn (?float $pct) => $pct === null ? 'text-ink-400' : ($pct > 0 ? 'text-emerald-600' : ($pct < 0 ? 'text-rose-600' : 'text-ink-500'));
        $changeText = fn (?float $pct) => $pct === null ? '—' : ($pct > 0 ? '+' : '').number_format($pct, 2).'%';
    @endphp

    <div class="card overflow-hidden">
        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="table-base">
                <thead class="bg-ink-50/60">
                    <tr>
                        <th>{{ __('Market') }}</th>
                        <th class="text-right">{{ __('Price') }}</th>
                        <th class="text-right">{{ __('24h change') }}</th>
                        <th class="text-right">{{ __('24h high') }}</th>
                        <th class="text-right">{{ __('24h low') }}</th>
                        <th class="text-right">{{ __('24h volume') }}</th>
                        <th>{{ __('Source') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($markets as $market)
                        @php $price = $market->latestPrice; $pct = $change($price); @endphp
                        <tr class="hover:bg-ink-50/60">
                            <td>
                                <span class="font-medium text-ink-900">{{ $market->base_asset }}</span>
                                <span class="ml-1 text-ink-400">{{ $market->name }}</span>
                            </td>
                            <td class="text-right font-semibold tabular-nums">{{ $price ? Money::format($price->last, $market->quote_currency, $market->price_precision) : '—' }}</td>
                            <td class="text-right font-medium tabular-nums {{ $changeClass($pct) }}">{{ $changeText($pct) }}</td>
                            <td class="text-right tabular-nums text-ink-500">{{ $price?->high ? Money::format($price->high, '', $market->price_precision) : '—' }}</td>
                            <td class="text-right tabular-nums text-ink-500">{{ $price?->low ? Money::format($price->low, '', $market->price_precision) : '—' }}</td>
                            <td class="text-right tabular-nums text-ink-500">{{ $price?->volume ? Money::format($price->volume, $market->base_asset, 0) : '—' }}</td>
                            <td>@include('markets.partials.source', ['price' => $price])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-ink-400">{{ __('No markets are listed yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="divide-y divide-ink-100 md:hidden">
            @forelse ($markets as $market)
                @php $price = $market->latestPrice; $pct = $change($price); @endphp
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-ink-900">{{ $market->base_asset }} <span class="font-normal text-ink-400">{{ $market->name }}</span></span>
                        <span class="text-sm font-semibold tabular-nums">{{ $price ? Money::format($price->last, $market->quote_currency, $market->price_precision) : '—' }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs">
                        @include('markets.partials.source', ['price' => $price])
                        <span class="font-medium tabular-nums {{ $changeClass($pct) }}">{{ $changeText($pct) }}</span>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-ink-400">{{ __('No markets are listed yet.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
