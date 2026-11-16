<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class MenuController extends Controller
{
    /**
     * Obtener la información del restaurante y su menú completo ordenado por categorías.
     */
    public function showMenu($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories.dishes' => function($query) {
                // Opcional: ordenar platillos si lo requieres
            }])
            ->first();

        if (!$restaurant) {
            return response()->json([
                'error' => 'Restaurante no encontrado o inactivo'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories,
            'dishes' => $restaurant->dishes
        ]);
    }

    /**
     * Actualizar el estado de disponibilidad (stock) de un platillo en tiempo real.
     */
    public function toggleAvailability(Request $request, $dishId)
    {
        $dish = Dish::find($dishId);

        if (!$dish) {
            return response()->json([
                'error' => 'Platillo no encontrado'
            ], 404);
        }

        $dish->available = !$dish->available;
        $dish->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado de disponibilidad actualizado',
            'dish' => $dish
        ]);
    }

    /**
     * Registrar un nuevo platillo para el restaurante.
     */
    public function storeDish(Request $request, $restaurantId)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'tags' => 'nullable|array',
            'video_url' => 'nullable|string'
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['available'] = true;

        $dish = Dish::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Platillo creado exitosamente',
            'dish' => $dish
        ], 201);
    }

    /**
     * Subir y procesar/optimizar video del platillo usando FFmpeg.
     */
    public function uploadAndProcessVideo(Request $request, $restaurantId)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,mov,avi,webm|max:51200', // Máx 50MB
            'dish_id' => 'required|exists:dishes,id'
        ]);

        $dish = Dish::findOrFail($request->dish_id);
        
        // 1. Guardar el archivo original temporalmente en storage/app/public/temp
        $file = $request->file('video');
        $filename = 'dish_' . $dish->id . '_' . time() . '.mp4';
        $originalPath = $file->storeAs('temp', $filename, 'public');
        
        $fullOriginalPath = storage_path('app/public/' . $originalPath);
        $processedFilename = 'optimized_' . $filename;
        $outputRelativePath = 'videos/' . $processedFilename;
        $fullOutputPath = storage_path('app/public/' . $outputRelativePath);

        // Asegurar que el directorio de salida exista
        if (!file_exists(storage_path('app/public/videos'))) {
            mkdir(storage_path('app/public/videos'), 0755, true);
        }

        // 2. Ejecutar comando FFmpeg para comprimir a H.264 / AAC y reescalar a 720p optimizado para web (móvil)
        $ffmpegCommand = [
            'ffmpeg',
            '-i', $fullOriginalPath,
            '-vf', 'scale=-2:720',
            '-c:v', 'libx264',
            '-crf', '28',
            '-preset', 'fast',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            $fullOutputPath
        ];

        $process = new Process($ffmpegCommand);
        $process->setTimeout(300); // 5 minutos máx para videos pesados
        $process->run();

        // Limpiar archivo temporal original
        if (file_exists($fullOriginalPath)) {
            unlink($fullOriginalPath);
        }

        if (!$process->isSuccessful()) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el video con FFmpeg: ' . $process->getErrorOutput()
            ], 500);
        }

        // 3. Guardar la ruta pública en el platillo
        $dish->video_url = asset('storage/' . $outputRelativePath);
        $dish->save();

        return response()->json([
            'success' => true,
            'message' => 'Video comprimido y optimizado con éxito',
            'video_url' => $dish->video_url,
            'dish' => $dish
        ]);
    }
}