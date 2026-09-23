<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar / mobile navigation. Items only render once their route exists
 * (so later phases light up automatically) and, for the back office, only
 * when the user holds the required permission.
 */
final class Navigation
{
    /**
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public static function customer(): array
    {
        return self::available([
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => 'dashboard'],
            ['label' => 'Wallet', 'route' => 'wallet.index', 'icon' => 'wallet', 'active' => 'wallet.*'],
            ['label' => 'Markets', 'route' => 'markets.index', 'icon' => 'chart', 'active' => 'markets.*'],
            ['label' => 'Trade', 'route' => 'trade.index', 'icon' => 'arrows', 'active' => 'trade.*'],
            ['label' => 'Portfolio', 'route' => 'portfolio.index', 'icon' => 'pie', 'active' => 'portfolio.*'],
            ['label' => 'Transactions', 'route' => 'transactions.index', 'icon' => 'list', 'active' => 'transactions.*'],
            ['label' => 'Deposits', 'route' => 'deposits.index', 'icon' => 'download', 'active' => 'deposits.*'],
            ['label' => 'Withdrawals', 'route' => 'withdrawals.index', 'icon' => 'upload', 'active' => 'withdrawals.*'],
            ['label' => 'KYC Verification', 'route' => 'kyc.show', 'icon' => 'badge', 'active' => 'kyc.*'],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell', 'active' => 'notifications.*'],
            ['label' => 'Security', 'route' => 'account.security', 'icon' => 'shield', 'active' => 'account.security'],
            ['label' => 'Profile', 'route' => 'account.profile', 'icon' => 'user', 'active' => 'account.profile'],
            ['label' => 'Support', 'route' => 'support.index', 'icon' => 'lifebuoy', 'active' => 'support.*'],
        ]);
    }

    /**
     * Compact set for the mobile bottom bar.
     *
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public static function customerMobile(): array
    {
        $keep = ['dashboard', 'wallet.index', 'trade.index', 'portfolio.index', 'transactions.index', 'account.profile'];

        return array_values(array_slice(array_filter(self::customer(), fn (array $item) => in_array($item['route'], $keep, true)), 0, 5));
    }

    /**
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public static function admin(User $user): array
    {
        $items = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'active' => 'admin.dashboard', 'can' => 'admin.access'],
            ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => 'admin.users.*', 'can' => 'users.view'],
            ['label' => 'KYC', 'route' => 'admin.kyc.index', 'icon' => 'badge', 'active' => 'admin.kyc.*', 'can' => 'kyc.view'],
            ['label' => 'Wallets', 'route' => 'admin.wallets.index', 'icon' => 'wallet', 'active' => 'admin.wallets.*', 'can' => 'wallets.view'],
            ['label' => 'Deposits', 'route' => 'admin.deposits.index', 'icon' => 'download', 'active' => 'admin.deposits.*', 'can' => 'deposits.view'],
            ['label' => 'Withdrawals', 'route' => 'admin.withdrawals.index', 'icon' => 'upload', 'active' => 'admin.withdrawals.*', 'can' => 'withdrawals.view'],
            ['label' => 'Transactions', 'route' => 'admin.transactions.index', 'icon' => 'list', 'active' => 'admin.transactions.*', 'can' => 'transactions.view'],
            ['label' => 'Markets', 'route' => 'admin.markets.index', 'icon' => 'chart', 'active' => 'admin.markets.*', 'can' => 'markets.manage'],
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'icon' => 'arrows', 'active' => 'admin.orders.*', 'can' => 'orders.view'],
            ['label' => 'Trades', 'route' => 'admin.trades.index', 'icon' => 'pie', 'active' => 'admin.trades.*', 'can' => 'orders.view'],
            ['label' => 'Investment Products', 'route' => 'admin.plans.index', 'icon' => 'briefcase', 'active' => 'admin.plans.*', 'can' => 'investment-plans.manage'],
            ['label' => 'Fees', 'route' => 'admin.fees.index', 'icon' => 'percent', 'active' => 'admin.fees.*', 'can' => 'fees.manage'],
            ['label' => 'Payment Methods', 'route' => 'admin.payment-methods.index', 'icon' => 'card', 'active' => 'admin.payment-methods.*', 'can' => 'payment-methods.manage'],
            ['label' => 'Support', 'route' => 'admin.support.index', 'icon' => 'lifebuoy', 'active' => 'admin.support.*', 'can' => 'support.view'],
            ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'icon' => 'bell', 'active' => 'admin.notifications.*', 'can' => 'notifications.send'],
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'document', 'active' => 'admin.reports.*', 'can' => 'reports.view'],
            ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'icon' => 'eye', 'active' => 'admin.audit-logs.*', 'can' => 'audit-logs.view'],
            ['label' => 'System Settings', 'route' => 'admin.settings.index', 'icon' => 'cog', 'active' => 'admin.settings.*', 'can' => 'settings.manage'],
        ];

        return self::available(array_values(array_filter($items, fn (array $item) => $user->can($item['can']))));
    }

    /**
     * @param  list<array<string, string>>  $items
     * @return list<array<string, string>>
     */
    private static function available(array $items): array
    {
        return array_values(array_filter($items, fn (array $item) => Route::has($item['route'])));
    }
}
