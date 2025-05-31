<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Filament\Notifications\Notification;

class MasterCost extends Model
{
    protected $fillable = ['size', 'production_cost', 'knife_cost', 'profit'];
    protected $casts = [
        'production_cost' => 'float',
        'knife_cost' => 'float',
        'profit' => 'float',
    ];

    public static function getBySize($size)
    {
        return self::where('size', $size)->first();
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
    
        // Hitung material
        $totalMaterialCost = 0;
        $selectedItems = [];
        foreach (['cover_dalam' => 'Cover Dalam', 'cover_luar' => 'Cover Luar', 'material' => 'Material'] as $field => $categoryLabel) {
            if (!empty($data[$field])) {
                $items = ProductionItem::whereIn('id', $data[$field])->get();
                foreach ($items as $item) {
                    $price = $item->effective_price;
                    $totalMaterialCost += $price;
                    $selectedItems[] = [
                        'category' => $categoryLabel,
                        'name' => $item->display_name,
                        'price' => $price,
                    ];
                }
            }
        }
        $totalMaterialCost = $totalMaterialCost * $quantity;
    
        // Ambil master cost
        $masterCost = MasterCost::where('size', $data['size'])->first();
    
        $productionCost = ($masterCost->production_cost ?? 0) * $quantity;
        $knifeCost = !empty($data['include_knife_cost']) ? ($masterCost->knife_cost ?? 0) : 0;
        $profit = ($masterCost->profit ?? 0) * $quantity;
    
        // Poly cost (input manual atau dari item, sesuai kebutuhan)
        $polyCost = 0;
        if (!empty($data['poly_cost'])) {
            $polyCost = ((float)$data['poly_cost']) * $quantity;
        }
    
        $totalPrice = $totalMaterialCost + $productionCost + $polyCost + $knifeCost + $profit;
    
        // Simpan ke DB
        $calculation = PriceCalculation::create([
            'product_name' => $data['product_name'],
            'size' => $data['size'],
            'selected_items' => json_encode($selectedItems),
            'total_material_cost' => $totalMaterialCost,
            'production_cost' => $productionCost,
            'poly_cost' => $polyCost,
            'knife_cost' => $knifeCost,
            'profit' => $profit,
            'total_price' => $totalPrice,
            'notes' => $data['notes'] ?? null
        ]);
    
        $this->calculationResult = [
            'product_name' => $data['product_name'],
            'size' => $data['size'],
            'selected_items' => $selectedItems,
            'total_material_cost' => $totalMaterialCost,
            'production_cost' => $productionCost,
            'poly_cost' => $polyCost,
            'knife_cost' => $knifeCost,
            'profit' => $profit,
            'total_price' => $totalPrice,
            'notes' => $data['notes'] ?? null
        ];
    
        Notification::make()
            ->title('Berhasil')
            ->body('Kalkulasi harga berhasil dihitung')
            ->success()
            ->send();
    }
    
    
}

