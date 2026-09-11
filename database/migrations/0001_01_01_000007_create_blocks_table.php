<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_alter_id')->constrained('alters')->cascadeOnDelete();
            // alter | system. La cible système n'est jamais révélée au bloqueur :
            // il bloque « le compte derrière ce profil », sans en voir les alters.
            $table->string('target_type');
            $table->unsignedBigInteger('target_ref_id');
            $table->timestamps();
            $table->unique(['blocker_alter_id', 'target_type', 'target_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
