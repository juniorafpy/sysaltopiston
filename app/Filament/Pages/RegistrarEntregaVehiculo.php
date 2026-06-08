<?php

namespace App\Filament\Pages;

use App\Forms\Components\SignaturePad;
use App\Models\EntregaVehiculo;
use App\Models\OrdenServicio;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class RegistrarEntregaVehiculo extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static string $view = 'filament.pages.entrega-vehiculo';
    protected static ?string $slug = 'entrega-vehiculo';
    protected static ?string $title = 'Nueva Entrega de Vehículo';
    protected static ?string $navigationLabel = 'Nueva Entrega';
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
                Section::make('Registrar Entrega de Vehículo')
                    ->schema([
                        Select::make('orden_servicio_id')
                            ->label('Orden de Servicio')
                            ->placeholder('Seleccione una OS finalizada y facturada...')
                            ->options(fn() => OrdenServicio::where('facturado', true)
                                ->whereDoesntHave('entregaVehiculo')
                                ->get()
                                ->mapWithKeys(fn($os) => [
                                    $os->id => "OS #{$os->id} - " . ($os->cliente?->nombre_completo ?? 'Sin cliente')
                                ]))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    $set('cliente_nombre', null);
                                    $set('cliente_documento', null);
                                    $set('vehiculo_matricula', null);
                                    $set('vehiculo_marca', null);
                                    $set('vehiculo_modelo', null);
                                    $set('vehiculo_anio', null);
                                    $set('vehiculo_kilometraje', null);
                                    return;
                                }

                                $os = OrdenServicio::with([
                                    'cliente.persona',
                                    'recepcionVehiculo.vehiculo.marca',
                                    'recepcionVehiculo.vehiculo.modelo',
                                    'presupuestoVenta.recepcionVehiculo.vehiculo.marca',
                                    'presupuestoVenta.recepcionVehiculo.vehiculo.modelo',
                                    'diagnostico.recepcionVehiculo.vehiculo.marca',
                                    'diagnostico.recepcionVehiculo.vehiculo.modelo',
                                    'presupuestoVenta.diagnostico.recepcionVehiculo.vehiculo.marca',
                                    'presupuestoVenta.diagnostico.recepcionVehiculo.vehiculo.modelo',
                                ])->find($state);

                                if ($os) {
                                    $set('cliente_nombre', $os->cliente?->nombre_completo);
                                    $set('cliente_documento', $os->cliente?->persona?->nro_documento);

                                    $rv = $os->recepcionVehiculo
                                        ?? $os->presupuestoVenta?->recepcionVehiculo
                                        ?? $os->diagnostico?->recepcionVehiculo
                                        ?? $os->presupuestoVenta?->diagnostico?->recepcionVehiculo;

                                    if ($rv) {
                                        $v = $rv->vehiculo;
                                        $set('vehiculo_matricula', $v?->matricula);
                                        $set('vehiculo_marca', $v?->marca?->descripcion);
                                        $set('vehiculo_modelo', $v?->modelo?->descripcion);
                                        $set('vehiculo_anio', $v?->anio);
                                        $set('vehiculo_kilometraje', $rv->kilometraje);
                                    }
                                }
                            }),

                        Section::make('Información del Vehículo y Cliente')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Placeholder::make('cliente_nombre')
                                            ->label('Cliente')
                                            ->content(fn(Get $get) => $get('cliente_nombre')
                                                ? new HtmlString('<span class="font-semibold text-gray-900 dark:text-gray-100">' . e($get('cliente_nombre')) . '</span>')
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                        Placeholder::make('vehiculo_matricula')
                                            ->label('Matrícula / Chapa')
                                            ->content(fn(Get $get) => $get('vehiculo_matricula')
                                                ? new HtmlString('<span class="font-medium text-gray-800 dark:text-gray-200">' . e($get('vehiculo_matricula')) . '</span>')
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                        Placeholder::make('vehiculo_marca')
                                            ->label('Marca')
                                            ->content(fn(Get $get) => $get('vehiculo_marca')
                                                ? e($get('vehiculo_marca'))
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                        Placeholder::make('vehiculo_modelo')
                                            ->label('Modelo')
                                            ->content(fn(Get $get) => $get('vehiculo_modelo')
                                                ? e($get('vehiculo_modelo'))
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                        Placeholder::make('vehiculo_anio')
                                            ->label('Año')
                                            ->content(fn(Get $get) => $get('vehiculo_anio')
                                                ? e($get('vehiculo_anio'))
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                        Placeholder::make('vehiculo_kilometraje')
                                            ->label('Kilometraje de Ingreso')
                                            ->content(fn(Get $get) => $get('vehiculo_kilometraje')
                                                ? new HtmlString('<span class="font-mono font-medium">' . e(number_format((int) $get('vehiculo_kilometraje'), 0, ',', '.')) . ' km</span>')
                                                : new HtmlString('<span class="text-gray-400 italic">—</span>')),
                                    ])
                                    ->columns(3),
                            ])
                            ->collapsible(false)
                            ->compact(),

                        Toggle::make('recibe_titular')
                            ->label('El cliente titular retira el vehículo')
                            ->helperText('Al activar, los datos de recepción se completarán automáticamente con la información del cliente.')
                            ->live()
                            ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                                if ($state) {
                                    $set('persona_recibe', $get('cliente_nombre'));
                                    $set('documento_recibe', $get('cliente_documento'));
                                } else {
                                    $set('persona_recibe', null);
                                    $set('documento_recibe', null);
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('fecha_entrega')
                                    ->label('Fecha y hora de entrega')
                                    ->default(now())
                                    ->required()
                                    ->seconds(false),
                                TextInput::make('kilometraje_salida')
                                    ->label('Kilometraje de salida')
                                    ->numeric()
                                    ->required()
                                    ->rules(function (Get $get) {
                                        $kmIngreso = $get('vehiculo_kilometraje');
                                        if ($kmIngreso !== null && $kmIngreso !== '') {
                                            return ['gte:' . (int) $kmIngreso];
                                        }
                                        return [];
                                    })
                                    ->validationMessages([
                                        'gte' => 'El kilometraje de salida no puede ser menor al de ingreso.',
                                    ])
                                    ->suffix('km'),
                                TextInput::make('persona_recibe')
                                    ->label('Persona que recibe')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn(Get $get): bool => (bool) $get('recibe_titular')),
                                TextInput::make('documento_recibe')
                                    ->label('Documento (CI/RUC)')
                                    ->maxLength(50)
                                    ->disabled(fn(Get $get): bool => (bool) $get('recibe_titular')),
                            ]),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),

                        SignaturePad::make('firma')
                            ->label('Firma del Cliente'),
                    ])
                    ->footerActions([
                        Action::make('registrar')
                            ->label('Registrar Entrega')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->action(function () {
                                $data = $this->form->getState();

                                $exists = EntregaVehiculo::where('orden_servicio_id', $data['orden_servicio_id'])->exists();
                                if ($exists) {
                                    Notification::make()
                                        ->title('Esta orden de servicio ya tiene una entrega registrada.')
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    return;
                                }

                                $allData = array_merge($this->data, $data);

                                EntregaVehiculo::create([
                                    'orden_servicio_id' => $allData['orden_servicio_id'],
                                    'fecha_entrega' => $allData['fecha_entrega'],
                                    'persona_recibe' => $allData['persona_recibe'] ?? null,
                                    'documento_recibe' => $allData['documento_recibe'] ?? null,
                                    'kilometraje_salida' => $allData['kilometraje_salida'],
                                    'observaciones' => $allData['observaciones'] ?? null,
                                    'recibe_titular' => $allData['recibe_titular'] ?? false,
                                    'firma' => $allData['firma'] ?? null,
                                    'usuario_alta' => auth()->user()?->name ?? 'Sistema',
                                    'fec_alta' => now(),
                                ]);

                                Notification::make()
                                    ->title('Entrega registrada correctamente.')
                                    ->success()
                                    ->send();

                                $this->form->fill();
                            }),
                    ])
                    ->footerActionsAlignment(Alignment::Right),
            ])
            ->statePath('data');
    }
}
