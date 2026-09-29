<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('peer_messages');
    }

    public function down(): void
    {
        // Tabel pesan rekan sengaja tidak dipulihkan; catatan peer review menjadi sumber feedback rekan.
    }
};
