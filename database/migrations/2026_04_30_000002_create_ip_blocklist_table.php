<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_blocklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->text('reason');
            $table->float('confidence');
            $table->string('blocked_by')->default('agent');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_blocklist');
    }
};
