<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('point_route', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('route')->onDelete('cascade');
            $table->foreignId('point_id')->constrained('point')->onDelete('cascade');
            $table->integer('order_index'); // порядок точек в маршруте
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_route');
    }
};