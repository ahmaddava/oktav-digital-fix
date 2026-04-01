<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductionResource;
use App\Filament\Resources\MachineResource;
use App\Filament\Resources\ProductionResource\Widgets\ProductionFilterWidget;
use App\Filament\Resources\ProductionResource\Widgets\ProductionStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Production;

class ListProductions extends ListRecords
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ProductionResource\Widgets\ProductionStats::class,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_machines')
                ->label('Kelola Mesin')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(MachineResource::getUrl('index'))
                ->color('info'),

            Action::make('manage_counters')
                ->label('Pengaturan Counter')
                ->icon('heroicon-o-adjustments-horizontal')
                ->url(ProductionResource::getUrl('counter-manager'))
                ->color('warning'),

            CreateAction::make()
                ->label('Buat Produksi Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })->count();
                }),

            'deadline_urgent' => Tab::make('Deadline Mendesak')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<=', now()->addDays(2)->toDateString())
                    ->whereNotNull('deadline')
                    ->count();
                })
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where(function ($q) {
                        $q->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<=', now()->addDays(2)->toDateString())
                    ->whereNotNull('deadline');
                }),

            'pending' => Tab::make('Pending')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'pending')
                    ->count();
                })
                ->badgeColor('warning')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where(function ($q) {
                        $q->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'pending');
                }),

            'in_progress' => Tab::make('Sedang Diproduksi')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'started')
                    ->count();
                })
                ->badgeColor('info')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where(function ($q) {
                        $q->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'started');
                }),

            'completed' => Tab::make('Selesai Diproduksi')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'completed')
                    ->count();
                })
                ->badgeColor('success')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where(function ($q) {
                        $q->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', 'completed');
                }),

            'overdue' => Tab::make('Terlambat')
                ->badge(function () {
                    return Production::where(function ($query) {
                        $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', now()->toDateString())
                    ->whereNotNull('deadline')
                    ->count();
                })
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where(function ($q) {
                        $q->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', now()->toDateString())
                    ->whereNotNull('deadline');
                }),
        ];
    }
}