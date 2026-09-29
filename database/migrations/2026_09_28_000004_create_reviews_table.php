<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('selesai')->default(false);
            $table->timestamps();
            $table->unique(['periode', 'reviewer_id', 'reviewee_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('komunikasi');
            $table->unsignedTinyInteger('inisiatif');
            $table->unsignedTinyInteger('kepatuhan_sop');
            $table->unsignedTinyInteger('kerjasama');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['periode', 'reviewer_id', 'reviewee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('review_assignments');
    }
};
