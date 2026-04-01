<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Colors\Color;

class LatestInvoices extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return __('Invoice Terbaru');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('Nomor'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_customer')
                    ->label(__('Pelanggan'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('Total'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }
}
