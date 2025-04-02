<?php
// database/seeders/UserRolePermissionSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buat role
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleSales = Role::create(['name' => 'sales']);
        $roleProduksi = Role::create(['name' => 'produksi']);

        // Buat permission
        Permission::create(['name' => 'customer.view']);
        Permission::create(['name' => 'customer.create']);
        Permission::create(['name' => 'customer.edit']);
        Permission::create(['name' => 'customer.delete']);

        Permission::create(['name' => 'product.view']);
        Permission::create(['name' => 'product.create']);
        Permission::create(['name' => 'product.edit']);
        Permission::create(['name' => 'product.delete']);
        Permission::create(['name' => 'product.update_stock']);

        Permission::create(['name' => 'invoice.view']);
        Permission::create(['name' => 'invoice.create']);
        Permission::create(['name' => 'invoice.edit']);
        Permission::create(['name' => 'invoice.delete']);

        Permission::create(['name' => 'production.view']);
        Permission::create(['name' => 'production.create']);
        Permission::create(['name' => 'production.edit']);
        Permission::create(['name' => 'production.complete']);

        // Assign permissions ke role admin
        $roleAdmin->givePermissionTo([
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete',
            'product.view', 'product.create', 'product.edit', 'product.delete', 'product.update_stock',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'production.view', 'production.create', 'production.edit', 'production.complete'
        ]);

        // Assign permissions ke role sales
        $roleSales->givePermissionTo([
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete',
            'product.view', 'product.create', 'product.edit', 'product.delete', 'product.update_stock',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
        ]);

        // Assign permissions ke role produksi
        $roleProduksi->givePermissionTo([
            'customer.view',
            'product.view',
            'invoice.view',
            'production.view', 'production.create'
        ]);

        // Buat User Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);
        $admin->assignRole('admin');

        // Buat User Sales
        $sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'password' => bcrypt('sales123'),
        ]);
        $sales->assignRole('sales');

        // Buat User Produksi
        $produksi = User::create([
            'name' => 'Produksi User',
            'email' => 'produksi@example.com',
            'password' => bcrypt('produksi123'),
        ]);
        $produksi->assignRole('produksi');
    }
}
