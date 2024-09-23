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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->double('price');
            $table->text('description');
            $table->string('image');
            $table->unsignedInteger('qty_available')->nullable();
            $table->bigInteger('category')->unsigned()->index()->nullable();
            $table->foreign('category')->references('id')->on('categories')->onDelete('cascade');
            $table->bigInteger('guage')->unsigned()->index()->nullable();
            $table->foreign('guage')->references('id')->on('guages')->onDelete('cascade');
            $table->bigInteger('added_by')->unsigned()->index()->nullable();
            $table->foreign('added_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
