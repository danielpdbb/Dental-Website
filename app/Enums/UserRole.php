<?php

namespace App\Enums;

/**
 * The roles a user can hold in the system.
 *
 * Backed by the string stored in the users.role column. Add new roles here and
 * everything (validation, dropdowns, redirects, badges) picks them up.
 */
enum UserRole: string
{
    case Patient = 'patient';
    case Receptionist = 'receptionist';
    case Dentist = 'dentist';
    case Management = 'management';

    /**
     * Human-friendly label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Patient => 'Patient',
            self::Receptionist => 'Receptionist',
            self::Dentist => 'Dentist',
            self::Management => 'Management / Admin',
        };
    }

    /**
     * Roles that may sign in to the /admin area.
     */
    public function isStaff(): bool
    {
        return $this !== self::Patient;
    }

    /**
     * Roles allowed to manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this === self::Management;
    }

    /**
     * Named route a user should land on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Management => 'admin.dashboard',
            default => 'dashboard',
        };
    }

    /**
     * Tailwind classes for the role badge shown in the admin table.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Management => 'bg-brand-navy/10 text-brand-navy',
            self::Dentist => 'bg-brand-blue/10 text-brand-blue',
            self::Receptionist => 'bg-brand-green/10 text-emerald-700',
            self::Patient => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * All roles as [value => label], handy for <select> options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
