<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('route')->onDelete('cascade');
            $table->date('proposed_date');
            $table->time('start_time');
            $table->dateTime('deadline');
            $table->enum('status', ['pending', 'approved', 'cancelled', 'completed'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('approval_requests');
    }
};