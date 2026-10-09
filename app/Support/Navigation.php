<?php

namespace App\Support;

/**
 * Single source of truth for sidebar navigation so the menu and the route
 * guards can never drift apart.
 *
 * Labels are English translation keys; the active locale resolves them at
 * render time. Icons refer to the shared <x-icon> set.
 */
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array{label: string, route: string, icon: string, ability?: string, badge?: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'items' => [
                    ['label' => 'Overview', 'route' => 'dashboard', 'icon' => 'dashboard'],
                ],
            ],
            [
                'key' => 'farm',
                'label' => 'Farm',
                'icon' => 'farm',
                'items' => [
                    ['label' => 'Farm', 'route' => 'farms.index', 'icon' => 'farm'],
                    ['label' => 'Barns', 'route' => 'barns.index', 'icon' => 'barn'],
                    ['label' => 'Livestock', 'route' => 'animals.index', 'icon' => 'cow'],
                    ['label' => 'Breeds', 'route' => 'breeds.index', 'icon' => 'tag'],
                ],
            ],
            [
                'key' => 'feed',
                'label' => 'Feed & Inventory',
                'icon' => 'inventory',
                'items' => [
                    ['label' => 'Feed items', 'route' => 'feed-items.index', 'icon' => 'feed'],
                    ['label' => 'Suppliers', 'route' => 'suppliers.index', 'icon' => 'truck'],
                    ['label' => 'Purchase orders', 'route' => 'purchase-orders.index', 'icon' => 'order'],
                    ['label' => 'Goods receipts', 'route' => 'goods-receipts.index', 'icon' => 'box'],
                    ['label' => 'Inventory', 'route' => 'inventory.index', 'icon' => 'inventory'],
                    ['label' => 'Feed consumption', 'route' => 'feed-consumptions.index', 'icon' => 'scale'],
                ],
            ],
            [
                'key' => 'health',
                'label' => 'Livestock health',
                'icon' => 'health',
                'items' => [
                    ['label' => 'Health records', 'route' => 'health-records.index', 'icon' => 'health'],
                    ['label' => 'Vaccinations', 'route' => 'vaccinations.index', 'icon' => 'syringe'],
                    ['label' => 'Treatments', 'route' => 'treatments.index', 'icon' => 'pill'],
                ],
            ],
            [
                'key' => 'breeding',
                'label' => 'Breeding',
                'icon' => 'breeding',
                'items' => [
                    ['label' => 'Breeding records', 'route' => 'breeding-records.index', 'icon' => 'breeding'],
                    ['label' => 'Pregnancy checks', 'route' => 'pregnancy-checks.index', 'icon' => 'check'],
                    ['label' => 'Births', 'route' => 'birth-records.index', 'icon' => 'infant'],
                ],
            ],
            [
                'key' => 'production',
                'label' => 'Production',
                'icon' => 'milk',
                'items' => [
                    ['label' => 'Milk production', 'route' => 'milk-productions.index', 'icon' => 'milk'],
                    ['label' => 'Production report', 'route' => 'reports.production.index', 'icon' => 'chart'],
                ],
            ],
            [
                'key' => 'sales',
                'label' => 'Sales',
                'icon' => 'sales',
                'items' => [
                    ['label' => 'Customers', 'route' => 'customers.index', 'icon' => 'users'],
                    ['label' => 'Sales orders', 'route' => 'sales-orders.index', 'icon' => 'order'],
                    ['label' => 'Invoices', 'route' => 'invoices.index', 'icon' => 'invoice'],
                    ['label' => 'Payments', 'route' => 'payments.index', 'icon' => 'cash'],
                ],
            ],
            [
                'key' => 'finance',
                'label' => 'Finance',
                'icon' => 'wallet',
                'items' => [
                    ['label' => 'Income', 'route' => 'incomes.index', 'icon' => 'income'],
                    ['label' => 'Expenses', 'route' => 'expenses.index', 'icon' => 'expense'],
                    ['label' => 'Cash flow', 'route' => 'cash-transactions.index', 'icon' => 'wallet'],
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'icon' => 'chart',
                'items' => [
                    ['label' => 'Farm report', 'route' => 'reports.animal.index', 'icon' => 'report'],
                    ['label' => 'Feed report', 'route' => 'reports.feed.index', 'icon' => 'report'],
                    ['label' => 'Financial report', 'route' => 'reports.financial.index', 'icon' => 'report'],
                    ['label' => 'Activity trail', 'route' => 'activity-logs.index', 'icon' => 'history'],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'settings',
                'items' => [
                    ['label' => 'System settings', 'route' => 'settings.index', 'icon' => 'settings'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array{label: string, route: string, icon: string}>}>
     */
    public static function forUser(mixed $user): array
    {
        if (! $user) {
            return [];
        }

        return array_values(array_filter(array_map(function (array $group) use ($user): ?array {
            $items = array_values(array_filter(
                $group['items'],
                // Skip entries that are not registered yet so the sidebar can never
                // link to a missing route.
                fn (array $item) => $user->canAccessModule($group['key']) && Route::has($item['route']),
            ));

            return $items === [] ? null : [
                'key' => $group['key'],
                'label' => __($group['label']),
                'icon' => $group['icon'],
                'items' => array_map(
                    fn (array $item) => [...$item, 'label' => __($item['label'])],
                    $items,
                ),
            ];
        }, self::groups())));
    }
}
