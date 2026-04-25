<?php

namespace App\Filament\Resources\RoleResource\Pages;

class EditRole extends \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\EditRole
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
