<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (empty($password)) {
            throw new \RuntimeException('ADMIN_PASSWORD tidak boleh kosong');
        }

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tokoyafi.test')],
            [
                'name' => env('ADMIN_NAME', 'Admin Toko Yafi'),
                'password' => Hash::make($password),
            ]
        );

        if (! $admin->wasRecentlyCreated) {
            return;
        }

        $minuman = Category::create(['name' => 'Minuman']);
        $makanan = Category::create(['name' => 'Makanan']);
        $snack = Category::create(['name' => 'Snack']);

        Product::insert([
            ['category_id' => $minuman->id, 'sku' => 'MNM-001', 'name' => 'Teh Botol 450ml', 'price' => 5000, 'stock' => 48, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $minuman->id, 'sku' => 'MNM-002', 'name' => 'Air Mineral 600ml', 'price' => 3000, 'stock' => 120, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $minuman->id, 'sku' => 'MNM-003', 'name' => 'Jus Jeruk 250ml', 'price' => 6000, 'stock' => 24, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $makanan->id, 'sku' => 'MKN-001', 'name' => 'Nasi Goreng Instan', 'price' => 4500, 'stock' => 36, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $makanan->id, 'sku' => 'MKN-002', 'name' => 'Mie Goreng Spesial', 'price' => 3500, 'stock' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $makanan->id, 'sku' => 'MKN-003', 'name' => 'Bubur Instan Ayam', 'price' => 5500, 'stock' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $snack->id, 'sku' => 'SNK-001', 'name' => 'Keripik Singkong Pedas', 'price' => 8000, 'stock' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $snack->id, 'sku' => 'SNK-002', 'name' => 'Biskuit Cokelat', 'price' => 12000, 'stock' => 18, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
