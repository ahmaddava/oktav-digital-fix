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
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;
    protected static ?string $navigationGroup = 'Produksi';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Bagian Pilih Invoice & Lampiran
                Forms\Components\Section::make('Informasi Invoice')
                    ->description('Pilih invoice yang akan diproses produksi dan lihat lampirannya.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->options(function () {
                                        return Invoice::availableForProduction()
                                            ->get()
                                            ->pluck('invoice_number', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        // Clear previous values when invoice changes
                                        $set('notes', null);
                                        // If an invoice is selected, fetch its data
                                        if ($state) {
                                            $invoice = Invoice::find($state);
                                            if ($invoice) {
                                                $set('notes', $invoice->notes);
                                            }
                                        }
                                    }),
    
                                Forms\Components\Placeholder::make('invoice_attachment')
                                    ->label('Lampiran Invoice')
                                    ->content(function (callable $get) {
                                        $invoiceId = $get('invoice_id');
                                        if (!$invoiceId) {
                                            return 'Pilih invoice untuk melihat lampiran';
                                        }
                                        $invoice = Invoice::find($invoiceId);
                                        if (!$invoice || !$invoice->attachment_path) {
                                            return 'Tidak ada lampiran untuk invoice ini';
                                        }
                                        $url = Storage::url($invoice->attachment_path);
                                        $filename = basename($invoice->attachment_path);
    
                                        return new HtmlString(
                                            "<div class='flex items-center p-2 bg-gray-50 dark:bg-gray-900 rounded-lg'>
                                                <svg class='w-5 h-5 mr-2 text-gray-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                                                </svg>
                                                <a href='{$url}' target='_blank' class='text-blue-600 hover:underline dark:text-blue-400'>
                                                    {$filename}
                                                </a>
                                            </div>"
                                        );
                                    }),
                            ]),
                    ])
                    ->columns(1),
    
                // Bagian Produk yang Akan Diproduksi
                Forms\Components\Section::make('Produk Yang Akan Diproduksi')
                    ->schema([
                        Forms\Components\Placeholder::make('products')
                            ->label('Daftar Produk')
                            ->content(function (callable $get) {
                                $invoiceId = $get('invoice_id');
                                if (!$invoiceId) {
                                    return 'Pilih invoice untuk melihat produk yang akan diproduksi';
                                }
                                $invoice = Invoice::with('products')->find($invoiceId);
                                if (!$invoice || $invoice->products->isEmpty()) {
                                    return 'Tidak ada produk ditemukan untuk invoice ini';
                                }
                                $productsList = '<div class="space-y-2">';
                                foreach ($invoice->products as $product) {
                                    $productsList .= '<div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">';
                                    // Product name
                                    $productsList .= '<div class="flex-1">';
                                    $productsList .= '<span class="font-medium text-gray-800 dark:text-gray-200">' . $product->product_name . '</span>';
                                    $productsList .= '</div>';
                                    // Quantity
                                    $productsList .= '<div class="ml-4 flex items-center justify-center">';
                                    $productsList .= '<span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full px-3 py-1 text-sm font-medium">';
                                    $productsList .= $product->pivot->quantity . ' unit';
                                    $productsList .= '</span>';
                                    $productsList .= '</div>';
                                    $productsList .= '</div>';
                                }
                                $productsList .= '</div>';
                                return new HtmlString($productsList);
                            }),
                    ])
                    ->columns(1),
    
                // Bagian Detail Produksi
                Forms\Components\Section::make('Detail Produksi')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('machine_type')
                                    ->label('Mesin')
                                    ->options([
                                        'mesin_1' => 'Mesin 1',
                                        'mesin_2' => 'Mesin 2',
                                    ])
                                    ->default('mesin_1')
                                    ->required(),
    
                                Forms\Components\TextInput::make('failed_prints')
                                    ->label('Jumlah Gagal Cetak')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
    
                                Forms\Components\Radio::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Selesai',
                                    ])
                                    ->default('pending')
                                    ->inline()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state === 'completed') {
                                            $set('completed_at', now());
                                        } else {
                                            $set('completed_at', null);
                                        }
                                    }),
                            ]),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Tanggal Selesai')
                            ->hidden()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('is_adjustment')
                            ->default(0),
                    ])
                    ->columns(1),
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
                        'primary' => fn ($state): bool => $state === 'mesin_1',
                        'success' => fn ($state): bool => $state === 'mesin_2',
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
                // Action untuk mencatat gagal cetak
                Tables\Actions\Action::make('updateFailedPrints')
                    ->label('Update Gagal Cetak')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (Production $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('failed_prints')
                            ->label('Jumlah Gagal Cetak')
                            ->required()
                            ->numeric()
                            ->default(function (Production $record) {
                                return $record->failed_prints;
                            }),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->default(function (Production $record) {
                                return $record->notes;
                            }),
                    ])
                    ->action(function (Production $record, array $data): void {
                        // Simply update the record - the model observers will handle
                        // the counter calculations and stock reduction automatically
                        $record->update([
                            'failed_prints' => $data['failed_prints'],
                            'notes' => $data['notes'],
                        ]);

                        Notification::make()
                            ->title('Gagal cetak berhasil diupdate')
                            ->success()
                            ->send();
                    }),

                // Action untuk menyelesaikan produksi
                Tables\Actions\Action::make('completeProduction')
                    ->label('Selesai Produksi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Production $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Production $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Produksi selesai')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Action untuk menyelesaikan produksi secara bulk
                    Tables\Actions\BulkAction::make('completeMultipleProductions')
                        ->label('Selesai Produksi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Tables\Actions\BulkAction $action, array $data): void {
                            $action->getRecords()->each(function (Production $record): void {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'completed',
                                        'completed_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Semua produksi terpilih telah diselesaikan')
                                ->success()
                                ->send();
                        }),

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