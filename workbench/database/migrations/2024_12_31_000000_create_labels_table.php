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
        Schema::create('labels', function (Blueprint $table) {
            $table->id();

            $table->string('src');
            $table->string('uri');
            $table->string('cid')->nullable();
            $table->string('val');
            $table->boolean('neg')->default(false);
            $table->dateTime('cts');
            $table->dateTime('exp')->nullable();
            $table->binary('sig');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
