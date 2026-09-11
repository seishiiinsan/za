<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alter_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Une notification appartient à un alter. Sa remontée au système
            // dépend du réglage de cet alter, jamais d'un défaut global.
            $table->foreignId('alter_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['alter_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alter_notifications');
    }
};
