<?php

namespace App\Filament\Resources\RoleResource\Pages;

class CreateRole extends \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\CreateRole
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Fusionar permisos filtrados con los normales
        if (isset($data['filtered_permissions']) && is_array($data['filtered_permissions'])) {
            foreach ($data['filtered_permissions'] as $filteredPerm) {
                $permName = explode(' (', $filteredPerm)[0];
                $data[$permName] = true;
            }
        }

        return parent::mutateFormDataBeforeCreate($data);
    }
}
