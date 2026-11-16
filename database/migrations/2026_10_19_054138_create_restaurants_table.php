<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('restaurants', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique(); // Identificador amigable (ej. burgers-grill)
        $table->string('owner_name');
        $table->string('plan')->default('Esencial');
        $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('restaurants');
    }
}
