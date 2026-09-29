<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_usaha')->default('Toko Kopi Rasa');
            $table->string('tier')->default('growth'); // starter | growth | scale
            $table->unsignedInteger('kuota_karyawan')->default(20);
            $table->unsignedTinyInteger('bobot_kehadiran')->default(40);
            $table->unsignedTinyInteger('bobot_target')->default(30);
            $table->unsignedTinyInteger('bobot_peer')->default(30);
            $table->boolean('maintenance_aktif')->default(true);
            $table->boolean('absen_mandiri_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('monthly_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 7);
            $table->decimal('skor_kehadiran', 4, 2)->default(0);
            $table->decimal('skor_target', 4, 2)->default(0);
            $table->decimal('skor_peer', 4, 2)->nullable();
            $table->decimal('skor_akhir', 4, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_scores');
        Schema::dropIfExists('settings');
    }
};
