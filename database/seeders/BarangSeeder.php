<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product; // Pastikan model Product sudah ada di app/Models/Product.php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Nonaktifkan foreign key check untuk proses truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Kosongkan tabel untuk menghindari duplikasi data jika seeder dijalankan lagi
        DB::table('product_prices')->truncate();
        DB::table('products')->truncate();

        // Aktifkan kembali foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Data mentah lengkap yang di-hardcode dari file CSV/Excel Anda.
        $rawData = [
            ['No.' => 1, 'Kode Barang' => '', 'Mesin' => 'HP Kecil', 'Deskripsi Barang' => 'Print Dig HVS A4 BW Cetak Mesin HP', 'Qty' => '-', 'Harga' => 1000],
            ['No.' => 2, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS A4 cetak 1 muka', 'Qty' => '-', 'Harga' => 3000],
            ['No.' => 3, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS A4 cetak 2 muka', 'Qty' => '-', 'Harga' => 4000],
            ['No.' => 4, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 4000],
            ['No.' => 5, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 2500],
            ['No.' => 6, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 2200],
            ['No.' => 7, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 5000],
            ['No.' => 8, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 4000],
            ['No.' => 9, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 3500],
            ['No.' => 10, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 1 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 2500],
            ['No.' => 11, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 1 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 2200],
            ['No.' => 12, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 2 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 3500],
            ['No.' => 13, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 2 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 3000],
            ['No.' => 14, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 190gr cetak 1 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 3000],
            ['No.' => 15, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 190gr cetak 1 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 2500],
            ['No.' => 16, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 190gr cetak 2 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 4000],
            ['No.' => 17, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 190gr cetak 2 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 3500],
            ['No.' => 18, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 3000],
            ['No.' => 19, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 2500],
            ['No.' => 20, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 2200],
            ['No.' => 21, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 4000],
            ['No.' => 22, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 3500],
            ['No.' => 23, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 210/230/250gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 3000],
            ['No.' => 24, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 270gr cetak 1 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 5000],
            ['No.' => 25, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 270gr cetak 1 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 4000],
            ['No.' => 26, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 270gr cetak 2 muka qty 1 s/d 100', 'Qty' => '1-100', 'Harga' => 6000],
            ['No.' => 27, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 270gr cetak 2 muka qty 101 s/d dst', 'Qty' => '101-dst', 'Harga' => 5000],
            ['No.' => 28, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 6000],
            ['No.' => 29, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 5000],
            ['No.' => 30, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 4000],
            ['No.' => 31, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 7000],
            ['No.' => 32, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 6000],
            ['No.' => 33, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 310gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 5000],
            ['No.' => 34, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 6000],
            ['No.' => 35, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 5000],
            ['No.' => 36, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 4000],
            ['No.' => 37, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 7000],
            ['No.' => 38, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 6000],
            ['No.' => 39, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Kertas BW Linen 230gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 5000],
            ['No.' => 40, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Fancy Import 250gr cetak 1 muka qty 1 s/d 10', 'Qty' => '1-10', 'Harga' => 12000],
            ['No.' => 41, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Fancy Import 250gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 10000],
            ['No.' => 42, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Fancy Import 250gr cetak 2 muka qty 1 s/d 10', 'Qty' => '1-10', 'Harga' => 15000],
            ['No.' => 43, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Fancy Import 250gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 12000],
            ['No.' => 44, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Kromo/HVS qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 5000],
            ['No.' => 45, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Kromo/HVS qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 4000],
            ['No.' => 46, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Kromo/HVS qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 3500],
            ['No.' => 47, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Vinyl Putih/Transparan qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 8000],
            ['No.' => 48, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Vinyl Putih/Transparan qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 7000],
            ['No.' => 49, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Vinyl Putih/Transparan qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 6000],
            ['No.' => 50, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Potong', 'Qty' => '-', 'Harga' => 1000],
            ['No.' => 51, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 2500],
            ['No.' => 52, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 1 muka qty 6 s/d 50', 'Qty' => '6-50', 'Harga' => 2000],
            ['No.' => 53, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 1 muka qty 51 s/d 100', 'Qty' => '51-100', 'Harga' => 1500],
            ['No.' => 54, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 1 muka qty 101 dst', 'Qty' => '101-dst', 'Harga' => 1000],
            ['No.' => 55, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 4000],
            ['No.' => 56, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 2 muka qty 6 s/d 50', 'Qty' => '6-50', 'Harga' => 3000],
            ['No.' => 57, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 2 muka qty 51 s/d 100', 'Qty' => '51-100', 'Harga' => 2500],
            ['No.' => 58, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Laminating 2 muka qty 101 dst', 'Qty' => '101-dst', 'Harga' => 2000],
            ['No.' => 59, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Sticker qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 5000],
            ['No.' => 60, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Sticker qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 4000],
            ['No.' => 61, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Sticker qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 3500],
            ['No.' => 62, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Karton qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 6000],
            ['No.' => 63, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Karton qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 5000],
            ['No.' => 64, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Ongkos Cutting Pola Kertas Karton qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 4000],
            ['No.' => 65, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 1 muka (Long Banner)', 'Qty' => '-', 'Harga' => 8000],
            ['No.' => 66, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 2 muka (Long Banner)', 'Qty' => '-', 'Harga' => 10000],
            ['No.' => 67, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 1 muka (Long Banner)', 'Qty' => '-', 'Harga' => 10000],
            ['No.' => 68, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Paper 150gr cetak 2 muka (Long Banner)', 'Qty' => '-', 'Harga' => 12000],
            ['No.' => 69, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 250gr cetak 1 muka (Long Banner)', 'Qty' => '-', 'Harga' => 12000],
            ['No.' => 70, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Art Carton 250gr cetak 2 muka (Long Banner)', 'Qty' => '-', 'Harga' => 15000],
            ['No.' => 71, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Kromo cetak (Long Banner)', 'Qty' => '-', 'Harga' => 15000],
            ['No.' => 72, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Sticker Vinyl cetak (Long Banner)', 'Qty' => '-', 'Harga' => 25000],
            ['No.' => 73, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Kartu Nama Kertas AP250 (isi 500 lembar)', 'Qty' => '-', 'Harga' => 25000],
            ['No.' => 74, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Kartu Nama Kertas AP250 (isi 1000 lembar)', 'Qty' => '-', 'Harga' => 45000],
            ['No.' => 75, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Kartu Nama Kertas Linen (isi 500 lembar)', 'Qty' => '-', 'Harga' => 35000],
            ['No.' => 76, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Kartu Nama Kertas Linen (isi 1000 lembar)', 'Qty' => '-', 'Harga' => 65000],
            ['No.' => 77, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Stiker A3 bahan AP/HVS (cetak 2 muka isi 500 lembar)', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 78, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Paket Stiker A3 bahan AP/HVS (cetak 2 muka isi 1000 lembar)', 'Qty' => '-', 'Harga' => 165000],
            ['No.' => 79, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Sticker Vinyl Glossy per meter', 'Qty' => '-', 'Harga' => 60000],
            ['No.' => 80, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Sticker Vinyl Doff per meter', 'Qty' => '-', 'Harga' => 65000],
            ['No.' => 81, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Sticker Ritrama + laminating per meter', 'Qty' => '-', 'Harga' => 105000],
            ['No.' => 82, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Albatros + laminating per meter', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 83, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Sticker Oneway Vision per meter', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 84, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Luster per meter', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 85, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Photo Paper + laminating per meter', 'Qty' => '-', 'Harga' => 100000],
            ['No.' => 86, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor Canvas per meter', 'Qty' => '-', 'Harga' => 125000],
            ['No.' => 87, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Indoor PVC per meter', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 88, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Flexy Cina 280 gsm per meter', 'Qty' => '-', 'Harga' => 25000],
            ['No.' => 89, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Flexy Cina 280 gsm Hires per meter', 'Qty' => '-', 'Harga' => 30000],
            ['No.' => 90, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Flexy Cina 340 gsm per meter', 'Qty' => '-', 'Harga' => 30000],
            ['No.' => 91, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Flexy Cina 340 gsm Hires per meter', 'Qty' => '-', 'Harga' => 35000],
            ['No.' => 92, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Korcin 440 gsm Glossy/Doff', 'Qty' => '-', 'Harga' => 45000],
            ['No.' => 93, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Korcin 440 gsm Glossy/Doff (Long Hammer)', 'Qty' => '-', 'Harga' => 55000],
            ['No.' => 94, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Sticker Vinyl Glossy/Doff per meter', 'Qty' => '-', 'Harga' => 65000],
            ['No.' => 95, 'Kode Barang' => '', 'Mesin' => '', 'Deskripsi Barang' => 'Print Outdoor Sticker Oneway Vision per meter', 'Qty' => '-', 'Harga' => 85000],
            ['No.' => 96, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 1 muka (Long Banner)', 'Qty' => '-', 'Harga' => 8000],
            ['No.' => 97, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig HVS 80/100gr cetak 2 muka (Long Banner)', 'Qty' => '-', 'Harga' => 10000],
            ['No.' => 98, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 6000],
            ['No.' => 99, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 5000],
            ['No.' => 100, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 3500],
            ['No.' => 101, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 11000],
            ['No.' => 102, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 9000],
            ['No.' => 103, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Ivory 250/270/300gr cetak 2 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 7000],
            ['No.' => 104, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Bahan Unggulan cetak 1 muka', 'Qty' => '-', 'Harga' => 10000],
            ['No.' => 105, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Bahan Unggulan cetak 2 muka', 'Qty' => '-', 'Harga' => 15000],
            ['No.' => 106, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Technova cetak 1 muka', 'Qty' => '-', 'Harga' => 15000],
            ['No.' => 107, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig Technova cetak 2 muka', 'Qty' => '-', 'Harga' => 20000],
            ['No.' => 108, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig White Kraft 150gr cetak 1 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 7000],
            ['No.' => 109, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig White Kraft 150gr cetak 1 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 6000],
            ['No.' => 110, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig White Kraft 150gr cetak 1 muka qty 11 s/d dst', 'Qty' => '11-dst', 'Harga' => 5000],
            ['No.' => 111, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig White Kraft 150gr cetak 2 muka qty 1 s/d 5', 'Qty' => '1-5', 'Harga' => 9000],
            ['No.' => 112, 'Kode Barang' => '', 'Mesin' => 'A3+', 'Deskripsi Barang' => 'Print Dig White Kraft 150gr cetak 2 muka qty 6 s/d 10', 'Qty' => '6-10', 'Harga' => 8000],
        ];

        $groupedProducts = [];

        foreach ($rawData as $row) {
            // Membersihkan deskripsi dari informasi kuantitas untuk mendapatkan nama produk dasar
            $baseName = preg_replace('/(qty|per)\s.*$/i', '', $row['Deskripsi Barang']);
            $baseName = trim($baseName);

            // Menentukan tipe produk berdasarkan kata kunci
            $type = 'digital_print';
            if (str_contains(strtolower($baseName), 'ongkos') || str_contains(strtolower($baseName), 'jasa') || str_contains(strtolower($baseName), 'paket')) {
                $type = 'jasa';
            }

            // Mengambil angka pertama dari kolom Qty sebagai min_quantity
            $min_quantity = 1;
            if (preg_match('/^(\d+)/', $row['Qty'], $matches)) {
                $min_quantity = (int)$matches[1];
            }

            // Mengelompokkan produk berdasarkan nama dasar
            if (!isset($groupedProducts[$baseName])) {
                $groupedProducts[$baseName] = [
                    'product_name' => $baseName,
                    'type' => $type,
                    'prices' => [],
                ];
            }

            // Menambahkan harga ke dalam grup
            $groupedProducts[$baseName]['prices'][] = [
                'min_quantity' => $min_quantity,
                'price' => $row['Harga'],
            ];
        }

        // Memasukkan data yang sudah dikelompokkan ke database
        foreach ($groupedProducts as $data) {
            // Mengurutkan harga dari kuantitas terendah ke tertinggi
            usort($data['prices'], function ($a, $b) {
                return $a['min_quantity'] <=> $b['min_quantity'];
            });
            
            // Mengambil harga terendah sebagai harga default di tabel products
            $defaultPrice = $data['prices'][0]['price'];
            if (count($data['prices']) > 1) {
                $allPrices = array_column($data['prices'], 'price');
                $defaultPrice = min($allPrices);
            }

            $product = Product::create([
                'product_name' => $data['product_name'],
                'price' => $defaultPrice,
                'stock' => 1000, // Set stock to 1000 for all products
                'type' => $data['type'],
                'sku' => Str::slug($data['product_name'], '-') . '-' . Str::random(4), // Membuat SKU unik
                'click' => 0,
            ]);

            // Memasukkan semua varian harga ke tabel product_prices
            foreach ($data['prices'] as $priceData) {
                $product->prices()->create([
                    'min_quantity' => $priceData['min_quantity'],
                    'price' => $priceData['price'],
                ]);
            }
        }
    }
}
