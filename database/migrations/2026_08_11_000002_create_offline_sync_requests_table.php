<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_requests', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path', 255);
            $table->unsignedSmallInteger('response_status')->default(200);
            $table->longText('response_payload')->nullable();
            $table->text('response_location')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_requests');
    }
};
