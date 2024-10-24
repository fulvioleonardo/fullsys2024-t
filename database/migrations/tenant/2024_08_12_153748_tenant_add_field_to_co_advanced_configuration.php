<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddFieldToCoAdvancedConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_advanced_configuration', function (Blueprint $table) {
            $table->string('item_tax_included')->default(false)->after('uvt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_advanced_configuration', function (Blueprint $table) {
            $table->dropColumn('item_tax_included');
        });
    }
}
