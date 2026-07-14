<?php

namespace Database\Seeders;

use App\Models\Product\Product;
use App\Models\Category\Category;
use App\Models\Product\Brand;
use App\Models\Product\Unit;
use App\Models\Inventory\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Admin User
        $user = User::where('email', 'admin@apmalls.com')->first() ?? User::first();
        $userId = $user ? $user->id : null;

        $products = [
            [
                'category' => 'Oil & Ghee',
                'brand' => 'Fortune',
                'unit' => 'L',
                'name' => 'Fortune Mustard Oil 1L',
                'sku' => 'FOR-MUST-OIL-1L',
                'barcode' => '8906007281011',
                'hsn_code' => '1514',
                'short_description' => 'Fortune Premium Kachi Ghani Pure Mustard Oil.',
                'description' => 'Fortune Premium Kachi Ghani Pure Mustard Oil has a strong aroma and flavor that enhances the taste of food. Rich in Omega 3 and monounsaturated fatty acids, it is good for health.',
                'purchase_price' => 140.00,
                'selling_price' => 165.00,
                'mrp' => 180.00,
                'tax_percent' => 5.00,
                'discount_percent' => 8.33,
                'stock' => 120,
                'minimum_stock' => 10,
                'featured' => true,
                'new_arrival' => false,
                'best_seller' => true,
            ],
            [
                'category' => 'Flour',
                'brand' => 'Aashirvaad',
                'unit' => 'Kg',
                'name' => 'Aashirvaad Shudh Chakki Atta 5kg',
                'sku' => 'AASH-ATTA-5KG',
                'barcode' => '8901725181223',
                'hsn_code' => '1101',
                'short_description' => '100% pure whole wheat flour with 0% maida.',
                'description' => 'Aashirvaad Shudh Chakki Atta is made from the finest grains - heavy on the palm, golden amber in colour and hard in bite. It is ground using modern chakki-grinding process for perfect fluffy rotis.',
                'purchase_price' => 200.00,
                'selling_price' => 235.00,
                'mrp' => 250.00,
                'tax_percent' => 0.00,
                'discount_percent' => 6.00,
                'stock' => 80,
                'minimum_stock' => 15,
                'featured' => true,
                'new_arrival' => true,
                'best_seller' => true,
            ],
            [
                'category' => 'Salt',
                'brand' => 'Tata',
                'unit' => 'Kg',
                'name' => 'Tata Salt Iodized 1kg',
                'sku' => 'TATA-SALT-1KG',
                'barcode' => '8901058002315',
                'hsn_code' => '2501',
                'short_description' => 'Desh ka Namak, vacuum evaporated iodized salt.',
                'description' => 'Tata Salt has been a trusted choice for generations, providing iodine required for mental development in children and overall health.',
                'purchase_price' => 20.00,
                'selling_price' => 25.00,
                'mrp' => 28.00,
                'tax_percent' => 0.00,
                'discount_percent' => 10.71,
                'stock' => 250,
                'minimum_stock' => 30,
                'featured' => false,
                'new_arrival' => false,
                'best_seller' => true,
            ],
            [
                'category' => 'Dairy Products',
                'brand' => 'Amul',
                'unit' => 'Pack',
                'name' => 'Amul Butter 100g',
                'sku' => 'AMUL-BUTTER-100G',
                'barcode' => '8901262010011',
                'hsn_code' => '0405',
                'short_description' => 'Amul Butter, utterly butterly delicious.',
                'description' => 'The classic pasteurized salted butter from Amul. Smooth, creamy, and ideal for spreading, cooking, and baking.',
                'purchase_price' => 46.00,
                'selling_price' => 52.00,
                'mrp' => 55.00,
                'tax_percent' => 12.00,
                'discount_percent' => 5.45,
                'stock' => 150,
                'minimum_stock' => 20,
                'featured' => true,
                'new_arrival' => false,
                'best_seller' => true,
            ],
            [
                'category' => 'Biscuits',
                'brand' => 'Parle',
                'unit' => 'Pack',
                'name' => 'Parle-G Gluco Biscuits 250g',
                'sku' => 'PARLE-G-250G',
                'barcode' => '8901160010021',
                'hsn_code' => '1905',
                'short_description' => 'G means Genius! The world\'s largest selling biscuit brand.',
                'description' => 'Parle-G filled with the goodness of milk and wheat, has been a source of all-round nourishment for millions.',
                'purchase_price' => 15.00,
                'selling_price' => 18.00,
                'mrp' => 20.00,
                'tax_percent' => 18.00,
                'discount_percent' => 10.00,
                'stock' => 400,
                'minimum_stock' => 50,
                'featured' => false,
                'new_arrival' => false,
                'best_seller' => true,
            ],
            [
                'category' => 'Tea & Coffee',
                'brand' => 'Tata',
                'unit' => 'Pack',
                'name' => 'Tata Tea Premium 1kg',
                'sku' => 'TATA-TEA-PREM-1KG',
                'barcode' => '8901058002209',
                'hsn_code' => '0902',
                'short_description' => 'Badi Patti Choti Patti Blend for rich taste and strength.',
                'description' => 'Tata Tea Premium has a unique blend of big tea leaves for great aroma and small tea leaves for a strong taste.',
                'purchase_price' => 320.00,
                'selling_price' => 380.00,
                'mrp' => 420.00,
                'tax_percent' => 5.00,
                'discount_percent' => 9.52,
                'stock' => 90,
                'minimum_stock' => 12,
                'featured' => false,
                'new_arrival' => true,
                'best_seller' => false,
            ],
            [
                'category' => 'LED Lights',
                'brand' => 'Havells',
                'unit' => 'Pc',
                'name' => 'Havells 9W Cool Daylight LED Bulb',
                'sku' => 'HAV-LED-9W',
                'barcode' => '8907338001122',
                'hsn_code' => '8539',
                'short_description' => 'Energy efficient, bright cool daylight bulb.',
                'description' => 'Havells LED Bulbs save up to 90% energy compared to regular incandescent lamps. Offers exceptionally long life and crisp white light.',
                'purchase_price' => 75.00,
                'selling_price' => 99.00,
                'mrp' => 140.00,
                'tax_percent' => 18.00,
                'discount_percent' => 29.28,
                'stock' => 100,
                'minimum_stock' => 15,
                'featured' => true,
                'new_arrival' => true,
                'best_seller' => false,
            ],
            [
                'category' => 'Interior Paint',
                'brand' => 'Asian Paints',
                'unit' => 'Can',
                'name' => 'Asian Paints Tractor Emulsion White 4L',
                'sku' => 'AP-TE-WHITE-4L',
                'barcode' => '8901234567891',
                'hsn_code' => '3209',
                'short_description' => 'Beautiful matte finish for interior walls.',
                'description' => 'Tractor Emulsion gives your walls a smooth matte finish at an affordable price. It offers a wide range of shades and superior coverage.',
                'purchase_price' => 440.00,
                'selling_price' => 540.00,
                'mrp' => 620.00,
                'tax_percent' => 18.00,
                'discount_percent' => 12.90,
                'stock' => 40,
                'minimum_stock' => 8,
                'featured' => true,
                'new_arrival' => false,
                'best_seller' => false,
            ],
            [
                'category' => 'Detergent',
                'brand' => 'Surf Excel',
                'unit' => 'Kg',
                'name' => 'Surf Excel Easy Wash Detergent Powder 1kg',
                'sku' => 'SURF-EASY-1KG',
                'barcode' => '8901030753006',
                'hsn_code' => '3402',
                'short_description' => 'Detergent powder for effortless stain removal.',
                'description' => 'Surf Excel Easy Wash contains power of 10 hands, which makes it easy to remove tough stains like mud, ink, oil, and chocolate easily.',
                'purchase_price' => 115.00,
                'selling_price' => 135.00,
                'mrp' => 150.00,
                'tax_percent' => 18.00,
                'discount_percent' => 10.00,
                'stock' => 110,
                'minimum_stock' => 15,
                'featured' => false,
                'new_arrival' => false,
                'best_seller' => true,
            ],
            [
                'category' => 'Soap',
                'brand' => 'Lux',
                'unit' => 'Pc',
                'name' => 'Lux Soft Touch Soap Bar 100g',
                'sku' => 'LUX-SOAP-100G',
                'barcode' => '8901030789012',
                'hsn_code' => '3401',
                'short_description' => 'Infused with French Rose and Almond Oil.',
                'description' => 'Lux Soft Glow bar soap is your key to soft, smooth and glowing skin. Enriched with moisturizing Silk Essence, French Rose and Almond Oil.',
                'purchase_price' => 24.00,
                'selling_price' => 31.00,
                'mrp' => 35.00,
                'tax_percent' => 18.00,
                'discount_percent' => 11.43,
                'stock' => 250,
                'minimum_stock' => 30,
                'featured' => false,
                'new_arrival' => false,
                'best_seller' => true,
            ],
        ];

        foreach ($products as $prod) {
            $category = Category::where('slug', Str::slug($prod['category']))->first();
            $brand = Brand::where('slug', Str::slug($prod['brand']))->first();
            $unit = Unit::where('short_name', $prod['unit'])->first();

            if (!$category || !$unit) {
                // If the exact match fails (unlikely given seeders), skip or log warning.
                continue;
            }

            $product = Product::updateOrCreate(
                ['sku' => $prod['sku']],
                [
                    'category_id'       => $category->id,
                    'brand_id'          => $brand ? $brand->id : null,
                    'unit_id'           => $unit->id,
                    'name'              => $prod['name'],
                    'slug'              => Str::slug($prod['name']),
                    'barcode'           => $prod['barcode'],
                    'hsn_code'          => $prod['hsn_code'],
                    'thumbnail'         => null,
                    'short_description' => $prod['short_description'],
                    'description'       => $prod['description'],
                    'purchase_price'    => $prod['purchase_price'],
                    'selling_price'     => $prod['selling_price'],
                    'mrp'               => $prod['mrp'],
                    'tax_percent'       => $prod['tax_percent'],
                    'discount_percent'  => $prod['discount_percent'],
                    'stock'             => $prod['stock'],
                    'minimum_stock'     => $prod['minimum_stock'],
                    'featured'          => $prod['featured'],
                    'new_arrival'       => $prod['new_arrival'],
                    'best_seller'       => $prod['best_seller'],
                    'is_active'         => true,
                    'created_by'        => $userId,
                    'updated_by'        => $userId,
                ]
            );

            // Seed/Update Stocks Table
            Stock::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'current_stock'   => $prod['stock'],
                    'reserved_stock'  => 0,
                    'available_stock' => $prod['stock'],
                    'minimum_stock'   => $prod['minimum_stock'],
                    'maximum_stock'   => $prod['stock'] * 2,
                ]
            );
        }
    }
}
