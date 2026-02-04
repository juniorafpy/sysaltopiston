<?php

namespace App\Filament\Resources\NotaResource\Pages;

use App\Filament\Resources\NotaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class ViewNota extends ViewRecord
{
    protected static string $resource = NotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->estado === 'Emitida'),
            Actions\Action::make('anular')
                ->label('Anular Nota')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->puedeAnularse())
                ->form([
                    \Filament\Forms\Components\Textarea::make('motivo_anulacion')
                        ->label('Motivo de Anulación')
                        ->required()
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    $record->anular($data['motivo_anulacion']);

                    \Filament\Notifications\Notification::make()
                        ->title('Nota anulada')
                        ->body('La nota ha sido anulada correctamente')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información de la Nota')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tipo_nota')
                                    ->label('Tipo')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'credito' => 'success',
                                        'debito' => 'warning',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'credito' => 'Nota de Crédito',
                                        'debito' => 'Nota de Débito',
                                    }),

                                TextEntry::make('numero_nota')
                                    ->label('Número de Nota'),

                                TextEntry::make('fecha_emision')
                                    ->label('Fecha de Emisión')
                                    ->date('d/m/Y'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('timbrado.numero_timbrado')
                                    ->label('Timbrado'),

                                TextEntry::make('estado')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Emitida' => 'success',
                                        'Anulada' => 'danger',
                                    }),
                            ]),
                    ]),

                Section::make('Factura de Referencia')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('factura.numero_factura')
                                    ->label('Número de Factura'),

                                TextEntry::make('factura.fecha_factura')
                                    ->label('Fecha Factura')
                                    ->date('d/m/Y'),

                                TextEntry::make('factura.total_general')
                                    ->label('Total Factura')
                                    ->money('PYG', locale: 'es_PY'),
                            ]),

                        TextEntry::make('factura.cliente.nombre_completo')
                            ->label('Cliente'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('factura')
                                    ->label('Saldo Actual Factura')
                                    ->formatStateUsing(fn ($record) =>
                                        number_format($record->factura->getSaldoConNotas(), 0, ',', '.') . ' Gs'
                                    )
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('efecto')
                                    ->label('Efecto')
                                    ->badge()
                                    ->color(fn ($state) => $state === 'Resta' ? 'success' : 'warning'),
                            ]),
                    ]),

                Section::make('Motivo')
                    ->schema([
                        TextEntry::make('motivo')
                            ->label('')
                            ->columnSpanFull(),
                    ]),

                Section::make('Detalles de la Nota')
                    ->schema([
                        RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                TextEntry::make('descripcion')
                                    ->label('Descripción'),

                                TextEntry::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric(decimalPlaces: 2),

                                TextEntry::make('precio_unitario')
                                    ->label('Precio Unit.')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('tipo_iva')
                                    ->label('IVA')
                                    ->badge(),

                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('monto_iva')
                                    ->label('IVA')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('PYG', locale: 'es_PY')
                                    ->weight('bold'),
                            ])
                            ->columns(7)
                            ->columnSpanFull(),
                    ]),

                Section::make('Totales')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subtotal_gravado_10')
                                    ->label('Subtotal Gravado 10%')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('total_iva_10')
                                    ->label('IVA 10%')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('subtotal_gravado_5')
                                    ->label('Subtotal Gravado 5%')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('total_iva_5')
                                    ->label('IVA 5%')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('subtotal_exenta')
                                    ->label('Exenta')
                                    ->money('PYG', locale: 'es_PY'),

                                TextEntry::make('monto_total')
                                    ->label('TOTAL')
                                    ->money('PYG', locale: 'es_PY')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),
                            ]),
                    ]),

                Section::make('Observaciones')
                    ->schema([
                        TextEntry::make('observaciones')
                            ->label('')
                            ->columnSpanFull()
                            ->placeholder('Sin observaciones'),
                    ])
                    ->visible(fn ($record) => filled($record->observaciones))
                    ->collapsed(),

                Section::make('Información de Auditoría')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('usuarioAlta.name')
                                    ->label('Creado por'),

                                TextEntry::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->dateTime('d/m/Y H:i:s'),

                                TextEntry::make('usuarioModificacion.name')
                                    ->label('Modificado por')
                                    ->placeholder('N/A'),

                                TextEntry::make('updated_at')
                                    ->label('Última Modificación')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
