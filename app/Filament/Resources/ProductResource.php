<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ProductResource extends Resource implements HasShieldPermissions
{
    // ...

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            // Tambahkan permission kustom, misalnya 'export'
            'export',
        ];
    }
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Tipe Produk')
                    ->options([
                        'digital_print' => 'Digital Print',
                        'jasa' => 'Jasa',
                    ])
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        if ($get('type') === 'jasa') {
                            $set('stock', 0);
                        }
                    }),
                
                TextInput::make('product_name')
                    ->label('Nama Produk/Jasa')
                    ->required()
                    ->maxLength(255),
                
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                
                TextInput::make('stock')
                    ->numeric()
                    ->required(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->hidden(fn (Get $get): bool => $get('type') === 'jasa'),
                TextInput::make('click')
                    ->label('Click')
                    ->numeric()
                    ->required(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->hidden(fn (Get $get): bool => $get('type') === 'jasa'),
            ]); // SKU dihapus dari form karena auto-generate
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'digital_print' => 'success',
                        'jasa' => 'warning',
                    }),
                
                TextColumn::make('product_name')
                    ->label('Nama')
                    ->searchable(),
                
                TextColumn::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
                
                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->color(fn (int $state, Product $record): string => 
                        $record->type === 'digital_print' ? ($state > 0 ? 'success' : 'danger') : 'gray'
                    )
                    ->formatStateUsing(fn (int $state, Product $record): string => 
                        $record->type === 'jasa' ? '-' : $state
                    ),

            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Produk')
                    ->options([
                        'digital_print' => 'Digital Print',
                        'jasa' => 'Jasa',
                    ])
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}