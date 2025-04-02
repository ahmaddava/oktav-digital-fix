<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms\Form;
use App\Models\Production;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\ProductionResource\Widgets\ProductionStats;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductionResource\Pages\EditProduction;
use App\Filament\Resources\ProductionResource\Pages\ListProductions;
use App\Filament\Resources\ProductionResource\Pages\CreateProduction;
use App\Filament\Resources\ProductionResource\Widgets\ProductionFilterWidget;
use Filament\Tables\Concerns\InteractsWithTable;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ProductionResource extends Resource implements HasShieldPermissions
{
    // ...

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            'complete',
        ];
    }
    protected static ?string $model = Production::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Production';
    protected static ?string $navigationGroup = 'Manufacturing';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('machine_type')
                ->label('Tipe Mesin')
                ->required()
                ->options([
                    Production::MESIN_1 => 'Mesin 1',
                    Production::MESIN_2 => 'Mesin 2',
                ])
                ->native(false)
                ->columnSpanFull(),
                Select::make('invoice_id')
                    ->label('Pilih Invoice')
                    ->required()
                    ->searchable()
                    ->options(function () {
                        return Invoice::whereHas('products', function($query) {
                                $query->where('type', Product::TYPE_DIGITAL_PRINT);
                            })
                            ->whereDoesntHave('production', function($query) {
                                $query->where('status', 'completed');
                            })
                            ->get()
                            ->mapWithKeys(fn ($invoice) => [
                                $invoice->id => $invoice->invoice_number.' - '.$invoice->name_customer
                            ]);
                    })
                    ->reactive()
                    ->columnSpanFull(),
                
                Placeholder::make('products')
                    ->label('Daftar Produksi')
                    ->content(function ($get) {
                        $invoiceId = $get('invoice_id');
                        
                        if (!$invoiceId) return 'Pilih invoice terlebih dahulu';
                        
                        $invoice = Invoice::with('products')->find($invoiceId);
                        
                        return $invoice->products
                            ->where('type', Product::TYPE_DIGITAL_PRINT)
                            ->map(fn ($product) => 
                                "{$product->product_name} (Qty: {$product->pivot->quantity})"
                            )->implode(', ');
                    })
                    ->columnSpanFull(),
                    
                TextInput::make('failed_prints')
                    ->label('Cetakan Gagal')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->maxValue(function ($get) {
                        if ($invoiceId = $get('invoice_id')) {
                            $invoice = Invoice::find($invoiceId);
                            return $invoice->products
                                ->where('type', Product::TYPE_DIGITAL_PRINT)
                                ->sum('pivot.quantity');
                        }
                        return 0;
                    })
                    ->helperText('Maksimal sesuai total produksi'),
                    
                Textarea::make('notes')
                    ->label('Catatan Produksi')
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice'),
    
                TextColumn::make('machine_type')
                    ->label('Tipe Mesin')
                    ->formatStateUsing(fn ($state) => match($state) {
                        Production::MESIN_1 => 'Mesin 1',
                        Production::MESIN_2 => 'Mesin 2',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        Production::MESIN_1 => 'primary',
                        Production::MESIN_2 => 'success',
                    }),
    
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    }),
    
                TextColumn::make('failed_prints')
                    ->label('Cetakan Gagal'),
    
                TextColumn::make('total_clicks')
                    ->label('Total Click')
                    ->numeric()
                    ->state(fn (Production $record) => $record->total_clicks)
                    ->suffix(' clicks'),
                                
    
                TextColumn::make('completed_at')
                    ->label('Tgl Selesai')
                    ->dateTime('d/m/Y H:i')
            ])
            ->actions([
                Action::make('process')
                    ->label('Proses Produksi')
                    ->icon('heroicon-o-cog')
                    ->color('primary')
                    ->url(fn (Production $record): string => self::getUrl('edit', [$record]))
                    ->hidden(fn ($record) => $record->status === 'completed'),
    
                Action::make('complete')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check')
                    ->action(function (Production $record) {
                        // Validasi: Hanya proses jika status pending
                        if ($record->status !== 'pending') return;

                        // Update status DAN hitung failed prints
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(), // Hapus update failed_prints di sini
                        ]);

                        // Update stok HANYA dengan failed_prints
                        foreach ($record->invoice->products as $product) {
                            if ($product->type === Product::TYPE_DIGITAL_PRINT) {
                                $product->stock = max(0, $product->stock - $record->failed_prints);
                                $product->save();
                            }
                        }
                    })
                    ->visible(fn ($record) => $record->status === 'pending')            
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('filters.created_at.from'),
                        DatePicker::make('filters.created_at.until'),
                ])
            ]);
    }

    protected static function calculateFailedPrints($productionId)
    {
        $production = Production::find($productionId);
        // Logika untuk menghitung failed prints
        $failedPrints = 0;
        // ...existing code untuk menghitung failed prints...
        return $failedPrints;
    }


    public static function getPages(): array
    {
        return [
            'index' => ListProductions::route('/'),
            'create' => CreateProduction::route('/create'),
            'edit' => EditProduction::route('/{record}/edit'),
        ];
    }
}