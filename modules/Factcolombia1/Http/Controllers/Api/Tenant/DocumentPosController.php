<?php

namespace Modules\Factcolombia1\Http\Controllers\Api\Tenant;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\DocumentPosController as WebDocumentPosController;
use App\Http\Resources\Tenant\DocumentPosResource;
use App\Models\Tenant\DocumentPos;


class DocumentPosController extends Controller
{
    public function voided($id) {

        $webController = (new WebDocumentPosController)->anulate($id);
        $search = DocumentPos::find($id);
        $record = new DocumentPosResource($search);

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'state_type' => $search->state_type,
                'document' => $record
            ]
        ];
    }
}