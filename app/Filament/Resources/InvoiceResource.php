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
use Illuminate\Support\Collection; // Import Collection

class InvoiceResource extends Resource implements HasShieldPermissions
{
    // Remove global static variables from here
    // protected static $customerOptions = null;
    // protected static $productOptions = null;

    protected static ?string $navigationGroup = 'Management';

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

        // Generate invoice number dengan logika yang benar
        $today = now()->timezone('Asia/Jakarta');
        $datePart = $today->format('dmy');

        $todayPattern = 'INV-%' . $datePart;

        $lastInvoiceToday = Invoice::where('invoice_number', 'LIKE', $todayPattern)
            ->orderByDesc('invoice_number')
            ->first();

        if (!$lastInvoiceToday) {
            $sequenceNumber = 1;
        } else {
            $existingInvoiceNumber = $lastInvoiceToday->invoice_number;
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

                Wizard::make([
                    Wizard\Step::make('Order Details')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Invoice Details')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('invoice_number')
                                            ->label('Invoice Number')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->disabled()
                                            ->dehydrated()
                                            ->default($invoiceNumber),

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
                                                $customerOptionsCache = null; // Clear local cache
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
                                            }),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('customer_phone')
                                            ->label('Nomor Telepon')
                                            ->required()
                                            ->prefix('+62')
                                            ->mask('9999-9999-9999')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-phone'),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('customer_email')
                                            ->label('Email Customer')
                                            ->email()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-envelope'),
                                        TextInput::make('alamat_customer')
                                            ->label('Alamat Customer')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-map-pin'),
                                    ]),

                                    Textarea::make('notes_invoice')
                                        ->label('Notes')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    FileUpload::make('attachment_path')
                                        ->label('Attach File')
                                        ->maxSize(1024000) // 1 GB
                                        ->acceptedFileTypes([
                                            'application/zip',
                                            'application/x-zip-compressed',
                                            'application/vnd.rar',
                                            'application/x-rar-compressed',
                                            'application/octet-stream',
                                        ])
                                        ->downloadable()
                                        ->openable()
                                        ->storeFileNamesIn('original_filename')
                                        ->preserveFilenames()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->compact(),
                        ])
                        ->columns(2),

                    Wizard\Step::make('Order Items')->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Section::make('Product Details')
                                ->schema([
                                    // REPEATER UTAMA YANG DIPERBAIKI
                                    Repeater::make('invoiceProducts')
                                        ->label('Item')
                                        ->defaultItems(1)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $items = $get('invoiceProducts') ?? [];

                                            // Kalkulasi grand total yang lebih akurat
                                            $grandTotal = self::calculateGrandTotal($items);

                                            // Set grand total tanpa formatting untuk penyimpanan
                                            $set('grand_total', $grandTotal);
                                        })
                                        ->schema([
                                            // Toggle untuk memilih tipe item
                                            ToggleButtons::make('type')
                                                ->label('Tipe Item')
                                                ->inline()
                                                ->options([
                                                    'existing' => 'Produk Terdaftar',
                                                    'custom' => 'Produk Kustom'
                                                ])
                                                ->default('existing')
                                                ->live()
                                                ->columnSpanFull(),

                                            // Select produk untuk tipe 'existing'
                                            Select::make('product_id')
                                                ->label('Produk')
                                                ->options(Product::all()->pluck('product_name', 'id'))
                                                ->searchable()
                                                ->live()
                                                ->reactive()
                                                ->requiredIf('type', 'existing')
                                                ->visible(fn(Get $get): bool => $get('type') === 'existing')
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    if (!$state) return;

                                                    // Find the selected product
                                                    $product = Product::find($state);

                                                    if ($product) {
                                                        $quantity = (float) ($get('quantity') ?: 1);

                                                        // Set the price based on the selected product
                                                        $set('price', $product->price);

                                                        //  ✅ THE KEY CHANGE IS HERE
                                                        // Set the product name as well
                                                        $set('product_name', $product->product_name);

                                                        // Recalculate the total price
                                                        $set('total_price', $product->price * $quantity);
                                                    }
                                                }),

                                            // Input nama produk untuk tipe 'custom'
                                            TextInput::make('product_name')
                                                ->label('Nama Produk Kustom')
                                                ->live(onBlur: true) // <-- DITAMBAHKAN: Untuk mengirim state ke server
                                                ->dehydrated()       // <-- DITAMBAHKAN: Wajib agar data ikut tersimpan
                                                ->requiredIf('type', 'custom')
                                                ->visible(fn(Get $get): bool => $get('type') === 'custom'),

                                            // Input quantity
                                            TextInput::make('quantity')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->required()
                                                ->placeholder('0')
                                                ->minValue(1)
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $price = (float) ($get('price') ?: 0);
                                                    $quantity = (float) ($state ?: 0);
                                                    $total = $price * $quantity;

                                                    $set('total_price', $total);
                                                }),

                                            // Input harga
                                            TextInput::make('price')
                                                ->label('Harga')
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->required()
                                                ->live()
                                                ->disabled(fn(Get $get) => $get('type') === 'existing')
                                                ->dehydrated()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    // Hitung ulang total price ketika harga berubah
                                                    $quantity = (float) ($get('quantity') ?: 1);
                                                    $price = (float) ($state ?: 0);
                                                    $total = $price * $quantity;

                                                    $set('total_price', $total);

                                                    // Trigger update grand total dengan mengambil semua items
                                                    $allItems = $get('../../invoiceProducts') ?? [];
                                                    $grandTotal = 0;
                                                    foreach ($allItems as $item) {
                                                        if (isset($item['total_price']) && !empty($item['total_price'])) {
                                                            $grandTotal += (float) $item['total_price'];
                                                        }
                                                    }
                                                    $set('../../grand_total', $grandTotal);
                                                }),

                                            // Display total harga per item
                                            TextInput::make('total_price')
                                                ->label('Total')
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->dehydrated()
                                                ->formatStateUsing(function ($state) {
                                                    if (empty($state)) return '0';
                                                    return number_format((float)$state, 0, ',', '.');
                                                }),
                                        ])
                                        ->columns(4),
                                ]),

                            Section::make('Order Summary')
                                ->schema([
                                    TextInput::make('grand_total')
                                        ->label('Grand Total')
                                        ->disabled()
                                        ->prefix('Rp ')
                                        ->dehydrated()
                                        ->formatStateUsing(function ($state) {
                                            if (empty($state)) return '0';
                                            return number_format((float)$state, 0, ',', '.');
                                        })
                                        ->numeric(),
                                ]),
                        ]),
                ])->skippable(false)->columnSpanFull(),
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
                    ->tooltip(fn($record) => $record->productFullList)
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
