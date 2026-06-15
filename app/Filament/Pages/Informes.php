<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;

class Informes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Informes';
    protected static ?string $navigationLabel = 'Reporte Pedido Compra';
    protected static ?string $title = 'Reporte Pedido Compra';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'informes';
    protected static string $view = 'filament.pages.informes';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros del Reporte')
                    ->schema([
                        Select::make('filtro_rapido')
                            ->label('Filtro Rápido')
                            ->options([
                                'hoy' => 'Hoy',
                                'esta_semana' => 'Esta Semana',
                                'este_mes' => 'Este Mes',
                                'mes_pasado' => 'Mes Pasado',
                                'personalizado' => 'Personalizado',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state === 'personalizado') {
                                    $set('fecha_desde', null);
                                    $set('fecha_hasta', null);
                                    return;
                                }

                                $now = now();
                                $set('fecha_desde', match ($state) {
                                    'hoy' => $now->format('Y-m-d'),
                                    'esta_semana' => $now->startOfWeek()->format('Y-m-d'),
                                    'este_mes' => $now->startOfMonth()->format('Y-m-d'),
                                    'mes_pasado' => $now->subMonth()->startOfMonth()->format('Y-m-d'),
                                });

                                $now = now();
                                $set('fecha_hasta', match ($state) {
                                    'hoy' => $now->format('Y-m-d'),
                                    'esta_semana' => $now->endOfWeek()->format('Y-m-d'),
                                    'este_mes' => $now->endOfMonth()->format('Y-m-d'),
                                    'mes_pasado' => $now->subMonth()->endOfMonth()->format('Y-m-d'),
                                });
                            }),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('fecha_desde')
                                    ->label('Fecha desde')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                DatePicker::make('fecha_hasta')
                                    ->label('Fecha hasta')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                Select::make('estado')
                                    ->label('Estado')
                                    ->options([
                                        'TODOS' => 'Todos',
                                        'PENDIENTE' => 'Pendientes',
                                        'APROBADO' => 'Aprobados',
                                        'COMPLETADO' => 'Completados',
                                        'ANULADO' => 'Anulados',
                                    ])
                                    ->default('TODOS'),
                            ]),
                    ])
                    ->footerActions([
                        Action::make('generar')
                            ->label('Generar Reporte')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('primary')
                            ->url(fn (Get $get) => route('informes.pedidos-compra.pdf', [
                                'fecha_desde' => $get('fecha_desde'),
                                'fecha_hasta' => $get('fecha_hasta'),
                                'estado' => $get('estado') ?? 'TODOS',
                            ]))
                            ->openUrlInNewTab(),
                    ])
                    ->footerActionsAlignment(Alignment::Right),
            ])
            ->statePath('data');
    }
}
