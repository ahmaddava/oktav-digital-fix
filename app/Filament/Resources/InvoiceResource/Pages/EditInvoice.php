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

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                    
                                ToggleButtons::make('status')
                                    ->label('Payment Status')
                                    ->options([
                                        'paid' => 'Paid',
                                        'unpaid' => 'Unpaid',
                                    ])
                                    ->colors([
                                        'paid' => 'success',
                                        'unpaid' => 'danger',
                                    ])
                                    ->icons([
                                        'paid' => 'heroicon-s-check-circle',
                                        'unpaid' => 'heroicon-s-exclamation-circle',
                                    ])
                                    ->inline()
                                    ->required(),
                                    
                                ToggleButtons::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'transfer' => 'Transfer',
                                        'cash' => 'Cash',
                                    ])
                                    ->colors([
                                        'transfer' => 'info',
                                        'cash' => 'warning',
                                    ])
                                    ->icons([
                                        'transfer' => 'heroicon-s-credit-card',
                                        'cash' => 'heroicon-s-currency-dollar',
                                    ])
                                    ->inline()
                                    ->required(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name_customer')
                                    ->label('Customer Name')
                                    ->required(),
                                    
                                TextInput::make('customer_phone')
                                    ->label('Phone Number')
                                    ->prefix('+62')
                                    ->required()
                                    ->mask('9999-9999-9999'),
                                    
                                TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->nullable(),
                                    
                                TextInput::make('alamat_customer')
                                    ->label('Address')
                                    ->nullable(),
                            ]),
                            
                        TextInput::make('grand_total')
                            ->label('Total Amount')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('Rp ')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->columnSpanFull(),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                    
                    Section::make('Product Details')
                    ->schema([
                        Repeater::make('invoiceProducts')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::query()->pluck('product_name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (!$state) return;
                                        
                                        $product = Product::find($state);
                                        if (!$product) return;
                                        
                                        $quantity = (int) $get('quantity');
                                        if ($quantity > 0) {
                                            $price = $product->getPriceByQuantity($quantity);
                                            $set('price', $price);
                                            $set('total_price', $price * $quantity);
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                    
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $quantity = (int) $state;
                                        $productId = $get('product_id');
                                        
                                        if (!$productId || $quantity <= 0) return;
                                        
                                        $product = Product::find($productId);
                                        if (!$product) return;
                                        
                                        $price = $product->getPriceByQuantity($quantity);
                                        $set('price', $price);
                                        $set('total_price', $price * $quantity);
                                    }),
                                    
                                TextInput::make('price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Rp ')
                                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                                    
                                TextInput::make('total_price')
                                    ->label('Total Price')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Rp ')
                                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => 
                                Product::find($state['product_id'])?->product_name . 
                                ' (Qty: ' . ($state['quantity'] ?? 1) . ')'
                            )
                            ->reorderable(true)
                            ->defaultItems(1)
                            ->rules([
                                'array',
                                'min:1' // Minimal 1 produk
                            ])
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            )
                            ->addActionLabel('Add Product')
                    ])
            ]);
    }

    // Override method mutateFormDataBeforeSave
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Tidak perlu menghitung grand_total di sini karena akan dihitung di afterSave
        return $data;
    }

    // Override method untuk menangani update
    protected function afterSave(): void
    {
        // Sync produk dengan perhitungan harga
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
            // Sync produk dengan price dan total_price
            $this->record->products()->sync($productsSync);
            
            // Update grand_total di invoice
            $grandTotal = array_sum(array_column($productsSync, 'total_price'));
            $this->record->update(['grand_total' => $grandTotal]);
        });
        
        // Tampilkan notifikasi berhasil
        Notification::make()
            ->title('Invoice updated successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            ...$this->record->toArray(),
            'invoiceProducts' => $this->record->invoiceProducts->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total_price' => $item->total_price
                ];
            })->toArray()
        ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab(),
                
            Actions\DeleteAction::make()->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // Nonaktifkan notifikasi bawaan
    protected function getSavedNotification(): ?Notification
    {
        return null; // Nonaktifkan notifikasi bawaan "Saved"
    }
}