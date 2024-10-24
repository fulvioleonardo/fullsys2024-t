<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Modules\Factcolombia1\Http\Resources\Tenant\AdvancedConfigurationResource;
use App\Models\Tenant\Item;
use App\Http\Controllers\Controller;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Http\Requests\Tenant\AdvancedConfigurationRequest;
use App\Models\Tenant\Document;
use Illuminate\Support\Facades\Log;


class AdvancedConfigurationController extends Controller
{

    public function index()
    {
        return view('factcolombia1::advanced-configuration.index');
    }

    public function record()
    {
        $record = new AdvancedConfigurationResource(AdvancedConfiguration::firstOrFail());
        return  $record;
    }

    public function store(AdvancedConfigurationRequest $request) {

        $id = $request->input('id');

        $record = AdvancedConfiguration::find($id);
        $record->fill($request->all());
        $record->save();

        return [
            'success' => true,
            'message' => 'Configuración actualizada'
        ];
    }


    public function deleteDocumentByResolution(Request $request)
    {
        $records = Document::where('type_document_id', $request->id)->get();

        if ($records->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No se ha encontrado registros'
            ];
        }

        $ids = $records->pluck('id')->toArray();
        Log::info('Deleted records with IDs: ' . implode(', ', $ids));

        try {
            Document::where('type_document_id', $request->id)->delete();
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'No se han eliminado registros'
            ];
        }

        return [
            'success' => true,
            'data' => $records,
            'message' => 'Han sido eliminado los documentos'
        ];
    }
}
