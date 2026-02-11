<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCliente extends ViewRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Cliente')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('nro_documento')
                                ->label('CI/RUC')
                                ->badge()
                                ->color('primary'),

                            Infolists\Components\TextEntry::make('tipo_persona')
                                ->label('Tipo de Persona')
                                ->getStateUsing(fn ($record) => $record->ind_juridica ? 'Persona Jurídica' : 'Persona Física')
                                ->badge()
                                ->color(fn ($record) => $record->ind_juridica ? 'success' : 'info'),

                            Infolists\Components\IconEntry::make('ind_activo')
                                ->label('Estado')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Datos Personales')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('nombres')
                                ->label('Nombres')
                                ->visible(fn ($record) => $record->ind_fisica),

                            Infolists\Components\TextEntry::make('apellidos')
                                ->label('Apellidos')
                                ->visible(fn ($record) => $record->ind_fisica),

                            Infolists\Components\TextEntry::make('razon_social')
                                ->label('Razón Social')
                                ->visible(fn ($record) => $record->ind_juridica)
                                ->columnSpan(2),

                            Infolists\Components\TextEntry::make('sexo')
                                ->label('Sexo')
                                ->formatStateUsing(fn ($state) => $state === 'M' ? 'Masculino' : 'Femenino')
                                ->visible(fn ($record) => $record->ind_fisica),

                            Infolists\Components\TextEntry::make('fec_nacimiento')
                                ->label('Fecha de Nacimiento')
                                ->date('d/m/Y')
                                ->visible(fn ($record) => $record->ind_fisica),

                            Infolists\Components\TextEntry::make('edad')
                                ->label('Edad')
                                ->suffix(' años')
                                ->visible(fn ($record) => $record->ind_fisica),

                            Infolists\Components\TextEntry::make('estadoCivil.descripcion')
                                ->label('Estado Civil')
                                ->visible(fn ($record) => $record->ind_fisica),
                        ]),
                    ]),

                Infolists\Components\Section::make('Contacto y Ubicación')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope')
                                ->copyable(),

                            Infolists\Components\TextEntry::make('direccion')
                                ->label('Dirección')
                                ->icon('heroicon-o-map-pin'),

                            Infolists\Components\TextEntry::make('pais.nombre')
                                ->label('País')
                                ->icon('heroicon-o-globe-alt'),

                            Infolists\Components\TextEntry::make('departamento.descripcion')
                                ->label('Departamento')
                                ->icon('heroicon-o-map'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Información Comercial')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('facturas_count')
                                ->label('Total Facturas')
                                ->getStateUsing(fn ($record) => $record->facturas()->count())
                                ->badge()
                                ->color('info'),

                            Infolists\Components\TextEntry::make('total_facturado')
                                ->label('Total Facturado')
                                ->getStateUsing(function ($record) {
                                    $total = $record->facturas()->sum('total');
                                    return 'Gs. ' . number_format($total, 0, ',', '.');
                                })
                                ->badge()
                                ->color('success'),
                        ]),
                    ]),
            ]);
    }
}
