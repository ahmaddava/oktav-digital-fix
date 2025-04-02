<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\InvoiceProduct;
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
                                    // ->tel()
                                    ->prefix('+62')
                                    ->required()
                                    ->mask('9999-9999-9999')
                                    // ->rule('regex:/^(\+62|62|0)8[1-9][0-9]{6,9}$/'),
                            ]),
                    ]),
                    
                    Section::make('Product Details')
                    ->schema([
                        Repeater::make('invoiceProducts')
                            ->relationship() // Gunakan relationship langsung ke pivot
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::query()->pluck('product_name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                    
                                TextInput::make('quantity') // Akses langsung field quantity
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => 
                                Product::find($state['product_id'])?->product_name . 
                                ' (Qty: ' . ($state['quantity'] ?? 1) . ')'
                            )
                            ->reorderable(true)
                            ->rules([
                                'array',
                                'min:1' // Minimal 1 produk
                            ])
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(
                                fn (array $data): array => [
                                    'product_id' => $data['product_id'],
                                    'quantity' => $data['quantity']
                                ]
                                )
                            ->addActionLabel('Add Product')
                    ])
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::transaction(function () use ($record, $data) {
            try {
                // Update data utama
                $record->update([
                    'invoice_number' => $data['invoice_number'],
                    'status' => $data['status'] ?? 'unpaid',
                    'name_customer' => $data['name_customer'] ?? '',
                    'customer_phone' => $data['customer_phone'] ?? '',
                    'notes' => $data['notes'] ?? null
                ]);

                // Handle products
                if (isset($data['invoiceProducts']) && is_array($data['invoiceProducts'])) {
                    // Validasi data sebelum proses
                    $validProducts = [];
                    foreach ($data['invoiceProducts'] as $product) {
                        if (!empty($product['product_id']) && !empty($product['quantity'])) {
                            $validProducts[] = [
                                'product_id' => $product['product_id'],
                                'quantity' => $product['quantity']
                            ];
                        }
                    }

                    // Hapus produk lama hanya jika ada produk baru yang valid
                    if (!empty($validProducts)) {
                        $record->invoiceProducts()->delete();
                        
                        // Insert produk baru
                        foreach ($validProducts as $product) {
                            $record->invoiceProducts()->create($product);
                        }
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception('Gagal menyimpan invoice: ' . $e->getMessage());
            }
        });
        
        return $record;
    }

protected function fillForm(): void
{
    $this->form->fill([
        ...$this->record->toArray(),
        'invoiceProducts' => $this->record->invoiceProducts->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity
            ];
        })->toArray()
    ]);
}
protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('print')
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
}