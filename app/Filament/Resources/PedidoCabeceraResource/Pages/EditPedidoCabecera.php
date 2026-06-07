<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Filament\Resources\PedidoCabeceraResource;
use App\Traits\WithSucursalData;
use Filament\Resources\Pages\EditRecord;

class EditPedidoCabecera extends EditRecord
{
    use WithSucursalData;

    protected static string $resource = PedidoCabeceraResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Verificar si el pedido está anulado o aprobado
        if (in_array($this->record->estado, ['ANULADO', 'APROBADO'])) {
            $this->redirect(static::getResource()::getUrl('index'));
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('No se puede editar')
                ->body('No se pueden editar pedidos anulados o aprobados.')
                ->send();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Inicializar los datos del trait
        $this->initSucursalData();
        $this->initUsuAltaData();
        $this->initEmpleadoData();

        // Agregar los datos calculados al formulario
        $data['nombre_sucursal'] = $this->nombre_sucursal;
        $data['nombre_empleado'] = $this->nombre_empleado;
        $data['usuario_alta'] = $data['usuario_alta'] ?? $this->usuario_alta;

        return $data;
    }    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('manual')
                ->label('Manual Usuario')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->url(route('pdf.manual-usuario.pedido-compra'), shouldOpenInNewTab: true),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Guardar')
                ->color('success'),

            $this->getCancelFormAction()
                ->label('Cancelar')
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
