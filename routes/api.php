<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Ruta pública de inicio de sesión
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por autenticación Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Aquí podemos mover después las rutas de administración de platillos que requieren dueño o superadmin
});

// Endpoint público para que el comensal vea el menú al escanear el QR
Route::get('/restaurants/{slug}/menu', [MenuController::class, 'showMenu']);

// Endpoints de administración del restaurante
Route::patch('/dishes/{id}/toggle-availability', [MenuController::class, 'toggleAvailability']);
Route::post('/restaurants/{restaurantId}/dishes', [MenuController::class, 'storeDish']);
Route::post('/restaurants/{restaurantId}/videos', [MenuController::class, 'uploadAndProcessVideo']);