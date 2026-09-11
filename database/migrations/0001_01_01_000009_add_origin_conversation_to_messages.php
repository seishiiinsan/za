<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Conversation d'origine d'un message détaché lors d'un split :
            // il n'appartient plus à aucune conversation mais reste récupérable
            // au retour en mode partagé.
            $table->foreignId('origin_conversation_id')->nullable()->after('conversation_id')
                ->constrained('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('origin_conversation_id');
        });
    }
};
