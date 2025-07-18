<?php

// App/Filament/Resources/PaymentResource/Pages/ListPayments.php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    // 1. TEMPATKAN WIDGET DI METHOD YANG BENAR
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\PaymentResource\Widgets\PaymentWidgets::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Payments'),
            
            'unpaid' => Tab::make('Unpaid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'unpaid'))
                ->badge(fn () => $this->getModel()::query()->where('status', 'unpaid')->count())
                ->badgeColor('danger'),
                
            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => $this->getModel()::query()->where('status', 'paid')->count())
                ->badgeColor('success'),
                
            'with_dp' => Tab::make('With Down Payment')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('dp', '>', 0))
                ->badge(fn () => $this->getModel()::query()->where('dp', '>', 0)->count())
                ->badgeColor('info'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'approved'))
                ->badge(fn () => $this->getModel()::query()->where('approval_status', 'approved')->count())
                ->badgeColor('success'),
            
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'pending'))
                ->badge(fn () => $this->getModel()::query()->where('approval_status', 'pending')->count())
                ->badgeColor('warning'),
        ];
    }
}