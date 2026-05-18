<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ListRoles as ShieldListRoles;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ViewRole as ShieldViewRole;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RoleResource extends BaseRoleResource
{
    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationGroup(): ?string
    {
        return 'Definiciones';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                Forms\Components\Tabs::make('Permissions')
                    ->contained()
                    ->tabs([
                        static::getTabFormComponentForResources(),
                        static::getTabFormComponentForPage(),
                        static::getTabFormComponentForWidget(),
                        static::getTabFormComponentForCustomPermissions(),
                    ])
                    ->columnSpan('full'),
            ])
            ->statePath('data')
            ->operation('edit')
            ->model(static::getModel());
    }

    public static function getTabFormComponentForResources(): Component
    {
        return Forms\Components\Tabs\Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->visible(fn (): bool => (bool) Utils::isResourceEntityEnabled())
            ->badge(static::getResourceTabBadgeCount())
            ->schema([
                Forms\Components\TextInput::make('search_resource')
                    ->label('Buscar permiso')
                    ->placeholder('Ej: APERTURA DE CAJA, usuario, crear...')
                    ->live(onBlur: false)
                    ->debounce(300)
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->extraInputAttributes(['class' => 'mb-4']),
                Forms\Components\Grid::make()
                    ->schema(function (callable $get) {
                        $search = strtolower($get('search_resource') ?? '');

                        $entities = collect(FilamentShield::getResources())
                            ->sortKeys()
                            ->filter(function ($entity) use ($search) {
                                if (empty($search)) return true;
                                $label = strval(FilamentShield::getLocalizedResourceLabel($entity['fqcn']));
                                return str_contains(strtolower($label), $search) || str_contains(strtolower($entity['model']), $search);
                            });

                        // Si hay búsqueda, mostrar lista plana de permisos
                        if (!empty($search)) {
                            $allPermissions = [];
                            foreach ($entities as $entity) {
                                $sectionLabel = strval(FilamentShield::getLocalizedResourceLabel($entity['fqcn']));
                                $perms = static::getResourcePermissionOptions($entity);
                                foreach ($perms as $permName => $permLabel) {
                                    if (str_contains(strtolower($permName), $search) || str_contains(strtolower($permLabel), $search) || str_contains(strtolower($sectionLabel), $search)) {
                                        $allPermissions[$permName] = $permLabel . ' (' . $sectionLabel . ')';
                                    }
                                }
                            }
                            return [
                                Forms\Components\CheckboxList::make('filtered_permissions')
                                    ->label('')
                                    ->options($allPermissions)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ];
                        }

                        // Sin búsqueda: mostrar secciones normales
                        return $entities
                            ->map(function ($entity) {
                                $sectionLabel = strval(FilamentShield::getLocalizedResourceLabel($entity['fqcn']));

                                return Forms\Components\Section::make($sectionLabel)
                                    ->description(fn () => new HtmlString('<span style="word-break: break-word;">' . Utils::showModelPath($entity['fqcn']) . '</span>'))
                                    ->compact()
                                    ->schema([
                                        static::getCheckBoxListComponentForResource($entity),
                                    ])
                                    ->columnSpan(static::shield()->getSectionColumnSpan())
                                    ->collapsible();
                            })
                            ->toArray();
                    })
                    ->columns(static::shield()->getGridColumns()),
            ]);
    }

    public static function getResourceTabBadgeCount(): ?int
    {
        return collect(FilamentShield::getResources())
            ->map(fn ($resource) => count(static::getResourcePermissionOptions($resource)))
            ->sum();
    }

    public static function getResourcePermissionOptions(array $entity): array
    {
        return collect(Utils::getResourcePermissionPrefixes($entity['fqcn']))
            ->flatMap(function ($permission) use ($entity) {
                $name = $permission . '_' . $entity['resource'];
                $label = static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedResourcePermissionLabel($permission)
                    : $name;

                return [
                    $name => $label,
                ];
            })
            ->toArray();
    }

    public static function getCheckBoxListComponentForResource(array $entity): Component
    {
        $permissionsArray = static::getResourcePermissionOptions($entity);

        return static::getCheckboxListFormComponent($entity['resource'], $permissionsArray, true);
    }

    public static function getTabFormComponentForPage(): Component
    {
        $options = static::getPageOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make('pages')
            ->label(__('filament-shield::filament-shield.pages'))
            ->visible(fn (): bool => (bool) Utils::isPageEntityEnabled() && $count > 0)
            ->badge($count)
            ->schema([
                static::getCheckboxListFormComponent('pages_tab', $options),
            ]);
    }

    public static function getTabFormComponentForWidget(): Component
    {
        $options = static::getWidgetOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make('widgets')
            ->label(__('filament-shield::filament-shield.widgets'))
            ->visible(fn (): bool => (bool) Utils::isWidgetEntityEnabled() && $count > 0)
            ->badge($count)
            ->schema([
                static::getCheckboxListFormComponent('widgets_tab', $options),
            ]);
    }

    public static function getTabFormComponentForCustomPermissions(): Component
    {
        $options = static::getCustomPermissionOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make('custom')
            ->label(__('filament-shield::filament-shield.custom'))
            ->visible(fn (): bool => (bool) Utils::isCustomPermissionEntityEnabled() && $count > 0)
            ->badge($count)
            ->schema([
                static::getCheckboxListFormComponent('custom_permissions', $options),
            ]);
    }

    public static function getCheckboxListFormComponent(string $name, array $options, bool $searchable = true): Component
    {
        return Forms\Components\CheckboxList::make($name)
            ->label('')
            ->options(fn (): array => $options)
            ->searchable($searchable)
            ->afterStateHydrated(
                fn (Component $component, string $operation, ?\Illuminate\Database\Eloquent\Model $record) => static::setPermissionStateForRecordPermissions(
                    component: $component,
                    operation: $operation,
                    permissions: $options,
                    record: $record
                )
            )
            ->dehydrated(fn ($state) => ! blank($state))
            ->bulkToggleable()
            ->gridDirection('row')
            ->columns(static::shield()->getCheckboxListColumns())
            ->columnSpan(static::shield()->getCheckboxListColumnSpan());
    }

    public static function setPermissionStateForRecordPermissions(Component $component, string $operation, array $permissions, ?\Illuminate\Database\Eloquent\Model $record): void
    {
        if (in_array($operation, ['edit', 'view'])) {
            if (blank($record)) {
                return;
            }
            if ($component->isVisible() && count($permissions) > 0) {
                $component->state(
                    collect($permissions)
                        ->filter(fn ($value, $key) => $record->checkPermissionTo($key))
                        ->keys()
                        ->toArray()
                );
            }
        }
    }

    public static function getPageOptions(): array
    {
        return collect(FilamentShield::getPages())
            ->flatMap(fn ($page) => [
                $page['permission'] => static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedPageLabel($page['class'])
                    : $page['permission'],
            ])
            ->toArray();
    }

    public static function getWidgetOptions(): array
    {
        return collect(FilamentShield::getWidgets())
            ->flatMap(fn ($widget) => [
                $widget['permission'] => static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedWidgetLabel($widget['class'])
                    : $widget['permission'],
            ])
            ->toArray();
    }

    public static function getCustomPermissionOptions(): ?array
    {
        return FilamentShield::getCustomPermissions()
            ->mapWithKeys(fn ($customPermission) => [
                $customPermission => static::shield()->hasLocalizedPermissionLabels() ? str($customPermission)->headline()->toString() : $customPermission,
            ])
            ->toArray();
    }

    public static function shield(): FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime('d/m/Y'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ShieldListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => ShieldViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
