<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

/**
 * UnitsOfMeasureSeeder - Seeds common units of measure
 */
class UnitsOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Quantity units
            [
                'name' => 'Piece',
                'name_ar' => 'قطعة',
                'symbol' => 'pc',
                'type' => 'quantity',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Dozen',
                'name_ar' => 'درزن',
                'symbol' => 'dz',
                'type' => 'quantity',
                'conversion_factor' => 12.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Box',
                'name_ar' => 'صندوق',
                'symbol' => 'box',
                'type' => 'quantity',
                'conversion_factor' => 1.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Pack',
                'name_ar' => 'علبة',
                'symbol' => 'pk',
                'type' => 'quantity',
                'conversion_factor' => 1.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Set',
                'name_ar' => 'طقم',
                'symbol' => 'set',
                'type' => 'quantity',
                'conversion_factor' => 1.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Pair',
                'name_ar' => 'زوج',
                'symbol' => 'pr',
                'type' => 'quantity',
                'conversion_factor' => 2.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 6,
            ],
            // Weight units
            [
                'name' => 'Gram',
                'name_ar' => 'جرام',
                'symbol' => 'g',
                'type' => 'weight',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Kilogram',
                'name_ar' => 'كيلوجرام',
                'symbol' => 'kg',
                'type' => 'weight',
                'conversion_factor' => 1000.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Ton',
                'name_ar' => 'طن',
                'symbol' => 't',
                'type' => 'weight',
                'conversion_factor' => 1000000.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Pound',
                'name_ar' => 'رطل',
                'symbol' => 'lb',
                'type' => 'weight',
                'conversion_factor' => 453.592000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 13,
            ],
            // Length units
            [
                'name' => 'Meter',
                'name_ar' => 'متر',
                'symbol' => 'm',
                'type' => 'length',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Centimeter',
                'name_ar' => 'سنتيمتر',
                'symbol' => 'cm',
                'type' => 'length',
                'conversion_factor' => 0.010000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'name' => 'Millimeter',
                'name_ar' => 'مليمتر',
                'symbol' => 'mm',
                'type' => 'length',
                'conversion_factor' => 0.001000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 22,
            ],
            [
                'name' => 'Inch',
                'name_ar' => 'بوصة',
                'symbol' => 'in',
                'type' => 'length',
                'conversion_factor' => 0.025400,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 23,
            ],
            [
                'name' => 'Foot',
                'name_ar' => 'قدم',
                'symbol' => 'ft',
                'type' => 'length',
                'conversion_factor' => 0.304800,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 24,
            ],
            // Volume units
            [
                'name' => 'Liter',
                'name_ar' => 'لتر',
                'symbol' => 'L',
                'type' => 'volume',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Milliliter',
                'name_ar' => 'مليلتر',
                'symbol' => 'ml',
                'type' => 'volume',
                'conversion_factor' => 0.001000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 31,
            ],
            [
                'name' => 'Gallon',
                'name_ar' => 'جالون',
                'symbol' => 'gal',
                'type' => 'volume',
                'conversion_factor' => 3.785400,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 32,
            ],
            // Area units
            [
                'name' => 'Square Meter',
                'name_ar' => 'متر مربع',
                'symbol' => 'm2',
                'type' => 'area',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'Square Foot',
                'name_ar' => 'قدم مربع',
                'symbol' => 'ft2',
                'type' => 'area',
                'conversion_factor' => 0.092900,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 41,
            ],
            // Time units
            [
                'name' => 'Hour',
                'name_ar' => 'ساعة',
                'symbol' => 'hr',
                'type' => 'time',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'Day',
                'name_ar' => 'يوم',
                'symbol' => 'd',
                'type' => 'time',
                'conversion_factor' => 24.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 51,
            ],
            [
                'name' => 'Week',
                'name_ar' => 'أسبوع',
                'symbol' => 'wk',
                'type' => 'time',
                'conversion_factor' => 168.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 52,
            ],
            [
                'name' => 'Month',
                'name_ar' => 'شهر',
                'symbol' => 'mo',
                'type' => 'time',
                'conversion_factor' => 720.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 53,
            ],
        ];

        foreach ($units as $unitData) {
            UnitOfMeasure::updateOrCreate(
                ['symbol' => $unitData['symbol'], 'type' => $unitData['type']],
                $unitData
            );
        }

	    // Default behavior: time-based units are not shown in Product forms.
	    // (They can still be used by other modules, and can be enabled later if needed.)
	    UnitOfMeasure::where('type', 'time')->update(['supports_products' => false]);
    }
}
