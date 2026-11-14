<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "I'm getting this." One claim per item; never shown to the item's
     * owner, and shown to everyone else without saying who.
     */
    public function up(): void
    {
        Schema::create('gift_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_claims');
    }
};
