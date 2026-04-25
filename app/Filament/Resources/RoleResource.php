<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ListRoles as ShieldListRoles;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ViewRole as ShieldViewRole;
use App\Filament\Resources\RoleResource\Pages;

class RoleResource extends BaseRoleResource
{
    public static function getPages(): array
    {
        return [
            'index' => ShieldListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => ShieldViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
