<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class LowStockProducts extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('type', 'digital_print')
                    ->where('stock', '<', 100)
                    ->orderBy('stock', 'asc')
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable(),
                    
                TextColumn::make('stock')
                    ->label('Stok Tersisa')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state < 50 ? 'danger' : 'warning'),
                    
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->actions([
                Action::make('edit')
                    ->url(fn (Product $record): string => route('filament.admin.resources.products.edit', $record))
            ]);
    }
    
    public function getHeading(): string
    {
        return 'Produk dengan Stok Rendah (di bawah 100)';
    }
}