<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('approved_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('route')->onDelete('cascade');
            $table->dateTime('event_date');
            $table->integer('max_users')->default(20);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('approved_schedule');
    }
};