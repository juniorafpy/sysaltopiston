<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateArticulo extends CreateRecord
{
    protected static string $resource = ArticuloResource::class;

    /**
     * Mutar los datos antes de crear el registro
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar usuario y fecha de alta
        $data['usuario_alta'] = Auth::user()->name;
        $data['fec_alta'] = now();

        return $data;
    }

    /**
     * Redireccionar después de crear
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Mensaje de éxito personalizado
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Artículo creado exitosamente';
    }
}
