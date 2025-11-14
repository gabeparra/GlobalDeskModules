<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apibridge_webhooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url');
            $table->json('events');
            $table->json('mailboxes')->nullable();
            $table->string('secret')->nullable();
            $table->timestamp('last_run_time')->nullable();
            $table->text('last_run_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apibridge_webhooks');
    }
};



