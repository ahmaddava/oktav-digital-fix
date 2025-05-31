<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionCategory;
use App\Models\ProductionItem;

class ProductionSeeder extends Seeder
{
    // public function run()
    // {
    //     // Kategori Cover Dalam
    //     $coverDalam = ProductionCategory::create([
    //         'name' => 'Cover Dalam',
    //         'type' => 'dalam'
    //     ]);

    //     $coverDalamItems = [
    //         ['name' => 'K30', 'price' => 245000],
    //         ['name' => 'K40', 'price' => 245000],
    //         ['name' => 'Embosindo', 'price' => 7000],
    //         ['name' => 'RCP', 'price' => 12000],
    //         ['name' => 'Pentamapan', 'price' => 21000],
    //         ['name' => 'Sanserita', 'price' => 12000],
    //     ];

    //     foreach ($coverDalamItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $coverDalam->id]));
    //     }

    //     // Kategori Cover Luar
    //     $coverLuar = ProductionCategory::create([
    //         'name' => 'Cover Luar',
    //         'type' => 'luar'
    //     ]);

    //     $coverLuarItems = [
    //         ['name' => 'Embosindo','price' => 5000],
    //         ['name' => 'RCP','price' => 10000],
    //         ['name' => 'Pentamapan', 'price' => 15000],
    //         ['name' => 'Sanserita', 'price' => 25000],
    //     ];

    //     foreach ($coverLuarItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $coverLuar->id]));
    //     }

    //     // Kategori Busaf
    //     $busa = ProductionCategory::create([
    //         'name' => 'Busa',
    //         'type' => 'material'
    //     ]);

    //     $busaItems = [
    //         ['name' => 'Busa Eva', 'price' => 110000],
    //         ['name' => 'Busa Kasur', 'price' => 55000],
    //     ];

    //     foreach ($busaItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $busa->id]));
    //     }

    //     // Kategori Ongkos Produksi
    //     $ongkosProduksi = ProductionCategory::create([
    //         'name' => 'Ongkos Produksi',
    //         'type' => 'service'
    //     ]);

    //     $ongkosProduksiItems = [
    //         ['name' => 'Ongkos Produksi', 'size' => 'XS', 'price' => 6000],
    //         ['name' => 'Ongkos Produksi', 'size' => 'Kecil', 'price' => 8500],
    //         ['name' => 'Ongkos Produksi', 'size' => 'Sedang', 'price' => 12000],
    //         ['name' => 'Ongkos Produksi', 'size' => 'Besar', 'price' => 22000],
    //         ['name' => 'Ongkos Produksi', 'size' => 'XXL', 'price' => 32000],
    //     ];

    //     foreach ($ongkosProduksiItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $ongkosProduksi->id]));
    //     }

    //     // Kategori Ongkos Poly
    //     $ongkosPoly = ProductionCategory::create([
    //         'name' => 'Ongkos Poly',
    //         'type' => 'service'
    //     ]);

    //     $ongkosPolyItems = [
    //         ['name' => 'Ongkos Poly', 'dimension' => '10x10', 'price' => 465000],
    //         ['name' => 'Ongkos Poly', 'dimension' => '10x15', 'price' => 523500],
    //         ['name' => 'Ongkos Poly', 'dimension' => '15x15', 'price' => 590000],
    //     ];

    //     foreach ($ongkosPolyItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $ongkosPoly->id]));
    //     }

    //     // Kategori Ongkos Pisau
    //     $ongkosPisau = ProductionCategory::create([
    //         'name' => 'Ongkos Pisau',
    //         'type' => 'service'
    //     ]);

    //     $ongkosPisauItems = [
    //         ['name' => 'Ongkos Pisau', 'size' => 'XS', 'price' => 500000],
    //         ['name' => 'Ongkos Pisau', 'size' => 'Kecil', 'price' => 800000],
    //         ['name' => 'Ongkos Pisau', 'size' => 'Sedang', 'price' => 1000000],
    //         ['name' => 'Ongkos Pisau', 'size' => 'Besar', 'price' => 1200000],
    //         ['name' => 'Ongkos Pisau', 'size' => 'XXL', 'price' => 1500000],
    //     ];

    //     foreach ($ongkosPisauItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $ongkosPisau->id]));
    //     }

    //     // Kategori Profit
    //     $profit = ProductionCategory::create([
    //         'name' => 'Profit',
    //         'type' => 'profit'
    //     ]);

    //     $profitItems = [
    //         ['name' => 'Profit', 'size' => 'XS', 'price' => 8000],
    //         ['name' => 'Profit', 'size' => 'Kecil', 'price' => 10000],
    //         ['name' => 'Profit', 'size' => 'Sedang', 'price' => 14000],
    //         ['name' => 'Profit', 'size' => 'Besar', 'price' => 18000],
    //         ['name' => 'Profit', 'size' => 'XXL', 'price' => 35000],
    //     ];

    //     foreach ($profitItems as $item) {
    //         ProductionItem::create(array_merge($item, ['category_id' => $profit->id]));
    //     }
    // }
}