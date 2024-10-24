<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddFieldsToItems extends Migration
{
    public function up()
    {
        try {
            if (!Schema::hasTable('colors')) {
                Schema::create('colors', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('sizes')) {
                Schema::create('sizes', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->timestamps();
                });
            }

            Schema::table('items', function (Blueprint $table) {
                if (!Schema::hasColumn('items', 'color_id')) {
                    $table->unsignedInteger('color_id')->nullable()->after('brand_id');
                    $table->foreign('color_id')->references('id')->on('colors');
                }

                if (!Schema::hasColumn('items', 'size_id')) {
                    $table->unsignedInteger('size_id')->nullable()->after('brand_id');
                    $table->foreign('size_id')->references('id')->on('sizes');
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Puedes registrar el error si lo deseas, o ignorarlo
            // Log::error($e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');

            $table->dropForeign(['size_id']);
            $table->dropColumn('size_id');
        });
    }
}
