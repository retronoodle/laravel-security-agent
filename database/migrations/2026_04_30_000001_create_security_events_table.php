<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_security_events', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('pattern_type', 50);
            $table->text('raw_excerpt');
            $table->text('agent_summary')->nullable();
            $table->float('confidence')->nullable();
            $table->enum('outcome', ['pending', 'blocked', 'alerted', 'ignored'])->default('pending');
            $table->timestamps();

            $table->index('ip_address');
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_security_events');
    }
};
