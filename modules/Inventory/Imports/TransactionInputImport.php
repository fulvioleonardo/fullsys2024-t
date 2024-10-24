<?php

namespace Modules\Inventory\Imports;

use Maatwebsite\Excel\Concerns\{
    WithChunkReading,
    WithHeadingRow,
    WithValidation,
    ToModel
};
use Modules\Factcolombia1\Models\Tenant\Client;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Requests\InventoryRequest;
use App\Models\Tenant\Item;

class TransactionInputImport implements ToModel, WithValidation, WithHeadingRow, WithChunkReading
{
    protected $warehouse_id;

    public function __construct($warehouse_id)
    {
        $this->warehouse_id = $warehouse_id;
    }

    public function headingRow(): int {
        return 1;
    }

    public function chunkSize(): int {
        return 500;
    }

    public function rules(): array {
        return [
            'codigo' => 'required|exists:tenant.items,internal_id',
            'tipo_transaccion' => 'required|exists:tenant.inventory_transactions,id',
            'cantidad' => 'required',
        ];
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model
    */
    public function model(array $row) {
        $item = Item::where('internal_id', $row['codigo'])->first();

        if (!$item) {
            throw new \Exception('Item no encontrado para el código: ' . $row['codigo']);
        }

        // Obtener el ID del item
        $item_id = $item->id;

        $request = new InventoryRequest();
        $request->merge([
            "id"  => null,
            "item_id"  => $item_id,
            "warehouse_id"  => $this->warehouse_id,
            "inventory_transaction_id"  => $row['tipo_transaccion'],
            "quantity"  => $row['cantidad'],
            "type"  => "input",
            "lot_code"  => null,
            "lots_enabled"  => false,
            "series_enabled"  => false,
            "lots"  => [],
            "date_of_due"  => null,
        ]);

        // $inventoryController = new \Modules\Inventory\Http\Controllers\InventoryController();
        $result = app(InventoryController::class)->store_transaction($request);

        if (!$result['success']) {
            throw new \Exception($result['message']);
        }
        return null;
    }
}
