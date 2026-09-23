@php use App\Support\Countries; use App\Support\Money; @endphp

<x-layouts.app :title="$user->name" area="admin">
    <a href="{{ route('admin.users.index') }}" class="mb-4 inline-block text-sm font-medium text-brand-700 hover:text-brand-800">&larr; {{ __('All users') }}</a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="card p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-ink-900">{{ $user->name }}</h2>
                    <p class="text-sm text-ink-500">{{ $user->email }}</p>
                </div>
                <x-status-badge :status="$user->status" />
            </div>
            @if ($user->isSuspended())
                <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ __('Suspended :when: :reason', ['when' => $user->suspended_at?->diffForHumans(), 'reason' => $user->suspension_reason]) }}</p>
            @endif
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('User ID') }}</dt><dd class="truncate font-mono text-xs">{{ $user->uuid }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Phone') }}</dt><dd>{{ $user->phone }} @if ($user->hasVerifiedPhone()) <x-icon name="check" class="inline size-4 text-emerald-600" /> @endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Email verified') }}</dt><dd>{{ $user->email_verified_at?->format('M j, Y') ?? __('No') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Country') }}</dt><dd>{{ Countries::name($user->country) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Date of birth') }}</dt><dd>{{ $user->date_of_birth->format('M j, Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Roles') }}</dt><dd>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('KYC') }}</dt><dd><x-status-badge :status="$user->kycStatus()" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('2FA') }}</dt><dd>{{ $user->hasTwoFactorEnabled() ? __('Enabled') : __('Off') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Terms accepted') }}</dt><dd>{{ $user->terms_accepted_at?->format('M j, Y') ?? '—' }} ({{ $user->terms_version ?? '—' }})</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Last login') }}</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? __('Never') }} {{ $user->last_login_ip ? '· '.$user->last_login_ip : '' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-400">{{ __('Joined') }}</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
            </dl>
        </section>

        <div class="space-y-6 xl:col-span-2">
            @if ($balances !== null)
                <section class="card overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4"><h2 class="text-sm font-semibold text-ink-800">{{ __('Wallets') }}</h2></div>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="bg-ink-50/60"><tr><th>{{ __('Currency') }}</th><th class="text-right">{{ __('Available') }}</th><th class="text-right">{{ __('Reserved') }}</th><th class="text-right">{{ __('Trading') }}</th></tr></thead>
                            <tbody class="divide-y divide-ink-100">
                                @forelse ($balances as $currency => $b)
                                    <tr>
                                        <td class="font-medium">{{ $currency }}</td>
                                        <td class="text-right tabular-nums">{{ Money::format($b['available'] ?? 0) }}</td>
                                        <td class="text-right tabular-nums">{{ Money::format($b['reserved'] ?? 0) }}</td>
                                        <td class="text-right tabular-nums">{{ Money::format($b['trading'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-ink-400">{{ __('No wallets (staff account).') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @can('transactions.view')
                <section class="card overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4"><h2 class="text-sm font-semibold text-ink-800">{{ __('Recent ledger transactions') }}</h2></div>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="bg-ink-50/60"><tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Description') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody class="divide-y divide-ink-100">
                                @forelse ($transactions as $tx)
                                    <tr>
                                        <td class="text-ink-500">{{ $tx->created_at->format('M j, Y H:i') }}</td>
                                        <td>{{ $tx->type->label() }}</td>
                                        <td class="max-w-xs truncate">{{ $tx->description }}</td>
                                        <td class="text-right tabular-nums">{{ Money::format($tx->amount, $tx->currency) }}</td>
                                        <td><x-status-badge :status="$tx->status" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-ink-400">{{ __('No transactions.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endcan
        </div>
    </div>
</x-layouts.app>
