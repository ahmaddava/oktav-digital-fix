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
    protected static ?string $navigationGroup = 'Kalkulasi Harga';
    protected static ?string $navigationLabel = 'Item Produksi';
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
                        // Jika Anda ingin menambah field lain, tambahkan di sini.
                    ])
                    ->createOptionUsing(function (array $data) {
                        $category = ProductionCategory::create([
                            'name' => $data['name'],
                            // Tambahkan field lain jika ada.
                        ]);
                        return $category->getKey(); // return id kategori baru
                    }),
                    
                Forms\Components\TextInput::make('name')
                    ->label('Nama Item')
                    ->required()
                    ->maxLength(255),
                    
                // Forms\Components\Select::make('size')
                //     ->label('Ukuran')
                //     ->options([
                //         'XS' => 'XS',
                //         'Kecil' => 'Kecil',
                //         'Sedang' => 'Sedang',
                //         'Besar' => 'Besar',
                //         'XXL' => 'XXL'
                //     ])
                //     ->nullable(),
                    
                // Forms\Components\TextInput::make('dimension')
                //     ->label('Dimensi')
                //     ->placeholder('10x10, 10x15, 15x15')
                //     ->maxLength(255),
                    
                    
                Forms\Components\TextInput::make('price') 
                    ->label('Harga Utama')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                    
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
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Harga untuk quantity tersebut'),
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