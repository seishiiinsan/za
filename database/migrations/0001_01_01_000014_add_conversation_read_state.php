<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            // Dernière lecture d'un correspondant : sert à marquer une
            // conversation non lue dans la liste.
            $table->timestamp('last_read_at')->nullable()->after('correspondent_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('last_read_at');
        });
    }
};
