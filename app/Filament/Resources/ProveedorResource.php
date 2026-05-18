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
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ProveedorResource\Pages;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               
             
                        Grid::make(2)
                            ->schema([
                                Select::make('cod_persona')
                                    ->label('Persona')
                                    ->relationship('personas_pro', 'nombres')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                                    ->searchable(['nombres', 'apellidos'])
                                    ->preload()
                                    ->optionsLimit(5)
                                    ->required()
                                    ->placeholder('Buscar persona...')
                                    ->columnSpan(2),

                                Toggle::make('estado')
                                    ->label('Estado Activo')
                                    ->helperText('Desactive para dar de baja al proveedor')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(2),
                            ]),
                

                        Grid::make(2)
                            ->schema([
                                TextInput::make('usuario_alta')
                                    ->label('Registrado por')
                                    ->default(auth()->user()->name)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ,

                                DateTimePicker::make('fec_alta')
                                    ->label('Fecha de Registro')
                                    ->default(now())
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ,
                        ]),
                  
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_proveedor')
                    ->label('#'),

                Tables\Columns\TextColumn::make('personas_pro.nombre_completo')
                    ->label('Nombre del Proveedor')
                    ->searchable(['nombres', 'apellidos'])
                    ->icon('heroicon-o-user')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    })
                    ->weight('medium')
                    ->searchable(),

                Tables\Columns\IconColumn::make('estado')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Registrado por')
                    ->icon('heroicon-o-user-circle'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->icon('heroicon-o-calendar'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportarPdf')
                    ->label('Listado')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn () => route('proveedores.pdf'))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('cod_proveedor', 'desc')
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Acciones')
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
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
