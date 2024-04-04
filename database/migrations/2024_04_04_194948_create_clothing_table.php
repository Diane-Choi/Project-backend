<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clothing', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('name');
            $table->string('brand');
            $table->string('colour');
            $table->string('type_id');
            $table->text('description');
            $table->string('image_path');

            $table->foreign('type_id')->references('id')->on('types'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clothing');
    }
};
