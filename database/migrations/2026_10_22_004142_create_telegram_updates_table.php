<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('update_id')->unique();
            $table->string('update_type', 32);
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->string('status', 32)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['telegram_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_updates');
    }
};
