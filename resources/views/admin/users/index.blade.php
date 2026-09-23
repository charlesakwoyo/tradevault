@php use App\Enums\KycStatus; use App\Enums\RoleName; use App\Enums\UserStatus; @endphp

<x-layouts.app :title="__('Users')" area="admin">
    <form method="GET" class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div class="lg:col-span-2">
            <label for="search" class="form-label">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, email, phone or ID') }}" class="form-input">
        </div>
        <div>
            <label for="status" class="form-label">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-input">
                <option value="">{{ __('Any') }}</option>
                @foreach (UserStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? null) === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kyc" class="form-label">{{ __('KYC') }}</label>
            <select id="kyc" name="kyc" class="form-input">
                <option value="">{{ __('Any') }}</option>
                @foreach (KycStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['kyc'] ?? null) === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <div class="flex-1">
                <label for="role" class="form-label">{{ __('Role') }}</label>
                <select id="role" name="role" class="form-input">
                    <option value="">{{ __('Any') }}</option>
                    @foreach (RoleName::cases() as $r)
                        <option value="{{ $r->value }}" @selected(($filters['role'] ?? null) === $r->value)>{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary self-end">{{ __('Filter') }}</button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead class="bg-ink-50/60"><tr><th>{{ __('User') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Country') }}</th><th>{{ __('Roles') }}</th><th>{{ __('KYC') }}</th><th>{{ __('Status') }}</th><th>{{ __('Joined') }}</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($users as $u)
                        <tr class="hover:bg-ink-50/60">
                            <td>
                                <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-ink-900 hover:text-brand-700">{{ $u->name }}</a>
                                <p class="text-xs text-ink-400">{{ $u->email }} @unless ($u->hasVerifiedEmail()) · <span class="text-amber-600">{{ __('unverified') }}</span> @endunless</p>
                            </td>
                            <td>{{ $u->phone }}</td>
                            <td>{{ $u->country }}</td>
                            <td class="text-xs">{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td><x-status-badge :status="$u->kycStatus()" /></td>
                            <td><x-status-badge :status="$u->status" /></td>
                            <td class="text-ink-500">{{ $u->created_at->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-ink-400">{{ __('No users match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
