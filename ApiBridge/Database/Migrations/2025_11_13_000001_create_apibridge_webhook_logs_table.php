<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apibridge_webhook_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('webhook_id');
            $table->string('event');
            $table->integer('status_code')->nullable();
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('finished')->default(false);
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamps();

            $table->foreign('webhook_id')
                ->references('id')
                ->on('apibridge_webhooks')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apibridge_webhook_logs');
    }
};



