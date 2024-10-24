<?php

namespace App\Imports;

use App\Models\Tenant\Item;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Modules\Item\Models\Category;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Color;
use Modules\Item\Models\Size;


class ItemsImport implements ToCollection
{
    use Importable;

    protected $data;

    public function collection(Collection $rows)
    {
        $total = count($rows);
        $registered = 0;
        unset($rows[0]);
        foreach ($rows as $row)
        {
            $name = $row[0];
            $item_type_id = '01';
            $internal_id = ($row[1])?:null;
            $unit_type_id = $row[2];
            $currency_type_id = $row[3];
            $sale_unit_price = $row[4];
            $tax_id = $row[5];
            $purchase_unit_price = ($row[6])?:0;
            $purchase_tax_id = ($row[7])?:null;
            $stock = $row[8];
            $stock_min = $row[9];
            $category_name = $row[10];
            $brand_name = $row[11];
            $color_name = $row[12];
            $size_name = $row[13];
            if($internal_id) {
                $item = Item::where('internal_id', $internal_id)->first();
            } else {
                $item = null;
            }

            if(!$item) {
                $category = Category::updateOrCreate(['name' => $category_name]);
                $brand = Brand::updateOrCreate(['name' => $brand_name]);
                $color = Color::updateOrCreate(['name' => $color_name]);
                $size = Size::updateOrCreate(['name' => $size_name]);

                Item::create([
                    'name' => $name,
                    'item_type_id' => $item_type_id,
                    'internal_id' => $internal_id,
                    'unit_type_id' => $unit_type_id,
                    'currency_type_id' => $currency_type_id,
                    'sale_unit_price' => $sale_unit_price,
                    'tax_id' => $tax_id,
                    'purchase_unit_price' => $purchase_unit_price,
                    'purchase_tax_id' => $purchase_tax_id,
                    'stock' => $stock,
                    'stock_min' => $stock_min,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'color_id' => $color->id,
                    'size_id' => $size->id,
                ]);
                $registered += 1;
            }else{
                $item->update([
                    'name' => $name,
                    'item_type_id' => $item_type_id,
                    'internal_id' => $internal_id,
                    'unit_type_id' => $unit_type_id,
                    'currency_type_id' => $currency_type_id,
                    'sale_unit_price' => $sale_unit_price,
                    'tax_id' => $tax_id,
                    'purchase_unit_price' => $purchase_unit_price,
                    'purchase_tax_id' => $purchase_tax_id,
                    'stock_min' => $stock_min,
                ]);
                $registered += 1;
            }
        }
        $this->data = compact('total', 'registered');
    }

    public function getData()
    {
        return $this->data;
    }

}
