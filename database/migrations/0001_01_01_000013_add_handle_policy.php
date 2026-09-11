<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alters', function (Blueprint $table) {
            // Forme canonique du handle : c'est elle qui porte l'unicité, pour
            // que @ka1 et @kai ne puissent pas coexister.
            $table->string('handle_key')->nullable()->after('handle');
            $table->timestamp('handle_changed_at')->nullable()->after('handle_key');
        });

        Schema::table('alters', function (Blueprint $table) {
            $table->unique('handle_key');
        });

        // Un handle libéré par un changement reste en quarantaine : personne ne
        // le reprend le temps que les abonnés s'y retrouvent.
        Schema::create('handle_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('handle_key')->unique();
            $table->foreignId('alter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('available_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handle_reservations');

        Schema::table('alters', function (Blueprint $table) {
            $table->dropUnique(['handle_key']);
            $table->dropColumn(['handle_key', 'handle_changed_at']);
        });
    }
};
