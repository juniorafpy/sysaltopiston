<?php

namespace App\Filament\Resources\PaisResource\Pages;

use App\Filament\Resources\PaisResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePais extends CreateRecord
{
    protected static string $resource = PaisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_alta'] = Auth::user()->name;
        $data['fec_alta'] = now();

        return $data;
    }

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
