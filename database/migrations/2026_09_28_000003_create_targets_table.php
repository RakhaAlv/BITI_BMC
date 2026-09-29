<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 7); // format YYYY-MM
            $table->string('jenis')->default('Penjualan');
            $table->decimal('target', 14, 2);
            $table->decimal('realisasi', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
