<?php

namespace App\Support;

/**
 * Single source of truth for sidebar navigation so the menu and the route
 * guards can never drift apart.
 *
 * Labels are English translation keys; the active locale resolves them at
 * render time. Icons refer to the shared <x-icon> set.
 *
 * Every item declares the module its route is actually guarded by. That field
 * is not optional bookkeeping: the "Production report" link lives in the
 * Production group but its route sits behind `module:reports`, so a Farm Worker
 * or Veterinarian — who have the `production` module but not `reports` — was
 * offered a link that answered 403. An item may also name a single `ability`
 * when the route behind it is finer grained than its module.
 */
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array{label: string, route: string, icon: string, module?: string, ability?: string}>}>
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
                    // Grouped with its sibling reports and, like them, behind
                    // `module:reports` rather than the `production` module.
                    ['label' => 'Production report', 'route' => 'reports.production.index', 'icon' => 'chart'],
                    ['label' => 'Financial report', 'route' => 'reports.financial.index', 'icon' => 'report'],
                    // The audit trail is not a report and is not part of
                    // `module:reports`; it needs its own gate.
                    [
                        'label' => 'Activity trail',
                        'route' => 'activity-logs.index',
                        'icon' => 'history',
                        'ability' => 'audit.view',
                    ],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'settings',
                'items' => [
                    [
                        'label' => 'System settings',
                        'route' => 'settings.index',
                        'icon' => 'settings',
                        'ability' => 'settings.manage',
                    ],
                ],
            ],
        ];
    }

    /**
     * Whether a navigation item owns the current request.
     *
     * A resource entry points at its index route, but the same section is
     * still "current" while the visitor is on a create, edit, show or nested
     * page. Matching the resource prefix keeps the sidebar marker on the
     * section instead of making it disappear as soon as a form opens.
     *
     * @param  array{route: string}  $item
     */
    public static function itemIsActive(array $item): bool
    {
        if (request()->routeIs($item['route'])) {
            return true;
        }

        if (! str_ends_with($item['route'], '.index')) {
            return false;
        }

        $resource = substr($item['route'], 0, -strlen('.index'));

        return request()->routeIs($resource.'.*');
    }

    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array{label: string, route: string, icon: string}>}>
     */
    public static function forUser(mixed $user): array
    {
        if (! $user) {
            return [];
        }

        $canSee = static function (array $item, array $group) use ($user): bool {
            // An item may sit in a group for presentation while being guarded
            // by a narrower module than the group's, so the item's own module
            // wins over the group's.
            if (! $user->canAccessModule($item['module'] ?? $group['key'])) {
                return false;
            }

            if (isset($item['ability']) && ! $user->hasAbility($item['ability'])) {
                return false;
            }

            // Skip entries that are not registered yet so the sidebar can never
            // link to a missing route.
            return Route::has($item['route']);
        };

        return array_values(array_filter(array_map(function (array $group) use ($canSee): ?array {
            $items = array_values(array_filter(
                $group['items'],
                fn (array $item) => $canSee($item, $group),
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
