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

class InvoiceResource extends Resource implements HasShieldPermissions
{
    // Variable untuk menyimpan data customer dan produk
    protected static $customerOptions = null;

    protected static ?string $navigationGroup = 'Management';

    protected static $productOptions = null;

    protected static function bootHasTable(): void
    {
        parent::bootHasTable();
        
        if (self::$productOptions === null) {
            self::$productOptions = Product::with('prices')->get();
        }
    }
    
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        if (self::$productOptions === null) {
            self::$productOptions = Product::with('prices')->get();
        }

        // Ambil opsi customer sekali saja
        if (self::$customerOptions === null) {
            self::$customerOptions = Customer::select('nama_customer', 'nomor_customer')
                ->orderBy('nama_customer')
                ->get()
                ->mapWithKeys(fn ($customer) => [
                    $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                ]);
        }
        
        // Ambil opsi produk sekali saja
        if (self::$productOptions === null) {
            self::$productOptions = Product::select('id', 'product_name', 'price')
                ->orderBy('product_name')
                ->get();
        }
        
        // Generate invoice number langsung tanpa cache
        $lastInvoice = Invoice::select('sequence_number')
            ->latest('sequence_number')
            ->first();
        $datePart = now()->timezone('Asia/Jakarta')->format('dmy');
        $sequenceNumber = $lastInvoice ? $lastInvoice->sequence_number + 1 : 1;
        $invoiceNumber = 'INV-'.str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT).$datePart;

        return $form
            ->schema([
                Hidden::make('grand_total')
                    ->reactive()
                    ->default(0),
                    
                // Set default values for payment fields
                Hidden::make('status')
                    ->default('unpaid'),
                Hidden::make('payment_method')
                    ->default('transfer'),
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
                                            ->unique()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default($invoiceNumber),
                                        
                                        Select::make('name_customer')
                                            ->label('Customer Name')
                                            ->options(self::$customerOptions)
                                            ->searchable()
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Customer::where('nama_customer', 'like', $search . '%')
                                                    ->select('nama_customer', 'nomor_customer')
                                                    ->limit(10)
                                                    ->orderBy('nama_customer')
                                                    ->get()
                                                    ->mapWithKeys(fn ($customer) => [
                                                        $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                                                    ]);
                                            })
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->searchDebounce(300)
                                            ->prefixIcon('heroicon-o-user')
                                            ->createOptionForm([
                                                TextInput::make('nama_customer')
                                                    ->label('Nama Customer')
                                                    ->required()
                                                    ->unique(
                                                        table: 'customers',
                                                        column: 'nama_customer'
                                                    ),
                                                TextInput::make('nomor_customer')
                                                    ->label('Nomor Telepon')
                                                    ->required()
                                                    ->unique(
                                                        table: 'customers', 
                                                        column: 'nomor_customer'
                                                    )
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
                                                
                                                // Refresh variabel statis
                                                self::$customerOptions = null;
                                                
                                                return $customer->nama_customer;
                                            })
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Hindari query database dengan mencari di cache
                                                $customer = Customer::where('nama_customer', $state)
                                                    ->select('nama_customer', 'nomor_customer', 'email_customer', 'alamat_customer')
                                                    ->first();
                                                
                                                if($customer) {
                                                    $set('customer_phone', $customer->nomor_customer);
                                                    $set('customer_email', $customer->email_customer);
                                                    $set('alamat_customer', $customer->alamat_customer);
                                                }
                                            })
                                            ->prefixIcon('heroicon-o-user'),
                                    ]),
                                    
                                    // Baris 2: Nomor Telepon
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
                                    
                                    // Baris 3: Email dan Alamat
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
                                    
                                    // Catatan
                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->rows(3)
                                        ->columnSpanFull()
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
                                        
                                        // Ambil dari variabel products yang sudah dikumpulkan
                                        $product = self::$productOptions->firstWhere('id', $state['product_id']);
                                        return $product ? $product->product_name : null;
                                    })
                                    ->afterStateUpdated(function ($get, $set) {
                                        // Hindari rekursi dengan flag
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
                                                    // Get all selected product IDs from other repeater items
                                                    $allItems = $get('../../invoiceProducts') ?: [];
                                                    $selectedProductIds = [];
                                                    
                                                    // Get the uuid of the current repeater item from context
                                                    $currentItemKey = $context;
                                                    
                                                    // Collect all selected product IDs except the current one
                                                    foreach ($allItems as $itemKey => $item) {
                                                        if ($itemKey !== $currentItemKey && isset($item['product_id']) && $item['product_id']) {
                                                            $selectedProductIds[] = $item['product_id'];
                                                        }
                                                    }
                                                    
                                                    // Filter out already selected products
                                                    $availableProducts = self::$productOptions
                                                        ->whereNotIn('id', $selectedProductIds)
                                                        ->pluck('product_name', 'id')
                                                        ->toArray();
                                                    
                                                    // If this item already has a selection, ensure it's included
                                                    if ($state && !in_array($state, $selectedProductIds)) {
                                                        $currentProduct = self::$productOptions->firstWhere('id', $state);
                                                        if ($currentProduct) {
                                                            $availableProducts[$state] = $currentProduct->product_name;
                                                        }
                                                    }
                                                    
                                                    return $availableProducts;
                                                })
                                                    ->searchable()
                                                    ->required()
                                                    ->reactive()
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        if (!$state) return;
                                                        
                                                        // Get from cached product options
                                                        $product = self::$productOptions->firstWhere('id', $state);
                                                        
                                                        if (!$product) return;
                                                        
                                                        // Set base price first
                                                        $price = $product->price;
                                                        $set('price', $price);
                                                        
                                                        // If quantity already exists, calculate based on that quantity
                                                        $quantity = (int) $get('quantity');
                                                        if ($quantity > 0) {
                                                            $priceByQuantity = $product->getPriceByQuantity($quantity);
                                                            $set('price', $priceByQuantity);
                                                            $set('total_price', $priceByQuantity * $quantity);
                                                        } else {
                                                            $set('total_price', 0);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                            TextInput::make('quantity')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->required()
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $quantity = (int) $state;
                                                    $productId = $get('product_id');
                                                    
                                                    if (!$productId || $quantity <= 0) {
                                                        $set('total_price', 0);
                                                        return;
                                                    }
                                                    
                                                    // Use cached products for better performance
                                                    $product = self::$productOptions->firstWhere('id', $productId);
                                                    
                                                    if (!$product) {
                                                        // Fallback to database query if not found in cache
                                                        $product = Product::with('prices')->find($productId);
                                                        
                                                        if (!$product) {
                                                            $set('total_price', 0);
                                                            return;
                                                        }
                                                    }
                                                    
                                                    // Use the getPriceByQuantity method from your model
                                                    $price = $product->getPriceByQuantity($quantity);
                                                    
                                                    // Set updated price and calculate total
                                                    $set('price', $price);
                                                    $set('total_price', $quantity * $price);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('price')
                                                ->label('Harga')
                                                ->disabled()
                                                ->prefix('Rp ')
                                                ->dehydrated(true)
                                                ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                                                ->reactive()
                                                ->columnSpan(1),
                                            TextInput::make('total_price')
                                                ->label('Total')
                                                ->disabled()
                                                ->prefix('Rp ')
                                                ->dehydrated(true)
                                                ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                                                ->reactive()
                                                ->columnSpan(1),
                                        ]),
                                    ]),
                                
                                // Add Grand Total Display at the bottom
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
    
                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'paid' => 'heroicon-s-check-circle',
                        'unpaid' => 'heroicon-s-exclamation-circle',
                        default => '',
                    })
                    ->iconPosition(IconPosition::After)
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        default => 'Unknown',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'paid' => 'Pembayaran telah diterima',
                        'unpaid' => 'Menunggu pembayaran',
                        default => '',
                    }),
                
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'transfer' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'transfer' => 'Transfer',
                        'cash' => 'Cash',
                        default => 'N/A',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'transfer' => 'Pembayaran via Transfer',
                        'cash' => 'Pembayaran via Cash',
                        default => 'Metode pembayaran tidak diketahui',
                    }),
    
                TextColumn::make('name_customer')
                    ->label('Customer Name')
                    ->searchable(),
                
                TextColumn::make('grand_total')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),
                
                // Gunakan accessor untuk menampilkan produk
                TextColumn::make('productSummary')
                    ->label('Products')
                    ->tooltip(fn ($record) => $record->productFullList)
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
                // \Filament\Tables\Actions\Action::make('manage_payment')
                //     ->label('Manage Payment')
                //     ->icon('heroicon-o-credit-card')
                //     ->url(fn ($record) => PaymentResource::getUrl('edit', ['record' => $record]))
                //     ->color('success'),
                // \Filament\Tables\Actions\Action::make('print')
                //     ->label('Print Invoice')
                //     ->icon('heroicon-o-printer')
                //     ->url(fn ($record) => route('invoices.print', $record))
                //     ->openUrlInNewTab()
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            // Query Optimasi - Eager load relasi sekaligus
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['products' => function($q) {
                    $q->select('products.id', 'products.product_name', 'invoice_product.quantity', 'invoice_product.invoice_id');
                }])->select('id', 'invoice_number', 'status', 'payment_method', 'name_customer', 'grand_total', 'created_at');
            })
            ->recordAction(null)
            ->deferLoading()
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    // app/Filament/Resources/InvoiceResource.php
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'), // Tambahkan ini
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
        // Pastikan grand_total terhitung sebelum menyimpan data
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
        // Pastikan grand_total terhitung sebelum update data
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