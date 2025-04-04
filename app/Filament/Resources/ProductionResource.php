<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionResource\Pages;
use App\Models\Invoice;
use App\Models\Production;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Ganti kode sesuai dengan struktur tabel Anda
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->options(function () {
                        return Invoice::availableForProduction()
                            ->get()
                            ->pluck('invoice_number', 'id');
                    })
                    ->searchable()
                    ->required(),

                // Kode lainnya...
                Forms\Components\Select::make('machine_type')
                    ->label('Mesin')
                    ->options([
                        Production::MESIN_1 => 'Mesin 1',
                        Production::MESIN_2 => 'Mesin 2',
                    ])
                    ->default(Production::MESIN_1)
                    ->required(),

                Forms\Components\TextInput::make('failed_prints')
                    ->label('Jumlah Gagal Cetak')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\TextInput::make('total_clicks')
                    ->label('Total Clicks')
                    ->numeric()
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Dihitung otomatis berdasarkan produk'),

                Forms\Components\TextInput::make('total_counter')
                    ->label('Total Counter')
                    ->numeric()
                    ->required()
                    ->default(fn ($get) => $get('total_clicks'))
                    ->helperText('Boleh diedit sesuai kebutuhan')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('is_adjustment')
                    ->default(0), // Semua production manual bukan adjustment

                Forms\Components\Toggle::make('status')
                    ->label('Selesai')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check')
                    ->offIcon('heroicon-m-x-mark')
                    ->default(false)
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                            $set('completed_at', now());
                        } else {
                            $set('completed_at', null);
                        }
                    }),

                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Tanggal Selesai')
                    ->hidden()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('machine_type')
                    ->label('Mesin')
                    ->badge()
                    ->colors([
                        'primary' => fn ($state): bool => $state === Production::MESIN_1,
                        'success' => fn ($state): bool => $state === Production::MESIN_2,
                    ]),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'completed' ? 'Selesai' : 'Pending')
                    ->colors([
                        'success' => fn ($state): bool => $state === 'completed',
                        'warning' => fn ($state): bool => $state === 'pending',
                    ]),
                
                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Total Clicks')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_counter')
                    ->label('Total Counter')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('failed_prints')
                    ->label('Gagal Cetak')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Tanggal Selesai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Sembunyikan record adjustment dari tabel
                return $query->where(function ($q) {
                    $q->where('is_adjustment', 0)
                      ->orWhereNull('is_adjustment');
                });
            })
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
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
            'counter-manager' => Pages\MachineCounterManager::route('/counter-manager'),
        ];
    }

    public static function getNavigationBadge(): ?string 
    {
        // Tampilkan jumlah production yang bukan adjustment
        return static::getModel()::where(function ($query) {
            $query->where('is_adjustment', 0)
                  ->orWhereNull('is_adjustment');
        })->count();
    }
    
    public static function getNavigationActions(): array 
    {
        return [
            \Filament\Actions\Action::make('manageCounters')
                ->label('Pengaturan Counter')
                ->url(static::getUrl('counter-manager'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
        ];
    }
}