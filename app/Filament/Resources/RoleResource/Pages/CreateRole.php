<?php

namespace App\Filament\Resources\RoleResource\Pages;

class CreateRole extends \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\CreateRole
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
