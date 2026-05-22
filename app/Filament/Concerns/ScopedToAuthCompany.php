<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Scope a Filament resource's base query to the authenticated admin's company.
 *
 * Define a COMPANY_SCOPE constant on the resource class:
 *   'direct'      — model has company_id column (default)
 *   'via_user'    — model has user_id → users.company_id
 *   'via_location'— model has location_id → locations.company_id
 */
trait ScopedToAuthCompany
{
    public static function getEloquentQuery(): Builder
    {
        return static::applyCompanyScope(parent::getEloquentQuery());
    }

    protected static function applyCompanyScope(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return $query;
        }

        $companyId = $user->company_id;
        $scope     = defined('static::COMPANY_SCOPE') ? static::COMPANY_SCOPE : 'direct';

        return match ($scope) {
            'via_user'     => $query->whereHas('user', fn($q) => $q->where('company_id', $companyId)),
            'via_location' => $query->whereHas('location', fn($q) => $q->where('company_id', $companyId)),
            default        => $query->where('company_id', $companyId),
        };
    }
}
