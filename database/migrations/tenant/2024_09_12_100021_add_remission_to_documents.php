<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemissionToDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verifica si la columna 'remission_id' no existe antes de agregarla
        if (!Schema::hasColumn('documents', 'remission_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedInteger('remission_id')->nullable()->after('order_note_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Verifica si la columna existe antes de eliminarla
        if (Schema::hasColumn('documents', 'remission_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('remission_id');
            });
        }
    }
}
