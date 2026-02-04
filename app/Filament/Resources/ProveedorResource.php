<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Proveedor;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ProveedorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProveedorResource\RelationManagers;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 🎨 SECCIÓN 1: Datos del Proveedor
                Section::make('🏢 Información del Proveedor')
                    ->description('Seleccione la persona que será registrada como proveedor')
                    ->icon('heroicon-o-building-storefront')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Persona asociada
                                Select::make('cod_persona')
                                    ->label('Persona')
                                    ->relationship('personas_pro', 'nombres')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Busque y seleccione la persona registrada')
                                    ->placeholder('Buscar persona...')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombres')
                                            ->required()
                                            ->label('Nombres'),
                                        Forms\Components\TextInput::make('apellidos')
                                            ->label('Apellidos'),
                                        Forms\Components\TextInput::make('ci_ruc')
                                            ->label('CI/RUC')
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading('Crear Nueva Persona')
                                    ->columnSpan(2),

                                // Estado
                                Toggle::make('estado')
                                    ->label('Estado Activo')
                                    ->helperText('Desactive para dar de baja al proveedor')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(2),
                            ]),
                    ]),

                // 🎨 SECCIÓN 2: Información de Auditoría
                Section::make('📋 Información de Registro')
                    ->description('Datos de auditoría del sistema')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('usuario_alta')
                                    ->label('Registrado por')
                                    ->default(auth()->user()->name)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('👤'),

                                DateTimePicker::make('fec_alta')
                                    ->label('Fecha de Registro')
                                    ->default(now())
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->prefix('📅'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_proveedor')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('personas_pro.nombre_completo')
                    ->label('Nombre del Proveedor')
                    ->searchable(['nombres', 'apellidos'])
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    })
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('personas_pro.ci_ruc')
                    ->label('CI/RUC')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('CI/RUC copiado')
                    ->icon('heroicon-o-identification'),

                Tables\Columns\IconColumn::make('estado')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Registrado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-user-circle'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),
            ])
            ->defaultSort('cod_proveedor', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('estado')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos')
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProveedors::route('/'),
            // 'create' => Pages\CreateProveedor::route('/create'), // Deshabilitado - usando modal en ListProveedors
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
