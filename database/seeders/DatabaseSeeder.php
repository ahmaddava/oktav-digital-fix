<?php

namespace Database\Seeders;

use App\Models\User; // Biarkan ini jika Anda memerlukannya untuk Factory, tapi karena dihapus, bisa juga dihapus
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hapus atau komentari baris di bawah ini
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Pastikan ini tetap ada, karena ini memanggil seeder utama Anda
        $this->call(UserRolePermissionSeeder::class);

        // Jika ProductionSeeder juga menggunakan Faker, Anda juga perlu mengomentari/menghapusnya atau memodifikasinya
        // $this->call([
        //     ProductionSeeder::class,
        // ]);
    }
}
