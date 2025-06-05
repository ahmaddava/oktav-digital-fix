<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define ALL permissions for your application.
        // Pastikan nama permission di sini sesuai dengan yang diharapkan oleh Filament Shield
        // (berdasarkan nama resource dan prefix dari getPermissionPrefixes()).
        $allShieldGeneratedPermissions = [
            // CustomerResource
            'view_customer', 'view_any_customer', 'create_customer', 'update_customer', 'delete_customer', 'delete_any_customer', 'export_customer',
            // InvoiceResource (Permissions: view_invoice, create_invoice, etc.)
            'view_invoice', 'view_any_invoice', 'create_invoice', 'update_invoice', 'delete_invoice', 'delete_any_invoice',
            // PaymentResource
            'view_payment', 'view_any_payment', 'create_payment', 'update_payment', 'delete_payment', 'delete_any_payment', 'export_payment',
            // MasterCostResource
            'view_master::cost', 'view_any_master::cost', 'create_master::cost', 'update_master::cost', 'restore_master::cost', 'restore_any_master::cost', 'replicate_master::cost', 'reorder_master::cost', 'delete_master::cost', 'delete_any_master::cost', 'force_delete_master::cost', 'force_delete_any_master::cost', 'export_master::cost', 'send_master::cost',
            // PolyCostResource
            'view_poly::cost', 'view_any_poly::cost', 'create_poly::cost', 'update_poly::cost', 'restore_poly::cost', 'restore_any_poly::cost', 'replicate_poly::cost', 'reorder_poly::cost', 'delete_poly::cost', 'delete_any_poly::cost', 'force_delete_poly::cost', 'force_delete_any_poly::cost', 'export_poly::cost', 'send_poly::cost',
            // PriceCalculationResource (Riwayat Kalkulasi)
            'view_price::calculation', 'view_any_price::calculation', 'create_price::calculation', 'update_price::calculation', 'restore_price::calculation', 'restore_any_price::calculation', 'replicate_price::calculation', 'reorder_price::calculation', 'delete_price::calculation', 'delete_any_price::calculation', 'force_delete_price::calculation', 'force_delete_any_price::calculation', 'export_price::calculation', 'send_price::calculation',
            // ProductResource
            'view_product', 'view_any_product', 'create_product', 'update_product', 'delete_product', 'delete_any_product', 'export_product',
            'update_product_stock',
            // ProductionResource
            'view_production', 'view_any_production', 'create_production', 'update_production', 'restore_production', 'restore_any_production', 'replicate_production', 'reorder_production', 'delete_production', 'delete_any_production', 'force_delete_production', 'force_delete_any_production', 'export_production', 'send_production',
            'complete_production',
            // ProductionCategoryResource
            'view_production::category', 'view_any_production::category', 'create_production::category', 'update_production::category', 'restore_production::category', 'restore_any_production::category', 'replicate_production::category', 'reorder_production::category', 'delete_production::category', 'delete_any_production::category', 'force_delete_production::category', 'force_delete_any_production::category', 'export_production::category', 'send_production::category',
            // ProductionItemResource
            'view_production::item', 'view_any_production::item', 'create_production::item', 'update_production::item', 'restore_production::item', 'restore_any_production::item', 'replicate_production::item', 'reorder_production::item', 'delete_production::item', 'delete_any_production::item', 'force_delete_production::item', 'force_delete_any_production::item', 'export_production::item', 'send_production::item',
            // UserResource
            'view_user', 'view_any_user', 'create_user', 'update_user', 'restore_user', 'restore_any_user', 'replicate_user', 'reorder_user', 'delete_user', 'delete_any_user', 'force_delete_user', 'force_delete_any_user', 'export_user', 'send_user',

            // PAGE PERMISSIONS
            'page_ProductionCalculator',

            // WIDGET PERMISSIONS
            'widget_InvoiceChart', 'widget_LowStockProducts', 'widget_StatsOverview',

            // SHIELD UI PERMISSIONS
            'view_any_shield::role', 'view_shield::role', 'create_shield::role', 'update_shield::role', 'delete_shield::role',
        ];

        // Create permissions if they don't exist
        foreach ($allShieldGeneratedPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 2. Create roles
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleManager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $roleSales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $roleProduksi = Role::firstOrCreate(['name' => 'produksi', 'guard_name' => 'web']);
        $roleManagement = Role::firstOrCreate(['name' => 'management', 'guard_name' => 'web']); // Role baru

        // 3. Assign permissions to roles

        // Admin gets ALL permissions DEFINED in $allShieldGeneratedPermissions
        $roleAdmin->syncPermissions(Permission::all());

        // Management permissions
        $managementPermissionsList = [
            // Customer: CRUD
            'view_customer', 'view_any_customer', 'create_customer', 'update_customer', 'delete_customer', 'delete_any_customer', 'export_customer',
            // Product: CRUD
            'view_product', 'view_any_product', 'create_product', 'update_product', 'delete_product', 'delete_any_product', 'export_product', 'update_product_stock',
            // Invoice: CRUD
            'view_invoice', 'view_any_invoice', 'create_invoice', 'update_invoice', 'delete_invoice', 'delete_any_invoice',
            // Payment: CRUD
            'view_payment', 'view_any_payment', 'create_payment', 'update_payment', 'delete_payment', 'delete_any_payment', 'export_payment',
            // MasterCost (Biaya Produksi): CRUD
            'view_master::cost', 'view_any_master::cost', 'create_master::cost', 'update_master::cost', 'restore_master::cost', 'restore_any_master::cost', 'replicate_master::cost', 'reorder_master::cost', 'delete_master::cost', 'delete_any_master::cost', 'force_delete_master::cost', 'force_delete_any_master::cost', 'export_master::cost', 'send_master::cost',
            // PolyCost (Biaya Poly): CRUD
            'view_poly::cost', 'view_any_poly::cost', 'create_poly::cost', 'update_poly::cost', 'restore_poly::cost', 'restore_any_poly::cost', 'replicate_poly::cost', 'reorder_poly::cost', 'delete_poly::cost', 'delete_any_poly::cost', 'force_delete_poly::cost', 'force_delete_any_poly::cost', 'export_poly::cost', 'send_poly::cost',
            // PriceCalculation (Riwayat Kalkulasi): CRUD
            'view_price::calculation', 'view_any_price::calculation', 'create_price::calculation', 'update_price::calculation', 'restore_price::calculation', 'restore_any_price::calculation', 'replicate_price::calculation', 'reorder_price::calculation', 'delete_price::calculation', 'delete_any_price::calculation', 'force_delete_price::calculation', 'force_delete_any_price::calculation', 'export_price::calculation', 'send_price::calculation',
            // ProductionCategory (Kategori Produksi): CRUD
            'view_production::category', 'view_any_production::category', 'create_production::category', 'update_production::category', 'restore_production::category', 'restore_any_production::category', 'replicate_production::category', 'reorder_production::category', 'delete_production::category', 'delete_any_production::category', 'force_delete_production::category', 'force_delete_any_production::category', 'export_production::category', 'send_production::category',
            // ProductionItem (Item Produksi): CRUD
            'view_production::item', 'view_any_production::item', 'create_production::item', 'update_production::item', 'restore_production::item', 'restore_any_production::item', 'replicate_production::item', 'reorder_production::item', 'delete_production::item', 'delete_any_production::item', 'force_delete_production::item', 'force_delete_any_production::item', 'export_production::item', 'send_production::item',
            // Production: View Only
            'view_production', 'view_any_production',
            // Page: Kalkulator Harga (TIDAK ADA untuk Management)
            // 'page_ProductionCalculator',
        ];
        $roleManagement->syncPermissions(array_intersect($managementPermissionsList, $allShieldGeneratedPermissions));


        // Manager permissions: CRUD for all resources except UserResource. Can access Production Calculator.
        $managerPermissionsList = [];
        foreach ($allShieldGeneratedPermissions as $permissionName) {
            // Berikan semua permission KECUALI yang berkaitan dengan UserResource dan Shield UI Roles
            if (
                !str_contains($permissionName, '_user') &&
                !str_contains($permissionName, 'shield::role')
            ) {
                $managerPermissionsList[] = $permissionName;
            }
        }
        // Pastikan manager bisa akses kalkulator harga (jika belum termasuk)
        if (in_array('page_ProductionCalculator', $allShieldGeneratedPermissions) && !in_array('page_ProductionCalculator', $managerPermissionsList)) {
            $managerPermissionsList[] = 'page_ProductionCalculator';
        }
        $roleManager->syncPermissions(array_intersect($managerPermissionsList, $allShieldGeneratedPermissions));


        // Sales permissions
        $salesPermissionsList = [
            // Customer: CRUD
            'view_customer', 'view_any_customer', 'create_customer', 'update_customer', 'delete_customer', 'delete_any_customer', 'export_customer',
            // Product: CRUD
            'view_product', 'view_any_product', 'create_product', 'update_product', 'delete_product', 'delete_any_product', 'export_product', 'update_product_stock',
            // Invoice: CRUD
            'view_invoice', 'view_any_invoice', 'create_invoice', 'update_invoice', 'delete_invoice', 'delete_any_invoice',
            // Payment: CRUD
            'view_payment', 'view_any_payment', 'create_payment', 'update_payment', 'delete_payment', 'delete_any_payment', 'export_payment',
            // MasterCost (Biaya Produksi): CRUD
            'view_master::cost', 'view_any_master::cost', 'create_master::cost', 'update_master::cost', 'restore_master::cost', 'restore_any_master::cost', 'replicate_master::cost', 'reorder_master::cost', 'delete_master::cost', 'delete_any_master::cost', 'force_delete_master::cost', 'force_delete_any_master::cost', 'export_master::cost', 'send_master::cost',
            // PolyCost (Biaya Poly): CRUD
            'view_poly::cost', 'view_any_poly::cost', 'create_poly::cost', 'update_poly::cost', 'restore_poly::cost', 'restore_any_poly::cost', 'replicate_poly::cost', 'reorder_poly::cost', 'delete_poly::cost', 'delete_any_poly::cost', 'force_delete_poly::cost', 'force_delete_any_poly::cost', 'export_poly::cost', 'send_poly::cost',
            // PriceCalculation (Riwayat Kalkulasi): CRUD
            'view_price::calculation', 'view_any_price::calculation', 'create_price::calculation', 'update_price::calculation', 'restore_price::calculation', 'restore_any_price::calculation', 'replicate_price::calculation', 'reorder_price::calculation', 'delete_price::calculation', 'delete_any_price::calculation', 'force_delete_price::calculation', 'force_delete_any_price::calculation', 'export_price::calculation', 'send_price::calculation',
            // ProductionCategory (Kategori Produksi): CRUD
            'view_production::category', 'view_any_production::category', 'create_production::category', 'update_production::category', 'restore_production::category', 'restore_any_production::category', 'replicate_production::category', 'reorder_production::category', 'delete_production::category', 'delete_any_production::category', 'force_delete_production::category', 'force_delete_any_production::category', 'export_production::category', 'send_production::category',
            // ProductionItem (Item Produksi): CRUD
            'view_production::item', 'view_any_production::item', 'create_production::item', 'update_production::item', 'restore_production::item', 'restore_any_production::item', 'replicate_production::item', 'reorder_production::item', 'delete_production::item', 'delete_any_production::item', 'force_delete_production::item', 'force_delete_any_production::item', 'export_production::item', 'send_production::item',
            // Production: View Only
            'view_production', 'view_any_production',
            // Page: Kalkulator Harga (TIDAK ADA untuk Management)
            'page_ProductionCalculator',
        ];
        $roleSales->syncPermissions(array_intersect($salesPermissionsList, $allShieldGeneratedPermissions));

        // Produksi permissions
        $produksiPermissionsList = [
            'view_any_product', 'view_product', 'update_product_stock',
            'view_any_production', 'view_production', 'create_production', 'update_production', 'complete_production',
            'view_any_production::item', 'view_production::item',
            'view_any_production::category', 'view_production::category',
            'view_any_poly::cost', 'view_poly::cost',
            'view_any_master::cost', 'view_master::cost',
            'widget_LowStockProducts',
            // Produksi TIDAK bisa akses 'page_ProductionCalculator'
        ];
        $roleProduksi->syncPermissions(array_intersect($produksiPermissionsList, $allShieldGeneratedPermissions));


        // 4. Create users and assign roles
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('admin123')]
        )->assignRole($roleAdmin);

        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Manager User', 'password' => Hash::make('manager123')]
        )->assignRole($roleManager);

        User::firstOrCreate(
            ['email' => 'sales@example.com'],
            ['name' => 'Sales User', 'password' => Hash::make('sales123')]
        )->assignRole($roleSales);

        User::firstOrCreate(
            ['email' => 'produksi@example.com'],
            ['name' => 'Produksi User', 'password' => Hash::make('produksi123')]
        )->assignRole($roleProduksi);

        User::firstOrCreate( // User baru untuk role management
            ['email' => 'management@example.com'],
            ['name' => 'Management User', 'password' => Hash::make('management123')]
        )->assignRole($roleManagement);
    }
}
