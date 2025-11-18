<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxPrice;

class TaxPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxPrices = [
            [
                'name' => 'VAT',
                'rate' => 12.00,
                'description' => 'Value Added Tax - Standard rate for Philippines',
                'is_active' => true
            ],
            [
                'name' => 'GST',
                'rate' => 0.00,
                'description' => 'Goods and Services Tax - Currently inactive',
                'is_active' => false
            ]
        ];

        foreach ($taxPrices as $taxPrice) {
            TaxPrice::updateOrCreate(
                ['name' => $taxPrice['name']],
                $taxPrice
            );
        }
    }
}
