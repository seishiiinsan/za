<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alters', function (Blueprint $table) {
            $table->id();
            // Seul identifiant exposé : deux alters d'un même système ne doivent
            // pas se suivre dans la numérotation.
            $table->uuid('uuid')->unique();
            // JAMAIS exposé publiquement (invariant d'anti-corrélation).
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('pronouns')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('privacy_level')->default('public');
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alters');
    }
};
