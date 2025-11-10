<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewFactura extends ViewRecord
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->estado === 'Emitida'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Factura')
                    ->schema([
                        Infolists\Components\TextEntry::make('numero_factura')
                            ->label('Número de Factura'),

                        Infolists\Components\TextEntry::make('timbrado.numero_timbrado')
                            ->label('Timbrado'),

                        Infolists\Components\TextEntry::make('fecha_factura')
                            ->label('Fecha')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('cliente.nombre_completo')
                            ->label('Cliente'),

                        Infolists\Components\TextEntry::make('cliente.nro_documento')
                            ->label('RUC/CI Cliente'),

                        Infolists\Components\TextEntry::make('condicion_venta')
                            ->label('Condición de Venta')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Contado' => 'success',
                                'Crédito' => 'warning',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Emitida' => 'success',
                                'Anulada' => 'danger',
                                'Pagada' => 'info',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull()
                            ->placeholder('Sin observaciones'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Detalles')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('detalles')
                            ->schema([
                                Infolists\Components\TextEntry::make('descripcion')
                                    ->label('Descripción'),

                                Infolists\Components\TextEntry::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric(decimalPlaces: 2),

                                Infolists\Components\TextEntry::make('precio_unitario')
                                    ->label('Precio Unit.')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('porcentaje_descuento')
                                    ->label('% Desc.')
                                    ->suffix('%'),

                                Infolists\Components\TextEntry::make('tipo_iva')
                                    ->label('IVA')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('monto_iva')
                                    ->label('IVA')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('total')
                                    ->label('Total')
                                    ->money('PYG', divideBy: 1)
                                    ->weight('bold'),
                            ])
                            ->columns(8)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Totales')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('subtotal_gravado_10')
                                    ->label('Gravado 10%')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('total_iva_10')
                                    ->label('IVA 10%')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('subtotal_gravado_5')
                                    ->label('Gravado 5%')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('total_iva_5')
                                    ->label('IVA 5%')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('subtotal_exenta')
                                    ->label('Exentas')
                                    ->money('PYG', divideBy: 1),

                                Infolists\Components\TextEntry::make('total_general')
                                    ->label('TOTAL GENERAL')
                                    ->money('PYG', divideBy: 1)
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Información Relacionada')
                    ->schema([
                        Infolists\Components\TextEntry::make('presupuestoVenta.id')
                            ->label('Presupuesto Nro.')
                            ->placeholder('No relacionado')
                            ->url(fn ($record) => $record->presupuesto_venta_id
                                ? route('filament.admin.resources.presupuesto-ventas.view', ['record' => $record->presupuesto_venta_id])
                                : null),

                        Infolists\Components\TextEntry::make('ordenServicio.id')
                            ->label('Orden de Servicio Nro.')
                            ->placeholder('No relacionado')
                            ->url(fn ($record) => $record->orden_servicio_id
                                ? route('filament.admin.resources.orden-servicios.view', ['record' => $record->orden_servicio_id])
                                : null),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Auditoría')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i:s'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Modificación')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
