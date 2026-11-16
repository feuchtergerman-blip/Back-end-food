<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Category;
use App\Models\Dish;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Restaurante de prueba
        $restaurant = Restaurant::create([
            'name' => 'Burgers & Grill House',
            'slug' => 'burgers-grill-house',
            'owner_name' => 'Carlos Mendoza',
            'plan' => 'Plan Pro Multi-Sucursal',
            'is_active' => true,
        ]);

        // 2. Crear Categorías
        $catBurgers = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Burgers', 'slug' => 'burgers', 'order' => 1]);
        $catSides = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Complementos', 'slug' => 'sides', 'order' => 2]);
        $catDrinks = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Bebidas', 'slug' => 'drinks', 'order' => 3]);
        $catDesserts = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Postres', 'slug' => 'desserts', 'order' => 4]);

        // 3. Crear Platillos iniciales (idénticos a tu prototipo)
        Dish::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $catBurgers->id,
            'name' => 'Hamburguesa Clásica Doble',
            'description' => 'Carne angus, doble queso cheddar y aderezo de la casa.',
            'price' => 180.00,
            'available' => true,
            'tags' => ['Más Vendido', 'HD'],
            'video_url' => 'https://videos.pexels.com/video-files/8879534/8879534-uhd_1440_2732_25fps.mp4',
        ]);

        Dish::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $catSides->id,
            'name' => 'Papas Rústicas con Cheddar y Tocino',
            'description' => 'Papas gajo crujientes bañadas en queso cheddar fundido artesanal y trocitos de tocino ahumado.',
            'price' => 160.00,
            'available' => true,
            'tags' => ['Nuevo'],
            'video_url' => 'https://media.istockphoto.com/id/1988144069/video/super-slow-motion-of-flying-tasty-french-fries.mp4?s=mp4-640x640-is&k=20&c=rJAKlIAs26_I1OZiTd93aivYUHe1_hv9pa58aL2schM=',
        ]);

        Dish::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $catDrinks->id,
            'name' => 'Limonada de Moras con Menta',
            'description' => 'Refrescante limonada natural con reducción de frutos rojos, moras silvestres y hojas de menta fresca.',
            'price' => 290.00,
            'available' => false,
            'tags' => ['Nuevo'],
            'video_url' => 'https://videos.pexels.com/video-files/11268029/11268029-hd_1920_1080_60fps.mp4',
        ]);
    }
}