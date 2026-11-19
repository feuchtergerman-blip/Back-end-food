<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class MenuController extends Controller
{
    /**
     * Obtener la información del restaurante y su menú (acepta slug o id)
     */
    public function showMenu($identifier)
    {
        $query = Restaurant::with(['categories.dishes', 'dishes.category']);

        if (is_numeric($identifier)) {
            $restaurant = $query->where('id', $identifier)->first();
        } else {
            $restaurant = $query->where('slug', $identifier)
                ->where('is_active', true)
                ->first();
        }

        if (!$restaurant) {
            return response()->json([
                'error' => 'Restaurante no encontrado o inactivo'
            ], 404);
        }

        // Formatear platillos incluyendo el slug/nombre de categoría para filtros del comensal
        $dishes = $restaurant->dishes->map(function ($dish) {
            return [
                'id'          => $dish->id,
                'name'        => $dish->name,
                'description' => $dish->description,
                'price'       => $dish->price,
                'available'   => (bool) $dish->available,
                'video_url'   => $dish->video_url,
                'tags'        => $dish->tags ?? [],
                'category_id' => $dish->category_id,
                'category'    => $dish->category ? $dish->category->slug ?? strtolower($dish->category->name) : 'all',
            ];
        });

        return response()->json([
            'success'    => true,
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories,
            'dishes'     => $dishes,
        ]);
    }

    /**
     * Registrar un nuevo platillo asegurando una categoría válida para este restaurante
     */
    public function storeDish(Request $request, $restaurantId)
    {
        $validated = $request->validate([
            'category_id' => 'nullable',
            'category_name' => 'nullable|string|max:100',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'tags'        => 'nullable|array',
            'video_url'   => 'nullable|string',
        ]);

        // Mapa de categorías base por índice (1..4) del selector del frontend
        $defaultCategories = [
            1 => ['name' => 'Burgers', 'slug' => 'burgers'],
            2 => ['name' => 'Complementos', 'slug' => 'sides'],
            3 => ['name' => 'Bebidas', 'slug' => 'drinks'],
            4 => ['name' => 'Postres', 'slug' => 'desserts'],
        ];

        $catIndex = (int) ($request->input('category_id', 1));
        $catMeta = $defaultCategories[$catIndex] ?? ['name' => 'Principales', 'slug' => 'burgers'];

        // Buscar o crear la categoría aislada para este restaurant_id
        $category = Category::firstOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'name'          => $catMeta['name'],
            ],
            [
                'slug' => $catMeta['slug'] . '-' . $restaurantId,
            ]
        );

        $dish = Dish::create([
            'restaurant_id' => $restaurantId,
            'category_id'   => $category->id,
            'name'          => $validated['name'],
            'description'   => $validated['description'] ?? null,
            'price'         => $validated['price'],
            'available'     => true,
            'tags'          => $validated['tags'] ?? [],
            'video_url'     => $validated['video_url'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Platillo creado exitosamente',
            'dish'    => $dish,
        ], 201);
    }

    /**
     * Actualizar el estado de disponibilidad (stock) de un platillo en tiempo real
     */
    public function toggleAvailability(Request $request, $restaurantId, $dishId = null)
    {
        // Soporta llamada con 2 parámetros (/restaurants/{id}/dishes/{dishId}) o 1 parámetro (/dishes/{id}/toggle-availability)
        $targetDishId = $dishId ?? $restaurantId;
        $dish = Dish::find($targetDishId);

        if (!$dish) {
            return response()->json(['error' => 'Platillo no encontrado'], 404);
        }

        if ($request->has('available')) {
            $dish->available = (bool) $request->input('available');
        } else {
            $dish->available = !$dish->available;
        }

        $dish->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado de disponibilidad actualizado',
            'dish'    => $dish,
        ]);
    }

    /**
     * Eliminar un platillo del menú del restaurante
     */
    public function destroyDish($restaurantId, $dishId)
    {
        $dish = Dish::where('restaurant_id', $restaurantId)->where('id', $dishId)->first();

        if (!$dish) {
            return response()->json(['error' => 'Platillo no encontrado'], 404);
        }

        $dish->delete();

        return response()->json([
            'success' => true,
            'message' => 'Platillo eliminado correctamente',
        ]);
    }

    /**
     * Subir y procesar/optimizar video del platillo usando FFmpeg
     */
    public function uploadAndProcessVideo(Request $request, $restaurantId)
    {
        $request->validate([
            'video'   => 'required|mimes:mp4,mov,avi,webm|max:102400', // Máx 100MB
            'dish_id' => 'required|exists:dishes,id',
        ]);

        $dish = Dish::findOrFail($request->dish_id);

        $file = $request->file('video');
        $extension = $file->getClientOriginalExtension() ?: 'mp4';
        $filename = 'dish_' . $dish->id . '_' . time() . '.' . $extension;
        $originalPath = $file->storeAs('temp', $filename, 'public');

        $fullOriginalPath = storage_path('app/public/' . $originalPath);
        $processedFilename = 'optimized_dish_' . $dish->id . '_' . time() . '.mp4';
        $outputRelativePath = 'videos/' . $processedFilename;
        $fullOutputPath = storage_path('app/public/' . $outputRelativePath);

        if (!file_exists(storage_path('app/public/videos'))) {
            mkdir(storage_path('app/public/videos'), 0755, true);
        }

        // Verificar si ffmpeg está instalado en el sistema (Mac local o servidor Ubuntu)
        $ffmpegCheck = new Process(['which', 'ffmpeg']);
        $ffmpegCheck->run();
        $hasFfmpeg = $ffmpegCheck->isSuccessful() && trim($ffmpegCheck->getOutput()) !== '';

        if ($hasFfmpeg) {
            $ffmpegCommand = [
                'ffmpeg',
                '-y',
                '-i', $fullOriginalPath,
                '-vf', 'scale=-2:720',
                '-c:v', 'libx264',
                '-crf', '28',
                '-preset', 'fast',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-movflags', '+faststart',
                $fullOutputPath,
            ];

            $process = new Process($ffmpegCommand);
            $process->setTimeout(300);
            $process->run();

            if (file_exists($fullOriginalPath)) {
                unlink($fullOriginalPath);
            }

            if (!$process->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Error al procesar el video con FFmpeg: ' . $process->getErrorOutput(),
                ], 500);
            }
        } else {
            // Respaldo para Mac local si no hay ffmpeg instalado: mover directo a public/videos
            rename($fullOriginalPath, $fullOutputPath);
        }

        $dish->video_url = asset('storage/' . $outputRelativePath);
        $dish->save();

        return response()->json([
            'success'   => true,
            'message'   => $hasFfmpeg ? 'Video comprimido con FFmpeg' : 'Video guardado (sin compresión local)',
            'video_url' => $dish->video_url,
            'dish'      => $dish,
        ]);
    }
}