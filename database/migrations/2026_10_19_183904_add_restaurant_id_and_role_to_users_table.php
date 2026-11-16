<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Se agrega la relación y el rol después de la creación de la tabla restaurants
            $table->foreignId('restaurant_id')->nullable()->after('password')->constrained()->onDelete('cascade');
            $table->string('role')->default('owner')->after('restaurant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn(['restaurant_id', 'role']);
        });
    }
};
