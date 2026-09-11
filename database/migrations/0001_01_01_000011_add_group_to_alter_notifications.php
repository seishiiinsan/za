<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alter_notifications', function (Blueprint $table) {
            // Notifications émises en une fois vers plusieurs alters d'un même
            // système (boîte commune de la messagerie partagée) : les lire une
            // fois les marque lues pour tout le monde.
            $table->uuid('group_uuid')->nullable()->after('type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('alter_notifications', function (Blueprint $table) {
            $table->dropColumn('group_uuid');
        });
    }
};
