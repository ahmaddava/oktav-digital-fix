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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
class ProductResource extends Resource implements HasShieldPermissions
{
    public static function getNavigationLabel(): string
    {
        return __('Produk');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Manajemen');
    }

    public static function getModelLabel(): string
    {
        return __('Produk');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Produk');
    }

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
                Section::make('Informasi Utama')
                    ->description('Data dasar produk atau jasa')
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

                        // Input untuk stock (hanya untuk produk digital print)
                        TextInput::make('stock')
                            ->numeric()
                            ->required(fn (Get $get): bool => $get('type') === 'digital_print')
                            ->default(0)
                            ->visible(fn (Get $get): bool => $get('type') === 'digital_print')
                            ->hidden(fn (Get $get): bool => $get('type') === 'jasa'),

                        Toggle::make('has_click')
                            ->label('Gunakan Hitungan Klik?')
                            ->inline(false)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, ?Product $record) => $component->state($record && $record->click > 0))
                            ->afterStateUpdated(fn ($state, Set $set) => ! $state ? $set('click', 0) : null),
                            
                        TextInput::make('click')
                            ->label('Jumlah Target Klik')
                            ->numeric()
                            ->required(fn (Get $get): bool => (bool) $get('has_click'))
                            ->default(0)
                            ->visible(fn (Get $get): bool => (bool) $get('has_click')),
                    ])->columns(2),

                Section::make('Harga Varian')
                    ->description('Atur harga berdasarkan jumlah minimum pesanan')
                    ->schema([
                        // Repeater untuk menambahkan beberapa set harga berdasarkan quantity
                        Repeater::make('prices')
                            ->relationship('prices')
                            ->hiddenLabel()
                            ->addActionLabel('Tambah Harga Quantity')
                            ->defaultItems(1)
                            ->schema([
                                TextInput::make('min_quantity')
                                    ->label('Minimum Quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Contoh: 100, 300, 500...'),
            
                                TextInput::make('price')
                                    ->label('Harga')
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 100.000')
                                    ->extraInputAttributes([
                                        'x-data' => '{}',
                                        'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                        'inputmode' => 'numeric',
                                    ])
                                    ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                                    ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),
                            ])
                            ->columns(2),
                    ]),
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
                    ->getStateUsing(function (Product $record) {
                        return $record->prices()->orderBy('min_quantity', 'asc')->first()?->price ?? $record->price ?? 0;
                    })
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
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('prices'));
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