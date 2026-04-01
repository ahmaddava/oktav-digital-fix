<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use App\Filament\Resources\InvoiceResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Wizard;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Get;
use Filament\Forms\Set;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Group;
use Illuminate\Support\Collection; // Import Collection

class InvoiceResource extends Resource implements HasShieldPermissions
{
    // Remove global static variables from here
    // protected static $customerOptions = null;
    // protected static $productOptions = null;

    public static function getNavigationLabel(): string
    {
        return __('Invoice');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Manajemen');
    }

    public static function getModelLabel(): string
    {
        return __('Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invoice');
    }

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function updateItemAndGrandTotal(Set $set, Get $get): void
    {
        $itemType = $get('item_type') ?? 'existing';
        $quantity = (float) ($get('quantity') ?: 0);
        
        $kalkulasiQty = $quantity;
        if ($itemType === 'banner') {
            $panjang = (float) ($get('panjang') ?: 0);
            $lebar = (float) ($get('lebar') ?: 0);
            $calculatedArea = $panjang * $lebar;
            // Minimum luas 1x1m
            $luas = ($calculatedArea > 0 && $calculatedArea < 1) ? 1 : $calculatedArea;
            $kalkulasiQty = $luas * $quantity;
        }
        
        // Cek harga berjenjang dari product
        if (in_array($itemType, ['existing', 'banner'])) {
            $productId = $get('product_id');
            if ($productId) {
                $product = Product::with('prices')->find($productId);
                if ($product) {
                    $newPrice = $product->getPriceByQuantity($kalkulasiQty);
                    $set('price', $newPrice);
                }
            }
        }
        
        $rawPrice = $get('price') ?: 0;
        $cleanPrice = is_string($rawPrice) ? (float) preg_replace('/[^0-9]/', '', $rawPrice) : (float) $rawPrice;
        
        $total = $cleanPrice * $kalkulasiQty;
        $set('total_price', $total);
        
        // Update Grand Total
        $allItems = $get('../../invoiceProducts') ?? [];
        $grandTotal = 0;
        foreach ($allItems as $item) {
            $val = $item['total_price'] ?? 0;
            $grandTotal += (float) (is_string($val) ? preg_replace('/[^0-9]/', '', $val) : $val);
        }
        $set('../../grand_total', $grandTotal);
    }

    // Helper function untuk kalkulasi grand total yang akurat
    private static function calculateGrandTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            if (isset($item['total_price']) && !empty($item['total_price'])) {
                // Convert string dengan formatting ke float
                $totalPrice = $item['total_price'];

                // Jika ada formatting (seperti thousand separator), hapus dulu
                if (is_string($totalPrice)) {
                    $totalPrice = str_replace([',', '.'], ['', ''], $totalPrice);
                }

                $total += (float) $totalPrice;
            }
        }

        return $total;
    }

    public static function form(Form $form): Form
    {
        // Provide options dynamically using local static cache within the closure
        static $customerOptionsCache = null;
        if ($customerOptionsCache === null) {
            $customerOptionsCache = Customer::select('nama_customer', 'nomor_customer')
                ->orderBy('nama_customer')
                ->get()
                ->mapWithKeys(fn($customer) => [
                    $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                ]);
        }

        static $productOptionsCache = null;
        if ($productOptionsCache === null) {
            $productOptionsCache = Product::with('prices')
                ->select('id', 'product_name', 'price')
                ->orderBy('product_name')
                ->get();
        }

        // Generate invoice number dengan logika yang benar (INV-{urutan}{bulan}{tahun})
        $today = now()->timezone('Asia/Jakarta');
        $datePart = $today->format('my');

        $todayPattern = 'INV-___' . $datePart;

        $lastInvoiceThisMonth = Invoice::where('invoice_number', 'LIKE', $todayPattern)
            ->orderByDesc('invoice_number')
            ->first();

        if (!$lastInvoiceThisMonth) {
            $sequenceNumber = 1;
        } else {
            $existingInvoiceNumber = $lastInvoiceThisMonth->invoice_number;
            $sequenceStr = substr($existingInvoiceNumber, 4, 3);
            $lastSequence = (int) $sequenceStr;
            $sequenceNumber = $lastSequence + 1;
        }

        $invoiceNumber = 'INV-' . str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT) . $datePart;

        return $form
            ->schema([
                Hidden::make('grand_total')
                    ->reactive()
                    ->default(0),

                Hidden::make('dp')
                    ->default(0),

                \Filament\Forms\Components\Hidden::make('status')->default('unpaid'),

                Section::make('Invoice Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        \Filament\Forms\Components\Fieldset::make('Informasi Customer')
                            ->schema([
                                Select::make('name_customer')
                                    ->label('Customer Name')
                                    ->options($customerOptionsCache) // Use local cache
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Customer::where('nama_customer', 'like', $search . '%')
                                            ->select('nama_customer', 'nomor_customer')
                                            ->limit(10)
                                            ->orderBy('nama_customer')
                                            ->get()
                                            ->mapWithKeys(fn($customer) => [
                                                $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                                            ]);
                                    })
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->live()
                                    ->prefixIcon('heroicon-o-user')
                                    ->createOptionForm([
                                        TextInput::make('nama_customer')
                                            ->label('Nama Customer')
                                            ->required()
                                            ->unique(table: 'customers', column: 'nama_customer'),
                                        TextInput::make('nomor_customer')
                                            ->label('Nomor Telepon')
                                            ->required()
                                            ->unique(table: 'customers', column: 'nomor_customer')
                                            ->prefix('+62')
                                            ->mask('9999-9999-9999'),
                                        TextInput::make('email_customer')
                                            ->label('Email')
                                            ->email()
                                            ->nullable(),
                                        Textarea::make('alamat_customer')
                                            ->label('Alamat')
                                            ->columnSpanFull()
                                            ->nullable(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $customer = Customer::create([
                                            'nama_customer' => $data['nama_customer'],
                                            'nomor_customer' => $data['nomor_customer'],
                                            'email_customer' => $data['email_customer'],
                                            'alamat_customer' => $data['alamat_customer'],
                                        ]);
                                        return $customer->nama_customer;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $customer = Customer::where('nama_customer', $state)
                                            ->select('nama_customer', 'nomor_customer', 'email_customer', 'alamat_customer')
                                            ->first();
                                        if ($customer) {
                                            $set('customer_phone', $customer->nomor_customer);
                                            $set('customer_email', $customer->email_customer);
                                            $set('alamat_customer', $customer->alamat_customer);
                                        }
                                    })
                                    ->columnSpan(1),

                                TextInput::make('customer_phone')
                                    ->label('Nomor Telepon')
                                    ->required()
                                    ->prefix('+62')
                                    ->mask('9999-9999-9999')
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-phone')
                                    ->columnSpan(1),

                                TextInput::make('customer_email')
                                    ->label('Email Customer')
                                    ->email()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->columnSpan(1),

                                Textarea::make('alamat_customer')
                                    ->label('Alamat Customer')
                                    ->disabled()
                                    ->dehydrated()
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])->columns(3),

                        \Filament\Forms\Components\Fieldset::make('Informasi Dokumen')
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->dehydrated()
                                    ->default($invoiceNumber)
                                    ->columnSpan(1),

                                \Filament\Forms\Components\DatePicker::make('created_at')
                                    ->label('Tanggal Invoice')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d M Y')
                                    ->format('Y-m-d H:i:s')
                                    ->columnSpan(1),

                                \Filament\Forms\Components\DatePicker::make('due_date')
                                    ->label('Jatuh Tempo (Deadline)')
                                    ->displayFormat('d M Y')
                                    ->format('Y-m-d')
                                    ->nullable()
                                    ->columnSpan(1),

                            ])->columns(3),
                    ])->columnSpanFull(),

                Section::make('Product Details')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Repeater::make('invoiceProducts')
                            ->relationship('invoiceProducts')
                            ->label('Item Pesanan')
                            ->defaultItems(1)
                            ->live()
                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? 'Item Pesanan Baru')
                            ->collapsible()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $items = $get('invoiceProducts') ?? [];
                                $grandTotal = self::calculateGrandTotal($items);
                                $set('grand_total', $grandTotal);
                            })
                            ->schema([
                                // --- HEADER: Tipe, Produk, & Qty ---
                                Grid::make(12)
                                    ->schema([
                                        ToggleButtons::make('item_type')
                                            ->label('Kategori')
                                            ->inline()
                                            ->options([
                                                'existing' => 'Produk',
                                                'custom' => 'Kustom',
                                                'banner' => 'Banner'
                                            ])
                                            ->colors([
                                                'existing' => 'primary',
                                                'custom' => 'warning',
                                                'banner' => 'success',
                                            ])
                                            ->default('existing')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('product_id', null);
                                                $set('product_name', null);
                                                $set('panjang', null);
                                                $set('lebar', null);
                                                $set('price', null);
                                                $set('total_price', null);
                                            })
                                            ->columnSpan([
                                                'default' => 12,
                                                'lg' => 3,
                                            ]),

                                        Select::make('product_id')
                                            ->label('Pilih Produk')
                                            ->options(Product::all()->pluck('product_name', 'id'))
                                            ->searchable()
                                            ->live()
                                            ->reactive()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->requiredIf('item_type', fn(Get $get) => in_array($get('item_type'), ['existing', 'banner']))
                                            ->visible(fn(Get $get): bool => in_array($get('item_type'), ['existing', 'banner']))
                                            ->columnSpan([
                                                'default' => 12,
                                                'lg' => 7,
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state) return;
                                                $product = Product::find($state);
                                                if ($product) {
                                                    $set('product_name', $product->product_name);
                                                    self::updateItemAndGrandTotal($set, $get);
                                                }
                                            }),

                                        TextInput::make('product_name')
                                            ->label('Nama Item Kustom')
                                            ->live(onBlur: true)
                                            ->dehydrated()
                                            ->requiredIf('item_type', 'custom')
                                            ->visible(fn(Get $get): bool => $get('item_type') === 'custom')
                                            ->columnSpan([
                                                'default' => 12,
                                                'lg' => 7,
                                            ]),

                                        TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live(onBlur: true)
                                            ->prefixIcon('heroicon-m-hashtag')
                                            ->columnSpan([
                                                'default' => 12,
                                                'lg' => 2,
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                self::updateItemAndGrandTotal($set, $get);
                                            }),
                                    ]),

                                // --- DETAIL: Ukuran & Keterangan ---
                                Grid::make(12)
                                    ->schema([
                                        TextInput::make('panjang')
                                            ->label('Panjang (m)')
                                            ->numeric()
                                            ->requiredIf('item_type', 'banner')
                                            ->visible(fn(Get $get): bool => $get('item_type') === 'banner')
                                            ->live(onBlur: true)
                                            ->columnSpan([
                                                'default' => 6,
                                                'lg' => 2,
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                self::updateItemAndGrandTotal($set, $get);
                                            }),

                                        TextInput::make('lebar')
                                            ->label('Lebar (m)')
                                            ->numeric()
                                            ->requiredIf('item_type', 'banner')
                                            ->visible(fn(Get $get): bool => $get('item_type') === 'banner')
                                            ->live(onBlur: true)
                                            ->columnSpan([
                                                'default' => 6,
                                                'lg' => 2,
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                self::updateItemAndGrandTotal($set, $get);
                                            }),

                                        TextInput::make('keterangan')
                                            ->label('Keterangan / Catatan Tambahan')
                                            ->placeholder('Contoh: Laminating, Finishing, dll.')
                                            ->nullable()
                                            ->columnSpan(fn(Get $get) => $get('item_type') === 'banner' ? 8 : 12),
                                    ]),

                                // --- FOOTER: Artwork & Financials ---
                                Section::make()
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                FileUpload::make('file_path')
                                                    ->label('Upload File Artwork')
                                                    ->directory('artwork-invoices')
                                                    ->acceptedFileTypes(['application/pdf', 'image/tiff', 'image/jpeg', 'image/png', 'image/bmp'])
                                                    ->imagePreviewHeight('80')
                                                    ->panelAspectRatio('6:1')
                                                    ->columnSpan([
                                                        'default' => 12,
                                                        'lg' => 7,
                                                    ]),

                                                Group::make([
                                                    TextInput::make('price')
                                                        ->label('Harga Satuan')
                                                        ->prefix('Rp')
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->disabled(fn(Get $get) => in_array($get('item_type'), ['existing', 'banner']))
                                                        ->dehydrated()
                                                        ->extraInputAttributes([
                                                            'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                                            'inputmode' => 'numeric',
                                                        ])
                                                        ->formatStateUsing(function ($state) {
                                                            if (empty($state)) return '0';
                                                            return number_format((float)$state, 0, ',', '.');
                                                        })
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                            $cleanPrice = $state ? (float) preg_replace('/[^0-9]/', '', $state) : 0;
                                                            $set('price', $cleanPrice);
                                                            self::updateItemAndGrandTotal($set, $get);
                                                        })
                                                        ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),

                                                    TextInput::make('total_price')
                                                        ->label('Subtotal Per Item')
                                                        ->prefix('Rp')
                                                        ->disabled()
                                                        ->dehydrated()
                                                        ->extraInputAttributes([
                                                            'style' => 'font-weight: 800; font-size: 1.1rem; color: #10b981;',
                                                            'x-init' => '$nextTick(() => { let v = String($el.value).replace(/\D/g, ""); if(v) $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, "."); })',
                                                            'x-effect' => 'let v = String($el.value).replace(/\D/g, ""); if(v) $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                                        ])
                                                        ->formatStateUsing(function ($state) {
                                                            if (empty($state)) return '0';
                                                            return number_format((float)$state, 0, ',', '.');
                                                        }),
                                                ])
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 5,
                                                ])
                                                ->columns(1),
                                            ]),
                                    ])
                                    ->compact()
                                    ->extraAttributes(['class' => 'bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border-dashed']),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Ringkasan Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('total_label')
                                    ->label('Informasi Pesanan')
                                    ->content(fn (Get $get) => 'Tersedia ' . count($get('invoiceProducts') ?? []) . ' jenis barang dalam invoice ini.'),
                                
                                TextInput::make('grand_total')
                                    ->label('Total Pembayaran')
                                    ->disabled()
                                    ->prefix('Rp')
                                    ->dehydrated()
                                    ->columnSpan(2)
                                    ->extraInputAttributes([
                                        'style' => 'font-size: 2.25rem; font-weight: 900; text-align: right; color: #fbbf24;',
                                        'x-init' => '$nextTick(() => { let v = String($el.value).replace(/\D/g, ""); if(v) $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, "."); })',
                                        'x-effect' => 'let v = String($el.value).replace(/\D/g, ""); if(v) $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                    ])
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) return '0';
                                        return number_format((float)$state, 0, ',', '.');
                                    }),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_customer')
                    ->label('Customer Name')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('productSummary')
                    ->label('Products')
                    ->tooltip(fn($record) => $record->productFullList),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date < now() && $record->status === 'unpaid' ? 'danger' : null)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ]),

                Tables\Filters\SelectFilter::make('month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereMonth('created_at', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // ✅ UBAH INI: Kita eager load relasi yang benar
                // yang digunakan oleh accessor `productSummary`
                return $query->with('invoiceProducts.product');
            })
            ->recordAction(null)
            ->deferLoading()
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Kalkulasi ulang grand total sebelum create
        $invoiceProducts = $data['invoiceProducts'] ?? [];
        $data['grand_total'] = self::calculateGrandTotal($invoiceProducts);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Kalkulasi ulang grand total sebelum save
        $invoiceProducts = $data['invoiceProducts'] ?? [];
        $data['grand_total'] = self::calculateGrandTotal($invoiceProducts);

        return $data;
    }
}
