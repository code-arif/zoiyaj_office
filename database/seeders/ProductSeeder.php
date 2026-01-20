<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class ProductSeeder extends Seeder
{

    private function decimalOrNull($value)
    {
        return ($value === '' || $value === null)
            ? null
            : (float) $value;
    }
    public function run(): void
    {
        $path = storage_path('app/seed/products.xlsx');

        $rows = Excel::toArray([], $path)[0]; // first sheet

        $headers = array_map('strtolower', $rows[0]);
        unset($rows[0]); // remove header row

        foreach ($rows as $row) {

            $data = array_combine($headers, $row);


            // 🔹 PRODUCT
            $product = Product::updateOrCreate(
                ['uniq_id' => $data['uniq_id'] ?? null],
                [
                    'product_id' => $data['product_id'] ?? null,
                    'category_id' => Category::first()->id,
                    'sku' => $data['sku'] ?? null,
                    'upc' => $data['upc'] ?? null, // BARCODE
                    'asin' => $data['asin'] ?? null,
                    'name' => $data['product_name'] ?? '',
                    'product_name' => $data['product_name'] ?? '',
                    'brand_name' => $data['brand_name'] ?? null,
                    'site_name' => $data['site_name'] ?? null,
                    'price' => $this->decimalOrNull($data['price'] ?? null),
                    'original_price' => $this->decimalOrNull($data['original_price'] ?? null),
                    'currency' => $data['currency'] ?? null,
                    'availability' => $data['availability'] ?? null,
                    'description' => $data['description'] ?? null,
                    'ingredients' => $data['ingredients'] ?? null,
                    'product_url' => $data['product_url'] ?? null,
                    'primary_image_url' => $data['primary_image_url'] ?? null,
                    'size' =>  0,
                ]
            );

            // 🔹 CATEGORY TREE
            $parent = null;

            foreach (['category_1', 'category_2', 'category_3'] as $level) {
                if (!empty($data[$level])) {

                    $category = Category::firstOrCreate(
                        [
                            'title' => trim($data[$level]),
                            'parent_id' => $parent?->id,
                        ],
                        [
                            'slug' => \Str::slug($data[$level]),
                            'status' => 'active',
                        ]
                    );

                    $parent = $category;
                }
            }

            // 🔹 ATTACH FINAL CATEGORY
            if ($parent) {
                $product->category_id = $parent->id;
                $product->save();
            }
        }
    }
}
