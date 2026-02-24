<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeBanner;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return 'Escritorio';
    }

    public function getSubheading(): ?string
    {
        return 'Panel principal del sistema';
    }

    public function getWidgets(): array
    {
        return [
            WelcomeBanner::class,
            StatsOverview::class,
            AccountWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
