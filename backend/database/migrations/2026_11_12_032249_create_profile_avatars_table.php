<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile photos live in the database (small re-encoded WebP, base64), so
     * moving the database moves them too. Kept apart from profiles so listing
     * profiles never loads image data; avatar_version (random, changes on
     * every upload) goes in the URL so browsers can cache each photo forever.
     */
    public function up(): void
    {
        Schema::create('profile_avatars', function (Blueprint $table) {
            $table->foreignId('profile_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mime_type', 50);
            $table->text('image');
            $table->timestamps();
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('avatar_version', 20)->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('avatar_version');
        });
        Schema::dropIfExists('profile_avatars');
    }
};
