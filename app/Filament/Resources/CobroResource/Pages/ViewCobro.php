<?php

namespace App\Filament\Resources\CobroResource\Pages;

use App\Filament\Resources\CobroResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCobro extends ViewRecord
{
    protected static string $resource = CobroResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General del Cobro')
                    ->schema([
                        Infolists\Components\TextEntry::make('cod_cobro')
                            ->label('N° Cobro'),
                        Infolists\Components\TextEntry::make('fecha_cobro')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('cliente.nombre_completo')
                            ->label('Cliente'),
                        Infolists\Components\TextEntry::make('aperturaCaja.cod_apertura')
                            ->label('Apertura de Caja'),
                        Infolists\Components\TextEntry::make('monto_total')
                            ->label('Monto Total')
                            ->money('PYG', divideBy: 1)
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Facturas y Cuotas Cobradas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('factura.numero_factura')
                                    ->label('Factura'),
                                Infolists\Components\TextEntry::make('numero_cuota')
                                    ->label('N° Cuota')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('monto_cuota')
                                    ->label('Monto')
                                    ->money('PYG', divideBy: 1),
                                Infolists\Components\TextEntry::make('factura.condicionCompra.descripcion')
                                    ->label('Condición'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Formas de Pago Utilizadas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('formasPago')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('formaCobro.descripcion')
                                    ->label('Tipo')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'Efectivo' => 'success',
                                        'Tarjeta de Crédito' => 'warning',
                                        'Tarjeta de Débito' => 'info',
                                        'Cheque' => 'danger',
                                        'Transferencia' => 'primary',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('monto')
                                    ->label('Monto')
                                    ->money('PYG', divideBy: 1),
                                Infolists\Components\TextEntry::make('entidadBancaria.nombre')
                                    ->label('Banco')
                                    ->visible(fn ($record) => $record->cod_entidad_bancaria !== null),
                                Infolists\Components\TextEntry::make('tipoTarjeta.descripcion')
                                    ->label('Tipo Tarjeta')
                                    ->visible(fn ($record) => $record->cod_tipo_tarjeta !== null),
                                Infolists\Components\TextEntry::make('procesadora.descripcion')
                                    ->label('Procesadora')
                                    ->visible(fn ($record) => $record->cod_procesadora !== null),
                                Infolists\Components\TextEntry::make('numero_voucher')
                                    ->label('N° Voucher')
                                    ->visible(fn ($record) => $record->numero_voucher !== null),
                                Infolists\Components\TextEntry::make('numero_cheque')
                                    ->label('N° Cheque')
                                    ->visible(fn ($record) => $record->numero_cheque !== null),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Información de Registro')
                    ->schema([
                        Infolists\Components\TextEntry::make('usuario_alta')
                            ->label('Registrado por'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
