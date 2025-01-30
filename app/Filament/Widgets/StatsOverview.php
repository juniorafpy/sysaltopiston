<?php

namespace App\Filament\Widgets;

use App\Models\Modelos;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Model', Modelos::count())
            ->description('Super bien')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success')
            ->chart([6, 4, 9, 5, 3, 0, 7]),
        ];
    }
}
