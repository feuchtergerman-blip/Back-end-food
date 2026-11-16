<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDishesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('dishes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('restaurant_id')->constrained()->onDelete('cascade'); // Aislamiento multi-tenant
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('price', 8, 2);
        $table->boolean('available')->default(true); // Control de stock en tiempo real
        $table->string('video_url')->nullable(); // Ruta del video o enlace S3/R2
        $table->json('tags')->nullable(); // Etiquetas como ['Favorito', 'Picante']
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dishes');
    }
}
