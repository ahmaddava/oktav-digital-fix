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

                    Wizard\Step::make('Order Items')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Section::make('Product Details')
                                ->schema([
                                    Repeater::make('invoiceProducts')
                                        ->relationship()
                                        ->label('')
                                        ->columns(2)
                                        ->grid(1)
                                        ->defaultItems(1)
                                        ->itemLabel(function (array $state): ?string {
                                            if (!isset($state['product_id'])) return null;
                                            static $productOptionsLocalCache = null;
                                            if ($productOptionsLocalCache === null) {
                                                $productOptionsLocalCache = Product::with('prices')
                                                    ->select('id', 'product_name', 'price')
                                                    ->orderBy('product_name')
                                                    ->get();
                                            }
                                            $product = $productOptionsLocalCache->firstWhere('id', $state['product_id']);
                                            return $product ? $product->product_name : null;
                                        })
                                        ->afterStateUpdated(function ($get, $set) {
                                            static $calculating = false;
                                            if ($calculating) return;

                                            $calculating = true;
                                            $items = $get('invoiceProducts') ?: [];
                                            $total = 0;

                                            foreach ($items as $item) {
                                                $quantity = (int)($item['quantity'] ?? 0);
                                                $price = (int)($item['price'] ?? 0);
                                                $total += $quantity * $price;
                                            }

                                            $set('grand_total', $total);
                                            $calculating = false;
                                        })
                                        ->schema([
                                            Grid::make(4)->schema([
                                                Select::make('product_id')
                                                    ->label('Product')
                                                    ->options(function (callable $get, ?string $state, ?string $context) {
                                                        static $productOptionsLocalCache = null;
                                                        if ($productOptionsLocalCache === null) {
                                                            $productOptionsLocalCache = Product::with('prices')
                                                                ->select('id', 'product_name', 'price')
                                                                ->orderBy('product_name')
                                                                ->get();
                                                        }

                                                        $allItems = $get('../../invoiceProducts') ?: [];
                                                        $selectedProductIds = [];
                                                        $currentItemKey = $context;

                                                        foreach ($allItems as $itemKey => $item) {
                                                            if ($itemKey !== $currentItemKey && isset($item['product_id']) && $item['product_id']) {
                                                                $selectedProductIds[] = $item['product_id'];
                                                            }
                                                        }

                                                        $availableProducts = $productOptionsLocalCache
                                                            ->whereNotIn('id', $selectedProductIds)
                                                            ->pluck('product_name', 'id')
                                                            ->toArray();

                                                        if ($state && !in_array($state, $selectedProductIds)) {
                                                            $currentProduct = $productOptionsLocalCache->firstWhere('id', $state);
                                                            if ($currentProduct) {
                                                                $availableProducts[$state] = $currentProduct->product_name;
                                                            }
                                                        }

                                                        return $availableProducts;
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->reactive()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        if (!$state) return;
                                                        static $productOptionsLocalCache = null;
                                                        if ($productOptionsLocalCache === null) {
                                                            $productOptionsLocalCache = Product::with('prices')
                                                                ->select('id', 'product_name', 'price')
                                                                ->orderBy('product_name')
                                                                ->get();
                                                        }

                                                        $product = $productOptionsLocalCache->firstWhere('id', $state);
                                                        if (!$product) {
                                                            $product = Product::with('prices')->find($state);
                                                            if (!$product) return;
                                                        }
                                                        $quantity = (int) $get('quantity');
                                                        if ($quantity > 0) {
                                                            $price = $product->getPriceByQuantity($quantity);
                                                            $set('price', $price);
                                                            $set('total_price', $price * $quantity);
                                                        } else {
                                                            $set('total_price', 0);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        $quantity = (int) $state;
                                                        $productId = $get('product_id');
                                                        if (!$productId || $quantity <= 0) return;
                                                        static $productOptionsLocalCache = null;
                                                        if ($productOptionsLocalCache === null) {
                                                            $productOptionsLocalCache = Product::with('prices')
                                                                ->select('id', 'product_name', 'price')
                                                                ->orderBy('product_name')
                                                                ->get();
                                                        }
                                                        $product = $productOptionsLocalCache->firstWhere('id', $productId);
                                                        if (!$product) {
                                                            $product = Product::with('prices')->find($productId);
                                                            if (!$product) return;
                                                        }
                                                        $price = $product->getPriceByQuantity($quantity);
                                                        $set('price', $price);
                                                        $set('total_price', $price * $quantity);
                                                    })
                                                    ->columnSpan(1),

                                                TextInput::make('price')
                                                    ->label('Harga')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('Rp ')
                                                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.')),

                                                TextInput::make('total_price')
                                                    ->label('Total')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('Rp ')
                                                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.')),
                                            ]),
                                        ]),

                                    Section::make('Order Summary')
                                        ->schema([
                                            TextInput::make('grand_total')
                                                ->label('Grand Total')
                                                ->disabled()
                                                ->prefix('Rp ')
                                                ->formatStateUsing(function ($state) {
                                                    return number_format((float)$state, 0, ',', '.');
                                                })
                                                ->dehydrated()
                                                ->columnSpan(1)
                                                ->required()
                                                ->numeric()
                                                ->default(0),
                                        ])
                                        ->columnSpanFull()
                                        ->compact(),
                                ])
                                ->compact()
                                ->columns(1)
                        ])
                        ->columns(1),
                ])
                    ->skippable(false)
                    ->columnSpanFull()
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
                return $query->with(['products' => function ($q) {
                    $q->select('products.id', 'products.product_name', 'invoice_product.quantity', 'invoice_product.invoice_id');
                }])->select('id', 'invoice_number', 'name_customer', 'grand_total', 'created_at');
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
        $invoiceProducts = $data['invoiceProducts'] ?? [];
        $grandTotal = 0;

        foreach ($invoiceProducts as $item) {
            if (isset($item['total_price'])) {
                $grandTotal += (int) $item['total_price'];
            }
        }

        $data['grand_total'] = $grandTotal;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $invoiceProducts = $data['invoiceProducts'] ?? [];
        $grandTotal = 0;

        foreach ($invoiceProducts as $item) {
            if (isset($item['total_price'])) {
                $grandTotal += (int) $item['total_price'];
            }
        }

        $data['grand_total'] = $grandTotal;

        return $data;
    }
}
