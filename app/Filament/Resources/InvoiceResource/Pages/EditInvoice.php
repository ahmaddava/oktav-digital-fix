<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Product;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\InvoiceResource;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Wizard;
use App\Models\Customer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection; // Import Collection

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    // REMOVE these static properties from EditInvoice.php
    // protected static $customerOptions = null;
    // protected static $productOptions = null;

    // We no longer need to initialize static options in mount()
    public function mount(int | string $record): void
    {
        parent::mount($record);
    }

    public function form(Form $form): Form
    {
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
                                            ->dehydrated(),

                                        Select::make('name_customer')
                                            ->label('Customer Name')
                                            // Provide options dynamically using a closure.
                                            // This ensures it's always fresh or re-fetched if null.
                                            ->options(function () {
                                                static $customerOptionsCache = null; // Local cache for options
                                                if ($customerOptionsCache === null) {
                                                    $customerOptionsCache = Customer::select('nama_customer', 'nomor_customer')
                                                        ->orderBy('nama_customer')
                                                        ->get()
                                                        ->mapWithKeys(fn($customer) => [
                                                            $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                                                        ]);
                                                }
                                                return $customerOptionsCache;
                                            })
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
                                                // Clear local cache for new options to appear
                                                $customerOptionsCache = null;
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
                                            // Get product options locally within the closure
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
                                        ->afterStateUpdated(function (Get $get, Set $set) {
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
                                                    // Provide options dynamically and locally cached
                                                    ->options(function (Get $get, ?string $state, ?string $context) {
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
                                                    ->label('Unit Price')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('Rp ')
                                                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.')),

                                                TextInput::make('total_price')
                                                    ->label('Total Price')
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $invoiceProducts = $data['invoiceProducts'] ?? [];
        $grandTotal = 0;

        foreach ($invoiceProducts as $item) {
            if (isset($item['product_id']) && isset($item['quantity'])) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $quantity = (int) $item['quantity'];
                    $price = $product->getPriceByQuantity($quantity);
                    $grandTotal += $price * $quantity;
                }
            }
        }

        $data['grand_total'] = $grandTotal;

        return $data;
    }

    protected function afterSave(): void
    {
        $productsSync = [];

        foreach ($this->data['invoiceProducts'] as $item) {
            if (isset($item['product_id']) && isset($item['quantity'])) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $quantity = (int) $item['quantity'];
                    $price = $product->getPriceByQuantity($quantity);
                    $totalPrice = $price * $quantity;

                    $productsSync[$item['product_id']] = [
                        'quantity' => $quantity,
                        'price' => $price,
                        'total_price' => $totalPrice
                    ];
                }
            }
        }

        DB::transaction(function () use ($productsSync) {
            $this->record->products()->sync($productsSync);
            $grandTotal = array_sum(array_column($productsSync, 'total_price'));
            $this->record->update(['grand_total' => $grandTotal]);
        });

        Notification::make()
            ->title('Invoice updated successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'invoice_number' => $this->record->invoice_number,
            'dp' => $this->record->dp,
            'name_customer' => $this->record->name_customer,
            'customer_phone' => $this->record->customer_phone,
            'customer_email' => $this->record->customer_email,
            'alamat_customer' => $this->record->alamat_customer,
            'notes_invoice' => $this->record->notes_invoice,
            'attachment_path' => $this->record->attachment_path,
            'grand_total' => $this->record->grand_total,

            'invoiceProducts' => $this->record->products->map(function ($product) {
                return [
                    'id' => $product->pivot->id,
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                    'total_price' => $product->pivot->total_price
                ];
            })->toArray()
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }
}
