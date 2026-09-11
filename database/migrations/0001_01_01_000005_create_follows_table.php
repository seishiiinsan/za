<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            // Relation strictement alter <-> alter.
            $table->foreignId('follower_alter_id')->constrained('alters')->cascadeOnDelete();
            $table->foreignId('followed_alter_id')->constrained('alters')->cascadeOnDelete();
            // Faux tant qu'un alter privé n'a pas accepté la demande.
            $table->boolean('accepted')->default(true);
            $table->timestamps();
            $table->unique(['follower_alter_id', 'followed_alter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
