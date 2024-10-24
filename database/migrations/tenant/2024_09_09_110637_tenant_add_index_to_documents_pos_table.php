<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddIndexToDocumentsPOSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear el índice en la columna external_id
        DB::statement('CREATE INDEX idx_external_id ON documents_pos (external_id)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar el índice si se revierte la migración
        DB::statement('DROP INDEX idx_external_id ON documents_pos');
    }
}
