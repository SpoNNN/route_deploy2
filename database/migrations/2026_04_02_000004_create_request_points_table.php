<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('request_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('point_id')->constrained('point')->onDelete('cascade');
            $table->enum('status', ['waiting', 'confirmed', 'rejected'])->default('waiting');
            $table->text('comment')->nullable();
            $table->dateTime('voted_at')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_points');
    }
};