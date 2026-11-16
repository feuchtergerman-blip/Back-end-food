<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Dish;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Asegurar que exista un restaurante de prueba con ID 1
        $restaurant = Restaurant::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Restaurante de Prueba',
                'slug' => 'restaurante-de-prueba',
                'owner_name' => 'Restaurantero Demo',
                'is_active' => true,
            ]
        );

        // 2. Crear categoría de prueba para el restaurante
        $category = Category::firstOrCreate(
            ['id' => 1],
            [
                'restaurant_id' => $restaurant->id,
                'name' => 'Entradas',
                'slug' => 'entradas', // Campo requerido por la tabla
            ]
        );

        // 3. Crear platillo de prueba con ID 1 para la subida de videos
        Dish::updateOrCreate(
            ['id' => 1], // Condición de búsqueda
            [
                'restaurant_id' => $restaurant->id,
                'category_id' => $category->id,
                'name' => 'Tacos de Prueba',
                'description' => 'Platillo de prueba para menús digitales',
                'price' => 100.00,
                'available' => true,
            ]
        );

        // Dueño de restaurante (vinculado al restaurante ID 1)
        User::updateOrCreate(
            ['email' => 'owner@foodies360.com'],
            [
                'name' => 'Restaurantero Demo',
                'password' => Hash::make('password123'),
                'restaurant_id' => 1,
                'role' => 'owner',
            ]
        );

        // Superadministrador del sistema
        User::updateOrCreate(
            ['email' => 'admin@foodies360.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'restaurant_id' => null,
                'role' => 'superadmin',
            ]
        );
    }
}