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
use Filament\Forms\Components\Repeater;

class ProductResource extends Resource implements HasShieldPermissions
{
    protected static ?string $navigationGroup = 'Management';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            'export',
        ];
    }
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Input untuk tipe produk
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
    
                // Input untuk nama produk
                TextInput::make('product_name')
                    ->label('Nama Produk/Jasa')
                    ->required()
                    ->maxLength(255),
    
                // Input untuk harga produk dasar
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
    
                // Input untuk stock (hanya untuk produk digital print)
                TextInput::make('stock')
                    ->numeric()
                    ->required(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('type') === 'digital_print')
                    ->hidden(fn (Get $get): bool => $get('type') === 'jasa'),
    
                TextInput::make('click')
                    ->label('Click')
                    ->numeric()
                    // Required untuk digital_print dan jasa
                    ->required(fn (Get $get): bool => in_array($get('type'), ['digital_print', 'jasa']))
                    ->default(0)
                    // Visible untuk digital_print dan jasa
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['digital_print', 'jasa'])),
                    
                
                // Repeater untuk menambahkan beberapa set harga berdasarkan quantity
                Repeater::make('prices')
                    ->relationship('prices')
                    ->label('Harga Berdasarkan Quantity')
                    ->schema([
                        // Input untuk minimum quantity
                        TextInput::make('min_quantity')
                            ->label('Minimum Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Contoh: 100, 300, 500, 700, 1000'),
    
                        // Input untuk harga berdasarkan quantity tersebut
                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Harga untuk quantity tersebut'),
                    ])
                    ->columnSpanFull(2),
            ]);
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