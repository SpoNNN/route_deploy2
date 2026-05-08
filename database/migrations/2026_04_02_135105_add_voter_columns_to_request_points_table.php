<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('request_points', function (Blueprint $table) {
            $table->string('voter_ip')->nullable()->after('voted_at');
            $table->string('voter_user_agent')->nullable()->after('voter_ip');
        });
    }

    public function down()
    {
        Schema::table('request_points', function (Blueprint $table) {
            $table->dropColumn(['voter_ip', 'voter_user_agent']);
        });
    }
};
