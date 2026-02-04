<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateArticulo extends CreateRecord
{
    protected static string $resource = ArticuloResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Mutar los datos antes de crear el registro
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que se guarden usuario y fecha de alta si no vienen del form
        if (empty($data['usuario_alta'])) {
            $data['usuario_alta'] = Auth::user()->name;
        }
        if (empty($data['fec_alta'])) {
            $data['fec_alta'] = now();
        }

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

    /**
     * Botones personalizados del formulario
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Guardar')
                ->action('create')
                ->keyBindings(['mod+s'])
                ->color('warning')
                ->icon('heroicon-o-check'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }
}
