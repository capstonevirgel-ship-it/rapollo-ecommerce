<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'logo_url' => 'brands/nike-logo.png',
                'meta_title' => 'Nike - Just Do It',
                'meta_description' => 'Official Nike products - athletic wear, shoes, and accessories for all sports.'
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'logo_url' => 'brands/adidas-logo.png',
                'meta_title' => 'Adidas - Impossible is Nothing',
                'meta_description' => 'Adidas sportswear, footwear, and accessories for athletes and fashion enthusiasts.'
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'logo_url' => 'brands/puma-logo.png',
                'meta_title' => 'Puma - Forever Faster',
                'meta_description' => 'Puma athletic wear, shoes, and lifestyle products for the modern athlete.'
            ],
            [
                'name' => 'Uniqlo',
                'slug' => 'uniqlo',
                'logo_url' => 'brands/uniqlo-logo.png',
                'meta_title' => 'Uniqlo - LifeWear',
                'meta_description' => 'Uniqlo casual wear, basics, and functional clothing for everyday life.'
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'logo_url' => 'brands/zara-logo.png',
                'meta_title' => 'Zara - Fashion Forward',
                'meta_description' => 'Zara trendy fashion clothing for men, women, and children.'
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'logo_url' => 'brands/hm-logo.png',
                'meta_title' => 'H&M - Fashion and Quality at the Best Price',
                'meta_description' => 'H&M affordable fashion for the whole family - clothing, accessories, and home goods.'
            ]
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}