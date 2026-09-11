<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            // pending | published — pending sert aux co/cross-posts (v2).
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });

        Schema::create('post_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alter_id')->constrained()->cascadeOnDelete();
            $table->boolean('accepted')->default(false);
            $table->timestamps();
            $table->unique(['post_id', 'alter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_authors');
        Schema::dropIfExists('posts');
    }
};
