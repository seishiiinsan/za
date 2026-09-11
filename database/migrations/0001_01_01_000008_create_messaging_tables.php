<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le correspondant est l'entité adressable : un alter, ou un système
        // entier quand celui-ci a choisi la messagerie partagée. Les quatre
        // combinaisons passent par ce seul modèle, sans logique par cas.
        Schema::create('correspondents', function (Blueprint $table) {
            $table->id();
            $table->string('type');            // alter | system
            $table->unsignedBigInteger('ref_id');
            $table->timestamps();
            $table->unique(['type', 'ref_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Négocié entre les deux côtés, jamais déduit du seul réglage global.
            $table->string('effective_mode');  // personal | shared
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('correspondent_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['conversation_id', 'correspondent_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_correspondent_id')->constrained('correspondents')->cascadeOnDelete();
            // L'alter qui frontait à l'écriture. Sert à l'option « afficher
            // l'auteur·e » et à la migration de mode ; jamais exposé sans réglage.
            $table->foreignId('author_alter_id')->nullable()->constrained('alters')->nullOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('correspondents');
    }
};
