<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One pending sign-in per user: an emailed 6-digit code and link token,
     * stored only as HMAC hashes and deleted once used or superseded.
     */
    public function up(): void
    {
        Schema::create('login_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->char('token_hash', 64)->unique();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('code_expires_at');
            $table->timestamp('link_expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_challenges');
    }
};
