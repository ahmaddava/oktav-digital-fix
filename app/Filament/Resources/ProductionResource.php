<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionResource\Pages;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Production;
use App\Models\InvoiceProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;
    public static function getNavigationLabel(): string
    {
        return __('Produksi');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Produksi');
    }

    public static function getModelLabel(): string
    {
        return __('Produksi');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Produksi');
    }
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * This method controls the visibility of the "Create" button.
     * Returning false will hide it for all users.
     */
    public static function canCreate(): bool
    {
        return false;
    }

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
                                        $set('notes_invoice', null);
                                        // If an invoice is selected, fetch its data
                                        if ($state) {
                                            $invoice = Invoice::find($state);
                                            if ($invoice) {
                                                $set('notes_invoice', $invoice->notes_invoice);
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

                                // Gunakan relasi yang benar: 'invoiceProducts.product'
                                $invoice = Invoice::with('invoiceProducts.product')->find($invoiceId);

                                if (!$invoice || $invoice->invoiceProducts->isEmpty()) {
                                    return 'Tidak ada produk ditemukan untuk invoice ini';
                                }

                                $productsList = '<div class="space-y-2">';

                                // Loop melalui 'invoiceProducts', bukan 'products'
                                foreach ($invoice->invoiceProducts as $item) {
                                    // Dapatkan nama dengan logika fallback
                                    $name = $item->product->product_name ?? $item->product_name;

                                    $productsList .= '<div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">';

                                    // Product name
                                    $productsList .= '<div class="flex-1">';
                                    $productsList .= '<span class="font-medium text-gray-800 dark:text-gray-200">' . $name . '</span>';
                                    $productsList .= '</div>';

                                    // Quantity (akses langsung dari $item, bukan pivot)
                                    $productsList .= '<div class="ml-4 flex items-center justify-center">';
                                    $productsList .= '<span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full px-3 py-1 text-sm font-medium">';
                                    $productsList .= $item->quantity . ' unit';
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
                                Forms\Components\Select::make('machine_id')
                                    ->label('Mesin')
                                    ->options(Machine::pluck('name', 'id'))
                                    ->default(Machine::first()?->id)
                                    ->required()
                                    ->reactive(),

                                Forms\Components\TextInput::make('failed_prints')
                                    ->label('Jumlah Gagal Cetak')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Radio::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'started' => 'Mulai Produksi',
                                        'completed' => 'Selesai',
                                    ])
                                    ->default('pending')
                                    ->inline()
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state === 'completed') {
                                            $set('completed_at', now());
                                        } else {
                                            $set('completed_at', null);
                                        }

                                        if ($state === 'started') {
                                            // Set started_at to current date only, if not already set
                                            $set('started_at', function (Forms\Get $get) {
                                                return $get('started_at') ?? now()->toDateString();
                                            });
                                        } else {
                                            // You might want to clear started_at if status changes from started
                                            // This depends on your business logic. For now, we'll keep it if it was started.
                                        }
                                    }),
                            ]),
                        Forms\Components\DatePicker::make('started_at')
                            ->label('Mulai Produksi')
                            ->hidden(fn(Forms\Get $get): bool => $get('status') !== 'started' && $get('status') !== 'completed')
                            ->dehydrated(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Tanggal Selesai')
                            ->hidden(fn(Forms\Get $get): bool => $get('status') !== 'completed')
                            ->dehydrated(),
                        Forms\Components\Textarea::make('notes_invoice')
                            ->label('Catatan')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('is_adjustment')
                            ->default(0),
                    ])
                    ->columns(1),

                // Section untuk update status per-item
                Forms\Components\Section::make('Status Item Produksi')
                    ->description('Tandai item yang sudah selesai diproduksi.')
                    ->schema([
                        Forms\Components\Placeholder::make('item_status_list')
                            ->label('')
                            ->content(function (callable $get, ?Production $record) {
                                if (!$record || !$record->invoice) {
                                    $invoiceId = $get('invoice_id');
                                    if (!$invoiceId) return 'Pilih invoice terlebih dahulu.';
                                    $invoice = Invoice::with(['invoiceProducts.machine'])->find($invoiceId);
                                    if (!$invoice || $invoice->invoiceProducts->isEmpty()) return 'Tidak ada item.';
                                    $items = $invoice->invoiceProducts;
                                } else {
                                    $record->load(['invoice.invoiceProducts.machine']);
                                    $items = $record->invoice->invoiceProducts;
                                }

                                $total = $items->count();
                                $completed = $items->where('status', 'completed')->count();
                                $pct = $total > 0 ? round(($completed / $total) * 100) : 0;

                                $html = "<div class='space-y-2'>";
                                $html .= "<div class='flex items-center gap-2 mb-3'>";
                                $html .= "<div class='flex-1 h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden'><div class='h-full rounded-full " . ($pct === 100 ? 'bg-green-500' : 'bg-blue-500') . "' style='width: {$pct}%'></div></div>";
                                $html .= "<span class='text-sm font-semibold text-gray-700 dark:text-gray-300'>{$completed}/{$total} Selesai</span>";
                                $html .= "</div>";

                                foreach ($items as $item) {
                                    $name = $item->product_name ?? 'Item';
                                    $qty = $item->quantity ?? 0;
                                    $status = $item->status ?? 'pending';
                                    $machineName = $item->machine?->name ?? '<span class="text-gray-400 italic font-normal">Belum ditentukan</span>';
                                    
                                    $statusBadge = match($status) {
                                        'completed' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">✓ Selesai</span>',
                                        'started' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">⚙ Proses</span>',
                                        default => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">⏳ Pending</span>',
                                    };
                                    $html .= "<div class='flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg'>";
                                    $html .= "<div class='flex-1'>";
                                    $html .= "<div class='font-medium text-gray-800 dark:text-gray-200'>{$name}</div>";
                                    $html .= "<div class='text-xs text-secondary-500 font-semibold mt-1 flex items-center gap-1'><x-heroicon-o-cpu-chip class='w-3 h-3' /> Mesin: {$machineName}</div>";
                                    $html .= "</div>";
                                    $html .= "<div class='flex items-center gap-3'>";
                                    
                                    // Tampilkan link artwork jika ada
                                    if ($item->file_path) {
                                        $fileUrl = route('artwork.view', ['filename' => basename($item->file_path)]);
                                        $html .= "<a href='{$fileUrl}' target='_blank' class='inline-flex items-center px-2 py-1 text-xs font-semibold text-primary-600 bg-primary-50 dark:bg-primary-900/30 dark:text-primary-400 rounded border border-primary-200 dark:border-primary-800 hover:bg-primary-100 transition-colors'>
                                            <x-heroicon-o-document-arrow-down class='w-3 h-3 mr-1' /> File
                                        </a>";
                                    }

                                    $html .= "<span class='bg-blue-100 dark:bg-blue-900 text-blue-800 dark:bg-blue-200 rounded-full px-3 py-1 text-sm font-medium'>{$qty} unit</span>";
                                    $html .= $statusBadge;
                                    $html .= "</div></div>";
                                }
                                $html .= "</div>";
                                return new HtmlString($html);
                            }),
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
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('machine.name')
                    ->label('Mesin')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Tanpa Mesin')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'started' => 'Mulai Produksi',
                        'completed' => 'Selesai',
                        default => $state,
                    })
                    ->colors([
                        'warning' => fn($state): bool => $state === 'pending',
                        'info' => fn($state): bool => $state === 'started',
                        'success' => fn($state): bool => $state === 'completed',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress Item')
                    ->badge()
                    ->color(function (Production $record) {
                        $items = $record->invoice?->invoiceProducts;
                        if (!$items || $items->count() === 0) return 'gray';
                        $completed = $items->where('status', 'completed')->count();
                        $total = $items->count();
                        if ($completed === $total) return 'success';
                        if ($completed > 0) return 'info';
                        return 'warning';
                    })
                    ->formatStateUsing(fn(string $state) => $state . ' Selesai')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai Produksi')
                    ->date() // Changed to date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('invoice.due_date')
                    ->label('Deadline')
                    ->date() // Changed to date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->color(function (?string $state, Production $record): string {
                        // Highlight red if deadline is past and status is not completed
                        // Using toDateString() for comparison to ignore time
                        if ($state && $record->status !== 'completed' && Carbon::parse($state)->isPast()) {
                            return 'danger';
                        }
                        return 'default';
                    }),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->dateTime() // Keep DateTime for completed_at if you need the exact time
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('failed_prints')
                    ->label('Gagal Cetak')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Total Clicks')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_counter')
                    ->label('Total Counter')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('machine_id')
                    ->label('Filter Mesin')
                    ->relationship('machine', 'name')
                    ->placeholder('Semua Mesin'),

                Tables\Filters\Filter::make('has_pending_items')
                    ->label('Masih Ada Item Pending')
                    ->query(fn (Builder $query) => $query->whereHas('invoice.invoiceProducts', function ($q) {
                        $q->where('status', 'pending');
                    })),

                Tables\Filters\Filter::make('has_started_items')
                    ->label('Ada Item Sedang Proses')
                    ->query(fn (Builder $query) => $query->whereHas('invoice.invoiceProducts', function ($q) {
                        $q->where('status', 'started');
                    })),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Sembunyikan record adjustment dan eager load relasi
                return $query->where(function ($q) {
                    $q->where('is_adjustment', 0)
                        ->orWhereNull('is_adjustment');
                })->with(['machine', 'invoice.invoiceProducts.machine']);
            })
            ->actions([
                // Action untuk update status per-item
                Tables\Actions\Action::make('updateItemStatus')
                    ->label('Update Item')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(function (Production $record): bool {
                        // Sembunyikan jika status global sudah completed
                        if ($record->status === 'completed') return false;
                        
                        // Tampilkan hanya jika status sudah 'started' (sedang proses)
                        return $record->status === 'started';
                    })
                    ->form(function (Production $record) {
                        $items = $record->invoice?->invoiceProducts ?? collect();
                        $fields = [];
                        foreach ($items as $item) {
                            $fields[] = Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\ToggleButtons::make('item_' . $item->id)
                                        ->label(($item->product_name ?? 'Item') . ' — ' . $item->quantity . ' unit')
                                        ->options([
                                            'pending' => 'Pending',
                                            'started' => 'Proses',
                                            'completed' => 'Selesai',
                                        ])
                                        ->icons([
                                            'pending' => 'heroicon-o-clock',
                                            'started' => 'heroicon-o-cog-6-tooth',
                                            'completed' => 'heroicon-o-check-circle',
                                        ])
                                        ->colors([
                                            'pending' => 'gray',
                                            'started' => 'info',
                                            'completed' => 'success',
                                        ])
                                        ->default($item->status ?? 'pending')
                                        ->inline()
                                        ->live()
                                        ->required(),

                                    Forms\Components\Select::make('machine_' . $item->id)
                                        ->label('Mesin')
                                        ->options(Machine::pluck('name', 'id'))
                                        ->default($item->machine_id ?? $record->machine_id)
                                        ->placeholder('Pilih Mesin')
                                        ->required()
                                        ->visible(fn(Get $get) => $get('item_' . $item->id) !== 'pending'),
                                    
                                    Forms\Components\Placeholder::make('file_' . $item->id)
                                        ->label('File Artwork')
                                        ->content(function () use ($item) {
                                            if (!$item->file_path) return 'Tidak ada file';
                                            $url = route('artwork.view', ['filename' => basename($item->file_path)]);
                                            return new \Illuminate\Support\HtmlString("<a href='{$url}' target='_blank' style='color: #0ea5e9; font-weight: bold; text-decoration: underline;'>Klik untuk Buka File</a>");
                                        })
                                        ->visible(fn(Get $get) => $get('item_' . $item->id) !== 'pending'),
                                ]);
                        }
                        return [
                            Forms\Components\Section::make('Atur status & mesin tiap item')
                                ->description('Anda bisa mengubah status dan mesin untuk masing-masing barang.')
                                ->schema($fields),
                        ];
                    })
                    ->action(function (Production $record, array $data): void {
                        $items = $record->invoice?->invoiceProducts ?? collect();
                        foreach ($items as $item) {
                            $statusKey = 'item_' . $item->id;
                            $machineKey = 'machine_' . $item->id;
                            
                            $updateData = [];
                            if (isset($data[$statusKey])) $updateData['status'] = $data[$statusKey];
                            if (isset($data[$machineKey])) $updateData['machine_id'] = $data[$machineKey];
                            
                            if (!empty($updateData)) {
                                $item->update($updateData);
                            }
                        }

                        // OTOMATIS: Cek jika semua item sudah 'completed'
                        $record->load('invoice.invoiceProducts');
                        $allItemsDone = $record->invoice->invoiceProducts->every(fn($i) => $i->status === 'completed');

                        if ($allItemsDone && $record->invoice->invoiceProducts->isNotEmpty()) {
                            $record->update(['status' => 'completed']);
                            
                            Notification::make()
                                ->title('Produksi otomatis diselesaikan')
                                ->body('Semua barang sudah selesai dikerjakan.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Status item diperbarui')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('startProduction')
                    ->label('Mulai Produksi')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(function (Production $record): bool {
                        // Hanya tampil jika status pending
                        return $record->status === 'pending';
                    })
                    ->form(function (Production $record) {
                        $items = $record->invoice?->invoiceProducts ?? collect();
                        $fields = [];
                        
                        // Dropdown Mesin Utama (disembunyikan sesuai permintaan tapi nilainya tetap diproses)
                        $fields[] = Forms\Components\Hidden::make('machine_id')
                            ->default($record->machine_id ?? Machine::first()?->id);

                        // List Item dengan Toggle & Mesin
                        foreach ($items as $item) {
                            $fields[] = Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('info_' . $item->id)
                                        ->label('Barang')
                                        ->content(($item->product_name ?? 'Item') . ' — ' . $item->quantity . ' unit'),

                                    Forms\Components\ToggleButtons::make('item_' . $item->id)
                                        ->label('Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'started' => 'Proses',
                                        ])
                                        ->icons([
                                            'pending' => 'heroicon-o-clock',
                                            'started' => 'heroicon-o-cog-6-tooth',
                                        ])
                                        ->colors([
                                            'pending' => 'gray',
                                            'started' => 'info',
                                        ])
                                        ->default('pending') // Diubah dari 'started' ke 'pending'
                                        ->inline()
                                        ->live() // Perlu live agar reaktif untuk visibility mesin
                                        ->required(),

                                    Forms\Components\Select::make('machine_' . $item->id)
                                        ->label('Mesin')
                                        ->options(Machine::pluck('name', 'id'))
                                        ->default($record->machine_id ?? Machine::first()?->id)
                                        ->required()
                                        ->visible(fn(Get $get) => $get('item_' . $item->id) === 'started'),

                                    Forms\Components\Placeholder::make('file_' . $item->id)
                                        ->label('File Artwork')
                                        ->content(function () use ($item) {
                                            if (!$item->file_path) return 'Tidak ada file';
                                            $url = route('artwork.view', ['filename' => basename($item->file_path)]);
                                            return new \Illuminate\Support\HtmlString("<a href='{$url}' target='_blank' style='color: #0ea5e9; font-weight: bold; text-decoration: underline;'>Klik untuk Buka File</a>");
                                        })
                                        ->visible(fn(Get $get) => $get('item_' . $item->id) === 'started'),
                                ]);
                        }

                        return [
                            Forms\Components\Section::make('Persiapan Produksi Per Item')
                                ->description('Tentukan mesin dan status pengerjaan untuk masing-masing barang.')
                                ->schema($fields),
                        ];
                    })
                    ->action(function (Production $record, array $data): void {
                        $record->update([
                            'status' => 'started',
                            'machine_id' => $data['machine_id'], // Primary machine
                            'started_at' => now()->toDateString(),
                        ]);

                        $items = $record->invoice?->invoiceProducts ?? collect();
                        foreach ($items as $item) {
                            $statusKey = 'item_' . $item->id;
                            $machineKey = 'machine_' . $item->id;
                            
                            $updateData = [];
                            if (isset($data[$statusKey])) $updateData['status'] = $data[$statusKey];
                            if (isset($data[$machineKey])) $updateData['machine_id'] = $data[$machineKey];
                            
                            if (!empty($updateData)) {
                                $item->update($updateData);
                            }
                        }

                        // OTOMATIS: Cek jika semua item sudah 'completed'
                        $record->load('invoice.invoiceProducts');
                        $allItemsDone = $record->invoice->invoiceProducts->every(fn($i) => $i->status === 'completed');

                        if ($allItemsDone && $record->invoice->invoiceProducts->isNotEmpty()) {
                            $record->update(['status' => 'completed']);
                        }

                        Notification::make()
                            ->title('Produksi dimulai')
                            ->success()
                            ->send();
                    }),

                // Action untuk mencatat gagal cetak
                Tables\Actions\Action::make('updateFailedPrints')
                    ->label('Update Gagal Cetak')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(function (Production $record): bool {
                        // Sembunyikan jika sudah selesai
                        if ($record->status === 'completed') return false;
                        
                        $items = $record->invoice?->invoiceProducts ?? collect();
                        if ($items->isEmpty()) return $record->status === 'started';

                        // Sembunyikan jika SEMUA item sudah selesai
                        $allCompleted = $items->every(fn($item) => $item->status === 'completed');
                        return !$allCompleted && $record->status === 'started';
                    })
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
                // Ganti action ini
                Tables\Actions\Action::make('completeProduction')
                    ->label('Selesai Produksi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Production $record): bool => $record->status === 'started')
                    ->requiresConfirmation()
                    ->action(function (Production $record): void {
                        try {
                            DB::transaction(function () use ($record) {
                                // Load relasi invoice dan produknya untuk efisiensi
                                $record->load('invoice.products');

                                if ($record->invoice && $record->invoice->products->isNotEmpty()) {
                                    foreach ($record->invoice->products as $product) {
                                        // Hanya kurangi stok jika tipenya 'digital_print'
                                        if ($product->type === 'digital_print') {
                                            // --- PERUBAHAN DI SINI ---
                                            // Stok hanya dikurangi sejumlah kuantitas pesanan di invoice.
                                            // Angka `failed_prints` kini hanya berfungsi sebagai catatan.
                                            $totalReduction = $product->pivot->quantity;

                                            // Kurangi stok produk
                                            $product->decrement('stock', $totalReduction);
                                        }
                                    }
                                }

                                // Update status produksi setelah stok berhasil dikurangi
                                $record->update([
                                    'status' => 'completed',
                                    'completed_at' => now(),
                                ]);
                            });

                            Notification::make()
                                ->title('Produksi selesai & Stok Diperbarui')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyelesaikan produksi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
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
                        ->action(function (Tables\Actions\BulkAction $action): void {
                            foreach ($action->getRecords() as $record) {
                                if ($record->status !== 'started') {
                                    continue;
                                }

                                try {
                                    DB::transaction(function () use ($record) {
                                        $record->load('invoice.products');

                                        if ($record->invoice && $record->invoice->products->isNotEmpty()) {
                                            foreach ($record->invoice->products as $product) {
                                                if ($product->type === 'digital_print') {
                                                    // --- PERUBAHAN DI SINI ---
                                                    // Stok hanya dikurangi sejumlah kuantitas pesanan.
                                                    $totalReduction = $product->pivot->quantity;
                                                    $product->decrement('stock', $totalReduction);
                                                }
                                            }
                                        }

                                        $record->update([
                                            'status' => 'completed',
                                            'completed_at' => now(),
                                        ]);
                                    });
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal memproses produksi: ' . $record->invoice?->invoice_number)
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                    continue;
                                }
                            }

                            Notification::make()
                                ->title('Produksi terpilih telah diproses')
                                ->body('Status produksi telah diubah dan stok telah diperbarui.')
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
