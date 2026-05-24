<?php

namespace App\Filament\Exports;

use App\Models\Mecanico;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class MecanicoExporter extends Exporter
{
    protected static ?string $model = Mecanico::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('cod_mecanico')
                ->label('Código'),
            ExportColumn::make('empleado.persona.nro_documento')
                ->label('CI'),
            ExportColumn::make('empleado.persona.nombres')
                ->label('Nombre')
                ->getStateUsing(function ($record) {
                    $persona = $record->empleado?->persona;
                    if ($persona) {
                        return $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                    }
                    return '-';
                }),
            ExportColumn::make('empleado.cargo.descripcion')
                ->label('Cargo'),
            ExportColumn::make('estado')
                ->label('Estado')
                ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo'),
            ExportColumn::make('usuario_alta')
                ->label('Usuario Alta'),
            ExportColumn::make('fec_alta')
                ->label('Fecha Alta'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your mecanico export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
