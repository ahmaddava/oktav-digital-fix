<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Ambil query dasar dari resource, termasuk filter yang aktif
        $baseQuery = InvoiceResource::getEloquentQuery();

        // Clone query untuk masing-masing metode pembayaran
        $cashInvoices = (clone $baseQuery)->where('payment_method', 'cash');
        $transferInvoices = (clone $baseQuery)->where('payment_method', 'transfer');

        // Hitung total dan jumlah invoice
        $cashTotal = $cashInvoices->sum('grand_total');
        $cashCount = $cashInvoices->count();
        
        $transferTotal = $transferInvoices->sum('grand_total');
        $transferCount = $transferInvoices->count();

        return [
            Stat::make('Cash Payments', 'Rp ' . number_format($cashTotal, 0, ',', '.'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->description("{$cashCount} invoices | Rp " . number_format($cashTotal, 0, ',', '.'))
                ->chart([7, 2, 5, 8, 3]),
            
            Stat::make('Transfer Payments', 'Rp ' . number_format($transferTotal, 0, ',', '.'))
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->description("{$transferCount} invoices | Rp " . number_format($transferTotal, 0, ',', '.'))
                ->chart([2, 5, 8, 3, 7]),
        ];
    }
}