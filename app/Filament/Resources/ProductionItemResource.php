<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionItemResource\Pages;
use App\Models\ProductionItem;
use App\Models\ProductionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionItemResource extends Resource
{
    protected static ?string $model = ProductionItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    public static function getNavigationLabel(): string
    {
        return __('Item Produksi');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Kalkulasi Harga');
    }

    public static function getModelLabel(): string
    {
        return __('Item Produksi');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Item Produksi');
    }
    protected static ?string $pluralModelLabel = 'Item Produksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->unique(table: ProductionCategory::class, column: 'name'),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $category = ProductionCategory::create([
                            'name' => $data['name'],
                        ]);
                        return $category->getKey();
                    }),

                Forms\Components\TextInput::make('name')
                    ->label('Nama Item')
                    ->required()
                    ->maxLength(255),

                // Grid 3 kolom untuk price, lebar_kertas, panjang_kertas
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Utama')
                            ->required()
                            ->prefix('Rp')
                            ->placeholder('Contoh: 100.000')
                            ->extraInputAttributes([
                                'x-data' => '{}',
                                'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                'inputmode' => 'numeric',
                            ])
                            ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('lebar_kertas')
                            ->label('Lebar Kertas (cm)')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('panjang_kertas')
                            ->label('Panjang Kertas (cm)')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->columnSpan(1),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Forms\Components\Repeater::make('prices')
                    ->label('Harga Berdasarkan Quantity')
                    ->relationship('prices')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Minimum Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Contoh: 100, 200, 500'),
                        Forms\Components\TextInput::make('price')
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
                    ->columnSpanFull(),
            ]);
    }

    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Item')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('size')
                //     ->label('Ukuran')
                //     ->badge()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('dimension')
                //     ->label('Dimensi')
                //     ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga satuan')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                // Tables\Filters\SelectFilter::make('size')
                //     ->label('Ukuran')
                //     ->options([
                //         'XS' => 'XS',
                //         'Kecil' => 'Kecil',
                //         'Sedang' => 'Sedang',
                //         'Besar' => 'Besar',
                //         'XXL' => 'XXL'
                //     ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionItems::route('/'),
            'create' => Pages\CreateProductionItem::route('/create'),
            'edit' => Pages\EditProductionItem::route('/{record}/edit'),
        ];
    }
}