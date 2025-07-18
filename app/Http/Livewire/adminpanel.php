<?php

namespace App\Http\Livewire;

use Filament\Pages\Actions;
use Filament\Panel;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

class AdminPanel extends Panel
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Admin';

    protected static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }
}
