<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'kyc' => ['nullable', Rule::enum(KycStatus::class)],
            'role' => ['nullable', Rule::enum(RoleName::class)],
        ]);

        $users = User::query()
            ->with(['kycProfile', 'roles'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('uuid', $search));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->role($role))
            ->when($filters['kyc'] ?? null, function ($query, string $kyc) {
                $kyc === KycStatus::NotSubmitted->value
                    ? $query->where(fn ($q) => $q->doesntHave('kycProfile')->orWhereHas('kycProfile', fn ($p) => $p->where('status', $kyc)))
                    : $query->whereHas('kycProfile', fn ($p) => $p->where('status', $kyc));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'filters' => $filters]);
    }

    public function show(User $user, WalletService $wallets): View
    {
        $this->authorize('view', $user);

        $user->load(['kycProfile', 'roles', 'wallets']);

        return view('admin.users.show', [
            'user' => $user,
            'balances' => request()->user()->can('wallets.view') ? $wallets->balances($user) : null,
            'transactions' => request()->user()->can('transactions.view')
                ? $user->walletTransactions()->latest()->limit(15)->get()
                : collect(),
        ]);
    }
}
