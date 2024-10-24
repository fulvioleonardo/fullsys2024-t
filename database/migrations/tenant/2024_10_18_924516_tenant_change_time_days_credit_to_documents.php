<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantChangeTimeDaysCreditToDocuments extends Migration
{
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('time_days_credit')->change()->nullable()->default(0);
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->tinyInteger('time_days_credit')->change()->nullable()->default(0);
        });
    }
}
