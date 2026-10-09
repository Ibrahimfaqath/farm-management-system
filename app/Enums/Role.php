<?php

namespace App\Enums;

/**
 * Application roles. A single role per user keeps the MVP simple while still
 * reflecting real farm responsibilities.
 */
enum Role: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Worker = 'worker';
    case Veterinarian = 'veterinarian';
    case Finance = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Manager => __('Farm Manager'),
            self::Worker => __('Farm Worker'),
            self::Veterinarian => __('Veterinarian'),
            self::Finance => __('Finance / Admin'),
        };
    }

    /**
     * Module keys this role may open. Used by navigation and route middleware.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        return match ($this) {
            self::Owner => ['dashboard', 'farm', 'feed', 'health', 'breeding', 'production', 'sales', 'finance', 'reports', 'settings'],
            self::Manager => ['dashboard', 'farm', 'feed', 'health', 'breeding', 'production', 'reports', 'sales'],
            self::Worker => ['dashboard', 'farm', 'production'],
            self::Veterinarian => ['dashboard', 'farm', 'health', 'breeding', 'production'],
            self::Finance => ['dashboard', 'sales', 'finance', 'reports'],
        };
    }

    /**
     * Fine grained abilities keyed by ability name.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Owner => [
                'feed.consume', 'feed.adjust', 'feed.receive', 'feed.purchase',
                'animal.manage', 'animal.exit',
                'health.manage', 'breeding.manage', 'production.record',
                'sales.manage', 'invoice.manage', 'payment.record',
                'finance.manage', 'report.view', 'settings.manage', 'audit.view',
            ],
            self::Manager => [
                'feed.consume', 'feed.adjust', 'feed.receive', 'feed.purchase',
                'animal.manage', 'animal.exit',
                'health.manage', 'breeding.manage', 'production.record',
                'sales.manage', 'invoice.manage', 'payment.record',
                'report.view',
            ],
            self::Worker => ['feed.consume', 'production.record'],
            self::Veterinarian => ['health.manage', 'breeding.manage'],
            self::Finance => [
                'sales.manage', 'invoice.manage', 'payment.record',
                'finance.manage', 'report.view', 'audit.view',
            ],
        };
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities(), true);
    }

    public function canAccessModule(string $module): bool
    {
        return in_array($module, $this->modules(), true);
    }
}
