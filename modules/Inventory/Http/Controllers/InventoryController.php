<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
//use App\Models\Tenant\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Http\Resources\InventoryCollection;
use Modules\Inventory\Http\Resources\InventoryResource;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Models\InventoryTransaction;
use Modules\Inventory\Traits\InventoryTrait;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Http\Requests\InventoryRequest;
use Modules\Item\Models\ItemLot;
use Modules\Item\Models\ItemLotsGroup;
use Barryvdh\DomPDF\Facade as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Imports\TransactionInputImport;


class InventoryController extends Controller
{
    use InventoryTrait;

    public function index()
    {
        return view('inventory::inventory.index');
    }

    public function columns()
    {
        return [
            'name' => 'Producto',
            'internal_id' => 'Código interno',
            'warehouse' => 'Almacén',
        ];
    }

    public function records(Request $request)
    {
        $column = $request->input('column');

        if($column == 'warehouse'){

            $records = ItemWarehouse::with(['item', 'warehouse'])
                            ->whereHas('item', function($query) use($request) {
                                $query->where('unit_type_id', '!=','ZZ');
                                $query->whereNotIsSet();
                            })
                            ->whereHas('warehouse', function($query) use($request) {
                                $query->where('description', 'like', '%' . $request->value . '%');
                            })
                            ->orderBy('item_id');

        }else{

            $records = ItemWarehouse::with(['item', 'warehouse'])
                            ->whereHas('item', function($query) use($request) {
                                $query->where('unit_type_id', '!=','ZZ');
                                $query->whereNotIsSet();
                                $query->where($request->column, 'like', '%' . $request->value . '%');
                            })->orderBy('item_id');

        }

        return new InventoryCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function tables()
    {
        return [
            'items' => $this->optionsItem(),
            'warehouses' => $this->optionsWarehouse()
        ];
    }

    public function record($id)
    {
        $record = new InventoryResource(ItemWarehouse::with(['item', 'warehouse'])->findOrFail($id));

        return $record;
    }


    public function tables_transaction($type)
    {
        return [
            'items' => $this->optionsItemFull(),
            'warehouses' => $this->optionsWarehouse(),
            'inventory_transactions' => $this->optionsInventoryTransaction($type),
        ];
    }

    public function store(Request $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            $item_id = $request->input('item_id');
            $warehouse_id = $request->input('warehouse_id');
            $quantity = $request->input('quantity');

            $item_warehouse = ItemWarehouse::firstOrNew(['item_id' => $item_id,
                                                         'warehouse_id' => $warehouse_id]);
            if($item_warehouse->id) {
                return [
                    'success' => false,
                    'message' => 'El producto ya se encuentra registrado en el almacén indicado.'
                ];
            }

            // $item_warehouse->stock = $quantity;
            // $item_warehouse->save();

            $inventory = new Inventory();
            $inventory->type = 1;
            $inventory->description = 'Stock inicial';
            $inventory->item_id = $item_id;
            $inventory->warehouse_id = $warehouse_id;
            $inventory->quantity = $quantity;
            $inventory->save();

            return  [
                'success' => true,
                'message' => 'Producto registrado en almacén'
            ];
        });

        return $result;
    }


    public function store_transaction(InventoryRequest $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            // dd($request->all());
            $type = $request->input('type');
            $item_id = $request->input('item_id');
            $warehouse_id = $request->input('warehouse_id');
            $inventory_transaction_id = $request->input('inventory_transaction_id');
            $quantity = $request->input('quantity');
            $lot_code = $request->input('lot_code');
            $lots = ($request->has('lots')) ? $request->input('lots'):[];

            $item_warehouse = ItemWarehouse::firstOrNew(['item_id' => $item_id,
                                                         'warehouse_id' => $warehouse_id]);

            $inventory_transaction = InventoryTransaction::findOrFail($inventory_transaction_id);

            if($type == 'output' && ($quantity > $item_warehouse->stock)) {
                return  [
                    'success' => false,
                    'message' => 'La cantidad no puede ser mayor a la que se tiene en el almacén.'
                ];
            }

            $inventory = new Inventory();
            $inventory->type = null;
            $inventory->description = $inventory_transaction->name;
            $inventory->item_id = $item_id;
            $inventory->warehouse_id = $warehouse_id;
            $inventory->quantity = $quantity;
            $inventory->inventory_transaction_id = $inventory_transaction_id;
            $inventory->lot_code = $lot_code;
            $inventory->save();


            $lots_enabled = isset($request->lots_enabled) ? $request->lots_enabled:false;

            if($type == 'input'){
                foreach ($lots as $lot){

                    /*$inventory->lots()->create([
                        'date' => $lot['date'],
                        'series' => $lot['series'],
                        'item_id' => $item_id,
                        'warehouse_id' => $warehouse_id,
                        'has_sale' => false
                    ]);*/

                    $inventory->lots()->create([
                        'date' => $lot['date'],
                        'series' => $lot['series'],
                        'item_id' => $item_id,
                        'warehouse_id' => $warehouse_id,
                        'has_sale' => false,
                        'state' => $lot['state'],
                    ]);

                }

                if($lots_enabled)
                {
                    ItemLotsGroup::create([
                        'code'  => $lot_code,
                        'quantity'  => $quantity,
                        'date_of_due'  => $request->date_of_due,
                        'item_id' => $item_id
                    ]);
                }


            }else{

                foreach ($lots as $lot){

                    if($lot['has_sale']){

                        $item_lot = ItemLot::findOrFail($lot['id']);
                        // $item_lot->delete();
                        $item_lot->has_sale = true;
                        $item_lot->state = 'Inactivo';
                        $item_lot->save();
                    }

                }

                if(isset($request->IdLoteSelected))
                {
                    $lot = ItemLotsGroup::find($request->IdLoteSelected);
                    $lot->quantity = ($lot->quantity - $quantity);
                    $lot->save();
                }


            }

            return  [
                'success' => true,
                'message' => ($type == 'input') ? 'Ingreso registrado correctamente' : 'Salida registrada correctamente'
            ];
        });

        return $result;
    }


    public function move(Request $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            // dd($request->all());
            $id = $request->input('id');
            $item_id = $request->input('item_id');
            $warehouse_id = $request->input('warehouse_id');
            $warehouse_new_id = $request->input('warehouse_new_id');
            $quantity = $request->input('quantity');
            $quantity_move = $request->input('quantity_move');
            $lots = ($request->has('lots')) ? $request->input('lots'):[];
            $detail = $request->input('detail');

            if($warehouse_id === $warehouse_new_id) {
                return  [
                    'success' => false,
                    'message' => 'El almacén destino no puede ser igual al de origen'
                ];
            }
            if($quantity < $quantity_move) {
                return  [
                    'success' => false,
                    'message' => 'La cantidad a trasladar no puede ser mayor al que se tiene en el almacén.'
                ];
            }

            //Transaction
            // $item_warehouse_new = ItemWarehouse::firstOrNew(['item_id' => $item_id,
            //                                                  'warehouse_id' => $warehouse_new_id]);

            // $stock_new = ($item_warehouse_new)?$item_warehouse_new->stock + $quantity_move:$quantity_move;
            // $item_warehouse_new->stock = $stock_new;
            // $item_warehouse_new->save();

            // $item_warehouse = ItemWarehouse::find($id);
            // $item_warehouse->stock = (float) $quantity - (float)$quantity_move;
            // $item_warehouse->save();

            $inventory = new Inventory();
            $inventory->type = 2;
            $inventory->description = 'Traslado';
            $inventory->item_id = $item_id;
            $inventory->warehouse_id = $warehouse_id;
            $inventory->warehouse_destination_id = $warehouse_new_id;
            $inventory->quantity = $quantity_move;
            $inventory->detail = $detail;

            $inventory->save();

            foreach ($lots as $lot){

                if($lot['has_sale']){

                    $item_lot = ItemLot::findOrFail($lot['id']);
                    $item_lot->warehouse_id = $inventory->warehouse_destination_id;
                    $item_lot->update();

                }

            }

            return  [
                'success' => true,
                'message' => 'Producto trasladado con éxito'
            ];
        });

        return $result;
    }

    public function remove(Request $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            // dd($request->all());
            $item_id = $request->input('item_id');
            $warehouse_id = $request->input('warehouse_id');
            $quantity = $request->input('quantity');
            $quantity_remove = $request->input('quantity_remove');
            $lots = ($request->has('lots')) ? $request->input('lots'):[];

            //Transaction
            $item_warehouse = ItemWarehouse::where('item_id', $item_id)
                                           ->where('warehouse_id', $warehouse_id)
                                           ->first();
            if(!$item_warehouse) {
                return [
                    'success' => false,
                    'message' => 'El producto no se encuentra en el almacén indicado'
                ];
            }

            if($quantity < $quantity_remove) {
                return  [
                    'success' => false,
                    'message' => 'La cantidad a retirar no puede ser mayor al que se tiene en el almacén.'
                ];
            }

            // $item_warehouse->stock = $quantity - $quantity_remove;
            // $item_warehouse->save();

            $inventory = new Inventory();
            $inventory->type = 3;
            $inventory->description = 'Retirar';
            $inventory->item_id = $item_id;
            $inventory->warehouse_id = $warehouse_id;
            $inventory->quantity = $quantity_remove;
            $inventory->save();

            foreach ($lots as $lot){

                if($lot['has_sale']){

                    $item_lot = ItemLot::findOrFail($lot['id']);
                    $item_lot->delete();

                }

            }

            return  [
                'success' => true,
                'message' => 'Producto trasladado con éxito'
            ];
        });

        return $result;
    }

    public function initialize()
    {
        $this->initializeInventory();
    }

    public function download(Request $request)
    {
        $type = $request->type;
        $product = $request->product;
        $quantity = $request->quantity;
        $warehouse = $request->warehouse;
        $reason = $request->reason;

        $pdf = PDF::loadView('inventory::inventory.constancy', compact("type", "product", "quantity", "warehouse", "reason"));
        $filename = "Constancia-{$type}";
        return $pdf->download($filename.'.pdf');
    }


    public function transactionImport(Request $request)
    {
        try {
            if ($request->hasFile('file')) Excel::import(new TransactionInputImport($request->warehouse_id), $request->file('file'));
        }
        catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();

            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'Errores de validación durante la importación.',
                'errors' => $errors
            ], 422);
        }
        catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'message' => 'Importación exítosa.'
        ];
    }
}
