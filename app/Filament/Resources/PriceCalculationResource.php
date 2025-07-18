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

    // This form method is primarily for the default create/edit pages if you were to use them.
    // Since we're redirecting 'edit' to the calculator, this form might not be directly used for 'edit'.
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
                Tables\Columns\TextColumn::make('box_type_selection')
                    ->label('Jenis Box')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'TAB' => 'primary',
                        'BUSA' => 'info',
                        'JENDELA' => 'success',
                        'BUKU PITA' => 'warning',
                        'BUKU MAGNET' => 'danger',
                        'SELONGSONG' => 'gray',
                        'Double WallTreasury' => 'rose',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('summary_selling_price_per_item')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_price_estimate_display')
                    ->label('Total Harga Estimasi')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Kalkulasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('box_type_selection')
                    ->label('Jenis Box')
                    ->options([
                        'TAB' => 'TAB',
                        'BUSA' => 'BUSA',
                        'JENDELA' => 'JENDELA',
                        'BUKU PITA' => 'BUKU PITA',
                        'BUKU MAGNET' => 'BUKU MAGNET',
                        'SELONGSONG' => 'SELONGSONG',
                        'Double WallTreasury' => 'Double Wall Treasury'
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
                // THIS IS THE CRUCIAL CHANGE:
                Tables\Actions\Action::make('edit_in_calculator') // Custom action
                    ->label('Ubah') // "Ubah" label for the button
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (PriceCalculation $record): string => ProductionCalculator::getUrl(['recordId' => $record->id]))
                    ->color('warning'),
                // END OF CRUCIAL CHANGE

                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (PriceCalculation $record) {
                        $newRecord = $record->replicate();
                        $newRecord->product_name = $record->product_name . ' (Copy)';
                        $newRecord->save();

                        return redirect()->to(ProductionCalculator::getUrl(['recordId' => $newRecord->id]));
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
                        Infolists\Components\TextEntry::make('box_type_selection')
                            ->label('Jenis Box')
                            ->badge(),
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Jumlah Pesan'),
                        Infolists\Components\TextEntry::make('master_cost_size_selected')
                            ->label('Ukuran Box (Master Cost)')
                            ->badge(),
                        Infolists\Components\TextEntry::make('poly_dimension_selected')
                            ->label('Dimensi Poly')
                            ->badge()
                            ->placeholder('Tidak ada'),
                        Infolists\Components\TextEntry::make('include_knife_cost')
                            ->label('Termasuk Ongkos Pisau')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'ada' ? 'Ada' : 'Tidak Ada'),
                        Infolists\Components\TextEntry::make('custom_profit_input')
                            ->label('Profit Kustom (%)')
                            ->suffix('%')
                            ->numeric(2)
                            ->placeholder('Tidak diterapkan'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Kalkulasi')
                            ->dateTime('d/m/Y H:i:s'),
                    ])->columns(3),

                Infolists\Components\Section::make('Dimensi Box Input')
                    ->schema([
                        Infolists\Components\TextEntry::make('atas_panjang')
                            ->label('Panjang Box Atas')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                        Infolists\Components\TextEntry::make('atas_lebar')
                            ->label('Lebar Box Atas')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                        Infolists\Components\TextEntry::make('atas_tinggi')
                            ->label('Tinggi Box Atas')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                        Infolists\Components\TextEntry::make('bawah_panjang')
                            ->label('Panjang Box Bawah')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                        Infolists\Components\TextEntry::make('bawah_lebar')
                            ->label('Lebar Box Bawah')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                        Infolists\Components\TextEntry::make('bawah_tinggi')
                            ->label('Tinggi Box Bawah')
                            ->suffix('cm')
                            ->numeric(2)
                            ->placeholder('0.0'),
                    ])->columns(3),

                Infolists\Components\Section::make('Pilihan Komponen Material')
                    ->schema([
                        Infolists\Components\TextEntry::make('is_board_included')
                            ->label('Sertakan Board')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('selected_items_ids')
                            ->label('Item Board Terpilih')
                            ->formatStateUsing(function ($state) {
                                $decoded = json_decode($state, true) ?? [];
                                return isset($decoded['board']) ? \App\Models\ProductionItem::find($decoded['board'])->name : 'N/A';
                            })
                            ->hidden(fn ($record) => !$record->is_board_included || !isset(json_decode($record->selected_items_ids, true)['board'])),
                        Infolists\Components\TextEntry::make('is_cover_luar_included')
                            ->label('Sertakan Cover Luar')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('selected_items_ids')
                            ->label('Item Cover Luar Terpilih')
                            ->formatStateUsing(function ($state) {
                                $decoded = json_decode($state, true) ?? [];
                                return isset($decoded['cover_luar']) ? \App\Models\ProductionItem::find($decoded['cover_luar'])->name : 'N/A';
                            })
                            ->hidden(fn ($record) => !$record->is_cover_luar_included || !isset(json_decode($record->selected_items_ids, true)['cover_luar'])),
                        Infolists\Components\TextEntry::make('is_cover_dalam_included')
                            ->label('Sertakan Cover Dalam')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('selected_items_ids')
                            ->label('Item Cover Dalam Terpilih')
                            ->formatStateUsing(function ($state) {
                                $decoded = json_decode($state, true) ?? [];
                                return isset($decoded['cover_dalam']) ? \App\Models\ProductionItem::find($decoded['cover_dalam'])->name : 'N/A';
                            })
                            ->hidden(fn ($record) => !$record->is_cover_dalam_included || !isset(json_decode($record->selected_items_ids, true)['cover_dalam'])),
                        Infolists\Components\TextEntry::make('is_busa_included')
                            ->label('Sertakan Busa')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('selected_items_ids')
                            ->label('Item Busa Terpilih')
                            ->formatStateUsing(function ($state) {
                                $decoded = json_decode($state, true) ?? [];
                                return isset($decoded['busa']) ? \App\Models\ProductionItem::find($decoded['busa'])->name : 'N/A';
                            })
                            ->hidden(fn ($record) => !$record->is_busa_included || !isset(json_decode($record->selected_items_ids, true)['busa'])),
                    ])->columns(2),

                Infolists\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Infolists\Components\TextEntry::make('summary_total_material_cost')
                            ->label('Total Biaya Material')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('summary_total_production_work_cost')
                            ->label('Ongkos Produksi')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('summary_total_poly_cost')
                            ->label('Ongkos Poly')
                            ->money('IDR')
                            ->placeholder('Tidak ada'),
                        Infolists\Components\TextEntry::make('summary_actual_knife_cost')
                            ->label('Ongkos Pisau')
                            ->money('IDR')
                            ->placeholder('Tidak ada'),
                        Infolists\Components\TextEntry::make('summary_profit_percentage_applied')
                            ->label('Profit Diterapkan (%)')
                            ->suffix('%')
                            ->numeric(2),
                        Infolists\Components\TextEntry::make('summary_total_profit_amount')
                            ->label('Jumlah Profit')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('summary_selling_price_per_item')
                            ->label('Harga Jual Per Item')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('total_price_estimate_display')
                            ->label('TOTAL HARGA ESTIMASI')
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
            // 'create' => Pages\CreatePriceCalculation::route('/create'), // Baris ini dihapus
            'view' => Pages\ViewPriceCalculation::route('/{record}'),
            // Remove the default 'edit' page route to prevent direct access to the basic form
            // 'edit' => Pages\EditPriceCalculation::route('/{record}/edit'),
        ];
    }
}