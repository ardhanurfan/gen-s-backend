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
        Schema::create('audio_playlists', function (Blueprint $table) {
            $table->foreignId('audioId')->constrained('audios')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('playlistId')->constrained('playlists')->onDelete('cascade')->onUpdate('cascade');
            $table->primary(['audioId', 'playlistId']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_playlists');
    }
};
