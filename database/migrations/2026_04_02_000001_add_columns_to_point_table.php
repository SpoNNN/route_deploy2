<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('point', function (Blueprint $table) {
            $table->string('email')->nullable()->after('description');
            $table->integer('duration_minutes')->default(30)->after('email');
        });
    }

    public function down()
    {
        Schema::table('point', function (Blueprint $table) {
            $table->dropColumn(['email', 'duration_minutes']);
        });
    }
};
