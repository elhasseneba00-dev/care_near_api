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
        Schema::create('care_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_user_id');
            $table->unsignedBigInteger('nurse_user_id')->nullable();
            $table->string('care_type', 80);
            $table->text('description')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->text('address');
            $table->text('city')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->string('status', 20)->default('PENDING');
            $table->foreign('patient_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('nurse_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['patient_user_id', 'status']);
            $table->index(['nurse_user_id', 'status']);

            $table->string('visibility', 20)->default('BROADCAST')->after('status');
            $table->unsignedBigInteger('target_nurse_user_id')->nullable()->after('nurse_user_id');
            $table->timestampTz('expires_at')->nullable()->after('scheduled_at');
            $table->foreign('target_nurse_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['visibility', 'status']);
            $table->index(['target_nurse_user_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_requests');
    }
};
