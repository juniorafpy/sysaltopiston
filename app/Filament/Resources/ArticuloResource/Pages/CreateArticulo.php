<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateArticulo extends CreateRecord
{
    protected static string $resource = ArticuloResource::class;
    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manual')
                ->label('Manual de Usuario')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(route('pdf.manual-usuario.articulo'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_alta'] = auth()->user()->name;
        $data['fec_alta'] = now();
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $this->dispatch('swal:success', message: 'Artículo creado exitosamente.');
    }
}
