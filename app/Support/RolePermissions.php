<?php

namespace App\Support;

use App\Enums\RoleName;

/**
 * Single source of truth for which role may do what. Seeded by
 * RolesAndPermissionsSeeder; checked everywhere via Gates/Policies
 * ($user->can('permission.name')).
 */
final class RolePermissions
{
    /** @var array<string, string> permission => description */
    public const PERMISSIONS = [
        'admin.access' => 'Access the back office',
        'users.view' => 'View customer accounts',
        'users.manage' => 'Suspend and reactivate accounts',
        'users.reset-security' => 'Reset a customer\'s 2FA',
        'kyc.view' => 'View KYC submissions and documents',
        'kyc.review' => 'Approve, reject or request more KYC information',
        'wallets.view' => 'View customer wallets and ledgers',
        'wallets.adjust' => 'Make audited balance adjustments',
        'deposits.view' => 'View deposits',
        'deposits.manage' => 'Manage deposit exceptions',
        'withdrawals.view' => 'View withdrawals',
        'withdrawals.review' => 'Approve or reject withdrawals',
        'transactions.view' => 'View all ledger transactions',
        'markets.manage' => 'Configure markets',
        'orders.view' => 'View all orders and trades',
        'orders.manage' => 'Cancel customer orders',
        'investment-plans.manage' => 'Configure investment products',
        'fees.manage' => 'Configure fees',
        'payment-methods.manage' => 'Configure payment methods',
        'support.view' => 'View support tickets',
        'support.respond' => 'Respond to support tickets',
        'notifications.send' => 'Send platform notifications',
        'reports.view' => 'View and export reports',
        'audit-logs.view' => 'View audit logs',
        'settings.manage' => 'Change system settings',
    ];

    /** @return array<string, list<string>> role => permissions */
    public static function matrix(): array
    {
        return [
            RoleName::User->value => [],
            RoleName::Admin->value => array_keys(self::PERMISSIONS),
            RoleName::Support->value => [
                'admin.access', 'users.view', 'kyc.view', 'kyc.review',
                'support.view', 'support.respond', 'deposits.view', 'withdrawals.view',
            ],
            RoleName::Finance->value => [
                'admin.access', 'users.view', 'kyc.view', 'wallets.view', 'wallets.adjust',
                'deposits.view', 'deposits.manage', 'withdrawals.view', 'withdrawals.review',
                'transactions.view', 'fees.manage', 'payment-methods.manage', 'reports.view',
            ],
            RoleName::Trading->value => [
                'admin.access', 'users.view', 'markets.manage', 'orders.view', 'orders.manage',
                'investment-plans.manage', 'fees.manage', 'reports.view',
            ],
        ];
    }

    /** @return list<string> */
    public static function staffRoles(): array
    {
        return [RoleName::Admin->value, RoleName::Support->value, RoleName::Finance->value, RoleName::Trading->value];
    }
}
