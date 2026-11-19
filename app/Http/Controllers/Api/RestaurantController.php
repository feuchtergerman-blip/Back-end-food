<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    /**
     * Listar todos los restaurantes formateados para SuperAdminPanel
     */
    public function index()
    {
        $restaurants = Restaurant::orderBy('created_at', 'desc')->get()->map(function ($rest) {
            return [
                'id'     => $rest->id,
                'name'   => $rest->name,
                'slug'   => $rest->slug,
                'owner'  => $rest->owner_name,
                'plan'   => $rest->plan,
                'views'  => number_format(rand(800, 5500)), // Placeholder hasta conectar analíticas reales
                'date'   => $rest->created_at ? $rest->created_at->translatedFormat('d M Y') : 'Reciente',
                'status' => $rest->is_active ? 'active' : 'suspended',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $restaurants,
        ]);
    }

    /**
     * Dar de alta un nuevo restaurante junto con su usuario dueño (role: owner)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'plan'       => 'nullable|string|max:100',
        ]);

        // Generar slug único automáticamente
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Restaurant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $result = DB::transaction(function () use ($validated, $slug) {
            $restaurant = Restaurant::create([
                'name'       => $validated['name'],
                'slug'       => $slug,
                'owner_name' => $validated['owner_name'],
                'plan'       => $validated['plan'] ?? 'Plan Esencial',
                'is_active'  => true,
            ]);

            $owner = User::create([
                'name'          => $validated['owner_name'],
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'role'          => 'owner',
                'restaurant_id' => $restaurant->id,
            ]);

            return ['restaurant' => $restaurant, 'owner' => $owner];
        });

        $rest = $result['restaurant'];

        return response()->json([
            'success' => true,
            'message' => 'Restaurante y cuenta de administrador creados con éxito.',
            'data'    => [
                'id'     => $rest->id,
                'name'   => $rest->name,
                'slug'   => $rest->slug,
                'owner'  => $rest->owner_name,
                'plan'   => $rest->plan,
                'views'  => '0',
                'date'   => $rest->created_at->translatedFormat('d M Y'),
                'status' => 'active',
            ],
        ], 201);
    }

    /**
     * Activar o suspender un restaurante desde el panel maestro
     */
    public function toggleStatus(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $isActive = $request->input('status') === 'active';
        $restaurant->is_active = $isActive;
        $restaurant->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'status'  => $restaurant->is_active ? 'active' : 'suspended',
        ]);
    }

    /**
     * Obtener los datos del restaurante vinculado al usuario autenticado (Owner)
     */
    public function myRestaurant(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->restaurant_id) {
            return response()->json(['message' => 'No tienes un restaurante asignado.'], 404);
        }

        $restaurant = Restaurant::findOrFail($user->restaurant_id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'        => $restaurant->id,
                'name'      => $restaurant->name,
                'slug'      => $restaurant->slug,
                'plan'      => $restaurant->plan,
                'is_active' => $restaurant->is_active,
            ],
        ]);
    }
}