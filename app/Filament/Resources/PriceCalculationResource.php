<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceCalculationResource\Pages;
use App\Filament\Pages\ProductionCalculator;
use App\Models\PriceCalculation;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class PriceCalculationResource extends Resource
{
    protected static ?string $model = PriceCalculation::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Kalkulasi Harga';
    protected static ?string $navigationLabel = 'Riwayat Kalkulasi';
    protected static ?string $pluralModelLabel = 'Riwayat Kalkulasi';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            'export',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('size')
                    ->label('Ukuran')
                    ->options([
                        'XS' => 'XS',
                        'Kecil' => 'Kecil',
                        'Sedang' => 'Sedang',
                        'Besar' => 'Besar',
                        'XXL' => 'XXL'
                    ])
                    ->required(),
                Forms\Components\TextInput::make('total_material_cost')
                    ->label('Total Biaya Material')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('production_cost')
                    ->label('Ongkos Produksi')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('poly_cost')
                    ->label('Ongkos Poly')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable(),
                Forms\Components\TextInput::make('knife_cost')
                    ->label('Ongkos Pisau')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable(),
                Forms\Components\TextInput::make('profit')
                    ->label('Profit')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('total_price')
                    ->label('Total Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Ukuran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'XS' => 'gray',
                        'Kecil' => 'info',
                        'Sedang' => 'success',
                        'Besar' => 'warning',
                        'XXL' => 'danger',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('total_material_cost')
                    ->label('Biaya Material')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('production_cost')
                    ->label('Ongkos Produksi')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Kalkulasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('size')
                    ->label('Ukuran')
                    ->options([
                        'XS' => 'XS',
                        'Kecil' => 'Kecil',
                        'Sedang' => 'Sedang',
                        'Besar' => 'Besar',
                        'XXL' => 'XXL'
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (PriceCalculation $record) {
                        $newRecord = $record->replicate();
                        $newRecord->product_name = $record->product_name . ' (Copy)';
                        $newRecord->save();
                        
                        // PERBAIKAN: Gunakan getUrl() dari halaman
                        return redirect()->to(ProductionCalculator::getUrl());
                    })
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Produk')
                    ->schema([
                        Infolists\Components\TextEntry::make('product_name')
                            ->label('Nama Produk'),
                        Infolists\Components\TextEntry::make('size')
                            ->label('Ukuran')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Kalkulasi')
                            ->dateTime('d/m/Y H:i:s'),
                    ])->columns(3),

                Infolists\Components\Section::make('Detail Material')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('selected_items')
                            ->label('Material Terpilih')
                            ->schema([
                                Infolists\Components\TextEntry::make('category')
                                    ->label('Kategori')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nama Item'),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('Harga')
                                    ->money('IDR'),
                            ])
                            ->columns(3),
                    ]),

                Infolists\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_material_cost')
                            ->label('Total Biaya Material')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('production_cost')
                            ->label('Ongkos Produksi')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('poly_cost')
                            ->label('Ongkos Poly')
                            ->money('IDR')
                            ->placeholder('Tidak ada'),
                        Infolists\Components\TextEntry::make('knife_cost')
                            ->label('Ongkos Pisau')
                            ->money('IDR')
                            ->placeholder('Tidak ada'),
                        Infolists\Components\TextEntry::make('profit')
                            ->label('Profit')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_price')
                            ->label('TOTAL HARGA')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success')
                            ->size('lg'),
                    ])->columns(2),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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
            'index' => Pages\ListPriceCalculations::route('/'),
            'create' => Pages\CreatePriceCalculation::route('/create'),
            'view' => Pages\ViewPriceCalculation::route('/{record}'),
            'edit' => Pages\EditPriceCalculation::route('/{record}/edit'),
        ];
    }
}