<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Filament\Resources\PedidoCabeceraResource;
use Filament\Actions;
Use App\Models\Sucursal;
use App\Traits\WithSucursalData;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
class CreatePedidoCabecera extends CreateRecord
{
    use WithSucursalData;
    protected static string $resource = PedidoCabeceraResource::class;
     protected static ?string $title = 'Pedido de Compra';

    public function mount(): void
    {
        parent::mount();

        // 3. Llama al método del Trait para inicializar los datos
        $this->initSucursalData();
        $this->initUsuAltaData();
        $this->initEmpleadoData();

        // 4. Rellena el formulario con los datos ya preparados por el Trait
        $this->form->fill([
            'cod_sucursal' => $this->cod_sucursal,
            'nombre_sucursal' => $this->nombre_sucursal,
            'usuario_alta' => $this->usuario_alta,
            'cod_empleado'=>$this->cod_empleado,
            'nombre_empleado'=>$this->nombre_empleado,
        ]);
    }

    protected function getFormActions(): array
{
    return [
        // Esto crea un único botón que utiliza la acción de crear por defecto.
        $this->getCreateFormAction()->label('Guardar Pedido'),
    ];
}

protected function getRedirectUrl(): string
    {
        // Esto le dice a Filament: "Vuelve a la página de la lista (index)".
        return static::getResource()::getUrl('index');
    }

}

