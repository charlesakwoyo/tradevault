@php
    use App\Support\Money;
    $c = $stats['currency'];
@endphp

<x-layouts.app :title="__('Back office')" area="admin">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-5">
        <x-stat-card :label="__('Customers')" :value="number_format($stats['total_users'])" icon="users" />
        <x-stat-card :label="__('KYC approved')" :value="number_format($stats['verified_users'])" icon="badge" />
        <x-stat-card :label="__('Pending KYC')" :value="number_format($stats['pending_kyc'])" icon="eye" />
        <x-stat-card :label="__('Pending deposits')" :value="number_format($stats['pending_deposits'])" icon="download" />
        <x-stat-card :label="__('Pending withdrawals')" :value="number_format($stats['pending_withdrawals'])" icon="upload" />
        <x-stat-card :label="__('Total deposits')" :value="Money::format($stats['total_deposits'], $c)" icon="download" class="col-span-2 lg:col-span-1" />
        <x-stat-card :label="__('Total withdrawals')" :value="Money::format($stats['total_withdrawals'], $c)" icon="upload" class="col-span-2 lg:col-span-1" />
        <x-stat-card :label="__('Trading volume')" :value="Money::format($stats['trading_volume'], $c)" icon="arrows" class="col-span-2 lg:col-span-1" />
        <x-stat-card :label="__('Platform fees')" :value="Money::format($stats['platform_fees'], $c)" icon="percent" class="col-span-2 lg:col-span-1" />
        <x-stat-card :label="__('Active orders')" :value="number_format($stats['active_orders'])" icon="chart" />
    </div>
    <p class="mt-2 text-xs text-ink-400">{{ __('Monetary totals are for :currency and are computed from completed ledger transactions. Figures refresh every minute.', ['currency' => $c]) }}</p>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-chart-card :title="__('New customers (30 days)')" :series="$charts['signups']" type="bar" :empty="__('No sign-ups in the last 30 days.')" />
        <x-chart-card :title="__('Deposits (30 days)')" :series="$charts['deposits']" type="bar" :currency="$c" :empty="__('No completed deposits in the last 30 days.')" />
        <x-chart-card :title="__('Withdrawals (30 days)')" :series="$charts['withdrawals']" type="bar" color="ink" :currency="$c" :empty="__('No completed withdrawals in the last 30 days.')" />
    </div>

    @can('audit-logs.view')
        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4"><h2 class="text-sm font-semibold text-ink-800">{{ __('Recent activity') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="bg-ink-50/60"><tr><th>{{ __('When') }}</th><th>{{ __('Actor') }}</th><th>{{ __('Action') }}</th><th>{{ __('IP') }}</th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($recentActivity as $log)
                            <tr>
                                <td class="text-ink-500">{{ $log->created_at->diffForHumans() }}</td>
                                <td>{{ $log->user?->email ?? __('System / guest') }}</td>
                                <td><code class="rounded bg-ink-50 px-1.5 py-0.5 text-xs">{{ $log->action }}</code></td>
                                <td class="text-ink-500">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-ink-400">{{ __('No activity yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan
</x-layouts.app>
