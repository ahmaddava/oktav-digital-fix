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
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Facades\Date;
use Closure;
use App\Filament\Resources\InvoiceResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Wizard;
use App\Models\Customer;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class InvoiceResource extends Resource implements HasShieldPermissions
{
    // ...

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            
        ];
    }
    protected static ?string $model = Invoice::class;

    protected static string $resource = InvoiceResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('grand_total')
                    ->reactive()
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
                                            ->default(function () {
                                                $lastInvoice = \App\Models\Invoice::latest('sequence_number')->first();
                                                $datePart = now()->timezone('Asia/Jakarta')->format('dmy');
                                                $sequenceNumber = $lastInvoice ? $lastInvoice->sequence_number + 1 : 1;
                                                return 'INV-'.str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT).$datePart;
                                            }),
                                        
                                        Select::make('name_customer')
                                            ->label('Customer Name')
                                            ->options(function () {
                                                return Customer::all()->mapWithKeys(fn ($customer) => [
                                                    $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                                                ]);
                                            })
                                            ->searchable()
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Customer::where('nama_customer', 'like', $search . '%')
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(fn ($customer) => [
                                                        $customer->nama_customer => "{$customer->nama_customer} - {$customer->nomor_customer}"
                                                    ]);
                                            })
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            // ->searchDebounce(500) // Delay 500ms setelah berhenti ketik
                                            ->prefixIcon('heroicon-o-user')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->createOptionForm([
                                                TextInput::make('nama_customer')
                                                    ->label('Nama Customer')
                                                    ->required()
                                                    ->unique( // PERBAIKAN DI SINI
                                                        table: 'customers',
                                                        column: 'nama_customer'
                                                    ),
                                                TextInput::make('nomor_customer')
                                                    ->label('Nomor Telepon')
                                                    ->required()
                                                    ->unique( // DAN DI SINI
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
                                                
                                                return $customer->nama_customer;
                                            })
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $customer = Customer::where('nama_customer', $state)->first();
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
                                        ->label('')
                                        ->columns(2)
                                        ->grid(1)
                                        ->defaultItems(1)
                                        ->itemLabel(fn (array $state): ?string => Product::find($state['product_id'])?->product_name)
                                        ->afterStateUpdated(function ($get, $set) {
                                            // Hitung grand total saat ada perubahan di repeater
                                            $total = collect($get('invoiceProducts'))->sum(function ($item) {
                                                return ((int)($item['quantity'] ?? 0) * (int)($item['price'] ?? 0));
                                            });
                                            $set('grand_total', $total);
                                        })
                                        ->schema([
                                            Grid::make(3)->schema([
                                                Select::make('product_id')
                                                    ->label('Product')
                                                    ->options(Product::all()->pluck('product_name', 'id'))
                                                    ->searchable()
                                                    ->required()
                                                    ->reactive()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        $product = Product::find($state);
                                                        $price = $product ? $product->price : 0;
                                                        $set('price', $price);
                                                        $set('total_price', 0 * $price); // Reset total saat ganti produk
                                                    })
                                                    ->columnSpan(1),
                    
                                                TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->minValue(1)
                                                    ->live(debounce: 500)
                                                    ->columnSpan(1)
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        $price = (int)$get('price');
                                                        $set('total_price', $state * $price);
                                                        
                                                        // Trigger update grand total
                                                        $set('../../grand_total', collect($get('../../invoiceProducts'))->sum(
                                                            fn($item) => $item['quantity'] * $item['price']
                                                        ));
                                                    }),
                    
                                                TextInput::make('price')
                                                    ->label('Harga')
                                                    ->disabled()
                                                    ->prefix('Rp ')
                                                    ->dehydrated()
                                                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                                                    ->reactive()
                                                    ->columnSpan(1),
                                            ]),
                                        ])
                                ])
                                ->compact()
                        ])
                        ->columns(1), 
                         // Wizard 3: Payment
                        Wizard\Step::make('Payment')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Payment Details')
                                    ->schema([
                                        // Total Harga
                                        TextInput::make('grand_total')
                                        ->label('Total Harga')
                                        ->disabled()
                                        ->prefix('Rp ')
                                        ->formatStateUsing(function ($state) {
                                            return number_format((float)$state, 0, ',', '.'); // ubah ke float
                                        })
                                        ->dehydrated()
                                        ->columnSpan(1)
                                        ->required() // tambahkan validasi
                                        ->numeric() // pastikan input numerik
                                        ->default(0),
        
                                        // Status Pembayaran
                                    ToggleButtons::make('status')
                                         ->label('Status')
                                        ->options([
                                            'paid' => 'Paid',
                                            'unpaid' => 'Unpaid',
                                        ])
                                        ->required()
                                        ->inline()
                                        ->colors([
                                            'paid' => 'success',
                                            'unpaid' => 'danger',
                                        ])
                                        ->icons([
                                            'paid' => 'heroicon-s-check-circle',
                                            'unpaid' => 'heroicon-s-exclamation-circle',
                                        ])
                                        ->default('unpaid')
                                        ->reactive(),
        
                                        // Jenis Pembayaran
                                        ToggleButtons::make('payment_method')
                                            ->label('Jenis Pembayaran')
                                            ->options([
                                                'transfer' => 'Transfer',
                                                'cash' => 'Cash',
                                            ])
                                            ->required()
                                            ->inline()
                                            ->colors([
                                                'transfer' => 'info',
                                                'cash' => 'warning',
                                            ])
                                            ->icons([
                                                'transfer' => 'heroicon-s-credit-card',
                                                'cash' => 'heroicon-s-currency-dollar',
                                            ])
                                            ->default('transfer'),
        
                                        // Down Payment (DP)
                                        TextInput::make('dp')
                                            ->label('Down Payment (DP)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('Masukkan jumlah DP')
                                            ->hidden(function ($get) {
                                                return $get('status') !== 'unpaid'; // Hanya muncul jika status adalah "unpaid"
                                            }),
                                    ])
                                    ->columns(2)
                                    ->compact(),
                            ])
                            ->columns(1),
                    ])
                    ->skippable(false)
                    ->columnSpanFull() // Full width wizard
                ]);
        }

    
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Menampilkan kolom untuk invoice_number
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->sortable(),
    
                // Menampilkan kolom untuk status
                TextColumn::make('status')
                ->label('Payment Status')
                ->badge()
                ->icon(fn (string $state): string => match ($state) {
                    'paid' => 'heroicon-s-check-circle',
                    'unpaid' => 'heroicon-s-exclamation-circle',
                })
                ->iconPosition(IconPosition::After)
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'unpaid' => 'danger',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'paid' => 'Paid',
                    'unpaid' => 'Unpaid',
                })
                ->sortable()
                ->searchable()
                ->toggleable()
                ->tooltip(fn (string $state): string => match ($state) {
                    'paid' => 'Pembayaran telah diterima',
                    'unpaid' => 'Menunggu pembayaran',
                }),
                TextColumn::make('payment_method')
                ->label('Payment Method')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'transfer' => 'info',
                    'cash' => 'warning',
                    default => 'gray', // Warna default jika tidak ada nilai
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'transfer' => 'Transfer',
                    'cash' => 'Cash',
                    default => 'N/A', // Default jika tidak ada nilai
                })
                ->sortable()
                ->searchable()
                ->toggleable()
                ->tooltip(fn (string $state): string => match ($state) {
                    'transfer' => 'Pembayaran via Transfer',
                    'cash' => 'Pembayaran via Cash',
                    default => 'Metode pembayaran tidak diketahui',
                }),
    
                // Menampilkan kolom untuk nama customer
                TextColumn::make('name_customer')
                    ->label('Customer Name')
                    ->searchable(),
                TextColumn::make('products')
                ->label('Products')
                ->getStateUsing(function ($record) {
                    $products = $record->products;
                    $limit = 2; // Batasi jumlah produk yang ditampilkan
                    $displayProducts = $products->take($limit)->map(function ($product) {
                        return $product->product_name . ' (Qty: ' . $product->pivot->quantity . ')';
                    })->implode(', ');

                    if ($products->count() > $limit) {
                        $remaining = $products->count() - $limit;
                        $displayProducts .= ' +' . $remaining . ' more';
                    }

                    return $displayProducts;
                })
                ->tooltip(function ($record) {
                    // Tampilkan semua produk dalam tooltip
                    return $record->products->map(function ($product) {
                        return $product->product_name . ' (Qty: ' . $product->pivot->quantity . ')';
                    })->implode("\n");
                }),
            ])
            ->filters([
                // Menambahkan filter jika perlu, misalnya berdasarkan status
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
                            $query->whereMonth('created_at', $data['value']); // Gunakan kolom yang ada
                        }
                    }),
            ])
            ->actions([
                // Menambahkan aksi seperti Edit, Delete, dll.
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                \Filament\Tables\Actions\Action::make('print')
                    ->label('Print Invoice')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('invoices.print', $record))
                    ->openUrlInNewTab()
                    ->action(function ($record) {
                        // Simpan data sebelum mencetak
                        $record->save();
                    }),
            ])
            ->bulkActions([
                // Menambahkan aksi bulk, misalnya untuk menghapus beberapa item
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text'; // Icon invoice
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('status', $search); // Exact match
    }
}
