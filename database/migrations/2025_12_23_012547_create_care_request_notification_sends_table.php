<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('care_request_notification_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('care_request_id');
            $table->foreign('care_request_id')->references('id')->on('care_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('nurse_user_id');
            $table->foreign('nurse_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('event', 20); // CREATED for now
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['care_request_id', 'nurse_user_id', 'event']);
            $table->index(['nurse_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_request_notification_sends');
    }
};
