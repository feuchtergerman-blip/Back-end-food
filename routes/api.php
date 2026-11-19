<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RestaurantController;

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
    Route::get('/my-restaurant', [RestaurantController::class, 'myRestaurant']);

    // Endpoints de Superadministrador (Multi-Tenant)
    Route::prefix('superadmin')->group(function () {
        Route::get('/restaurants', [RestaurantController::class, 'index']);
        Route::post('/restaurants', [RestaurantController::class, 'store']);
        Route::patch('/restaurants/{id}/status', [RestaurantController::class, 'toggleStatus']);
    });
    
    // Aquí podemos mover después las rutas de administración de platillos que requieren dueño o superadmin
});

// Endpoint público para que el comensal vea el menú al escanear el QR
//Route::get('/restaurants/{slug}/menu', [MenuController::class, 'showMenu']);
// Endpoint público para que el comensal vea el menú al escanear el QR (acepta slug o id)
Route::get('/restaurants/{identifier}/menu', [MenuController::class, 'showMenu']);

// Endpoints de administración del restaurante
Route::post('/restaurants/{restaurantId}/dishes', [MenuController::class, 'storeDish']);
Route::patch('/restaurants/{restaurantId}/dishes/{dishId}', [MenuController::class, 'toggleAvailability']);
Route::delete('/restaurants/{restaurantId}/dishes/{dishId}', [MenuController::class, 'destroyDish']);
Route::patch('/dishes/{id}/toggle-availability', [MenuController::class, 'toggleAvailability']);
Route::post('/restaurants/{restaurantId}/videos', [MenuController::class, 'uploadAndProcessVideo']);
//Route::patch('/dishes/{id}/toggle-availability', [MenuController::class, 'toggleAvailability']);
//Route::post('/restaurants/{restaurantId}/dishes', [MenuController::class, 'storeDish']);
//Route::post('/restaurants/{restaurantId}/videos', [MenuController::class, 'uploadAndProcessVideo']);