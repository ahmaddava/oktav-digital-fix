<?php

namespace App\Filament\Pages;

use App\Models\ProductionCategory;
use App\Models\ProductionItem;
use App\Models\PriceCalculation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Models\MasterCost;
use App\Models\PolyCost;

class ProductionCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Kalkulator Harga';
    protected static string $view = 'filament.pages.production-calculator';

    public ?array $data = [];
    public $selectedSize = 'Sedang';
    public $productName = '';
    public $coverDalamItems = [];
    public $coverLuarItems = [];
    public $materialItems = [];
    public $calculationResult = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->loadItems();
    }

    protected function loadItems(): void
    {
        $this->coverDalamItems = ProductionItem::whereHas('category', function($q) {
            $q->where('name', 'Cover Dalam');
        })->where('is_active', true)->get()->collect();

        $this->coverLuarItems = ProductionItem::whereHas('category', function($q) {
            $q->where('name', 'Cover Luar');
        })->where('is_active', true)->get()->collect();

        $this->materialItems = ProductionItem::whereHas('category', function($q) {
            $q->where('name', 'Busa');
        })->where('is_active', true)->get();
    }

    public function getCategoryItems()
    {
        return ProductionCategory::with(['items' => function ($query) {
            $query->where('is_active', true);
        }])->where('is_active', true)->get();
    }

    public function form(Form $form): Form
{
    // Section statis (informasi produk)
    $sections = [
        Forms\Components\Section::make('Informasi Produk')
        ->schema([
            Forms\Components\TextInput::make('product_name')
                ->label('Nama Produk')
                ->required()
                ->live()
                ->columnSpan(1),
            
            Forms\Components\Select::make('size')
                ->label('Ukuran')
                ->options(MasterCost::pluck('size', 'size')->toArray())
                ->required()
                ->live(),
                
            Forms\Components\TextInput::make('quantity')
                ->label('Jumlah')
                ->numeric()
                ->default(1)
                ->required(),
                
            Forms\Components\Select::make('poly_dimension')
                ->label('Dimensi Poly')
                ->options(fn () => \App\Models\PolyCost::all()->pluck('dimension', 'dimension'))
                ->nullable()
                ->live()
                ->columnSpan(1),
            
                
            Forms\Components\Toggle::make('include_knife_cost')
                ->label('Termasuk Ongkos Pisau')
                ->inline(false)
                ->columnSpan(1),
        ])
        ->columns(4) // 4 kolom total   
    ];

    // Section dinamis per kategori dari database
    $categories = $this->getCategoryItems();
    
    foreach ($categories as $category) {
        // Jika kategori tidak punya item, skip
        if ($category->items->isEmpty()) continue;

        $sections[] = Forms\Components\Section::make($category->name)
            ->schema([
                Forms\Components\CheckboxList::make('category_' . $category->id)
                    ->label('Pilih ' . $category->name)
                    ->options(
                        $category->items->pluck('name', 'id')->toArray()
                    )
                    ->descriptions(
                        $category->items->pluck('price', 'id')->map(
                            fn($price) => 'Rp ' . number_format($price, 0, ',', '.')
                        )->toArray()
                    )
                    ->columns(2)
            ]);
    }

    return $form
        ->schema($sections)
        ->statePath('data')
        ->live();
}

public function calculate(): void
{
    $data = Arr::wrap($this->form->getState());
    
    if (empty($data['product_name'])) {
        Notification::make()
            ->title('Error')
            ->body('Nama produk harus diisi')
            ->danger()
            ->send();
        return;
    }

    $quantity = (int)($data['quantity'] ?? 1);
    $totalMaterialCost = 0;
    $selectedItems = [];
    $categories = $this->getCategoryItems();

    // 1. Hitung biaya material dari semua kategori yang dipilih
    foreach ($categories as $category) {
        $fieldName = 'category_' . $category->id;
        
        // Periksa apakah ada item yang dipilih untuk kategori ini
        if (isset($data[$fieldName]) && !empty($data[$fieldName])) {
            $itemIds = $data[$fieldName];
            $items = ProductionItem::whereIn('id', $itemIds)->get();
            
            foreach ($items as $item) {
                $price = $item->price; // Gunakan price langsung
                $totalMaterialCost += $price;
                $selectedItems[] = [
                    'category' => $category->name,
                    'name' => $item->name,
                    'price' => $price
                ];
            }
        }
    }

    // 2. Ambil master cost berdasarkan ukuran
    $masterCost = MasterCost::where('size', $data['size'])->first();

    if (!$masterCost) {
        Notification::make()
            ->title('Error')
            ->body('Biaya untuk ukuran ini belum diatur')
            ->danger()
            ->send();
        return;
    }

    // 3. Hitung biaya tambahan
    $productionCost = $masterCost->production_cost;
    $knifeCost = !empty($data['include_knife_cost']) ? $masterCost->knife_cost : 0;
    $profit = $masterCost->profit;

    // 4. Hitung ongkos poly (jika ada) - PERUBAHAN DI SINI
    $polyCost = 0;
    $polyDimension = null;
    if (!empty($data['poly_dimension'])) {
        $polyCostItem = PolyCost::where('dimension', $data['poly_dimension'])->first();
        
        if ($polyCostItem) {
            $polyCost = $polyCostItem->cost;
            $polyDimension = $data['poly_dimension'];
        }
    }

    // 5. Hitung total harga per item
    $totalPricePerItem = $totalMaterialCost + $productionCost + $polyCost + $knifeCost + $profit;
    
    // 6. Hitung total harga untuk semua quantity
    $totalPrice = $totalPricePerItem * $quantity;

    // 7. Simpan hasil kalkulasi - TAMBAHKAN poly_dimension
    $calculation = PriceCalculation::create([
        'product_name' => $data['product_name'],
        'size' => $data['size'],
        'quantity' => $quantity,
        'selected_items' => json_encode($selectedItems),
        'total_material_cost' => $totalMaterialCost,
        'production_cost' => $productionCost,
        'poly_dimension' => $polyDimension, // Simpan dimensi poly
        'poly_cost' => $polyCost,
        'knife_cost' => $knifeCost,
        'profit' => $profit,
        'total_price_per_item' => $totalPricePerItem,
        'total_price' => $totalPrice,
        'notes' => $data['notes'] ?? null
    ]);

    // 8. Siapkan hasil untuk ditampilkan - TAMBAHKAN poly_dimension
    $this->calculationResult = [
        'product_name' => $data['product_name'],
        'size' => $data['size'],
        'quantity' => $quantity,
        'poly_dimension' => $polyDimension, // Untuk ditampilkan di view
        'selected_items' => $selectedItems,
        'total_material_cost' => $totalMaterialCost,
        'production_cost' => $productionCost,
        'poly_cost' => $polyCost,
        'knife_cost' => $knifeCost,
        'profit' => $profit,
        'total_price_per_item' => $totalPricePerItem,
        'total_price' => $totalPrice,
        'notes' => $data['notes'] ?? null
    ];

    Notification::make()
        ->title('Berhasil')
        ->body('Kalkulasi harga berhasil dihitung')
        ->success()
        ->send();
}

    public function resetCalculation(): void
    {
        $this->calculationResult = null;
        $this->form->fill();
        
        Notification::make()
            ->title('Reset')
            ->body('Form kalkulasi telah direset')
            ->info()
            ->send();
    }
}