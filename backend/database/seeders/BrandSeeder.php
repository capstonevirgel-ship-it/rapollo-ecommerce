<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Str;
use Database\Seeders\Traits\ImageUploadTrait;

class BrandSeeder extends Seeder
{
    use ImageUploadTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'logo_filename' => 'adidas.webp',
                'meta_title' => 'Adidas - Impossible is Nothing',
                'meta_description' => 'Adidas sportswear, footwear, and accessories for athletes and fashion enthusiasts.'
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'logo_filename' => 'hm.jpg',
                'meta_title' => 'H&M - Fashion and Quality at the Best Price',
                'meta_description' => 'H&M affordable fashion for the whole family - clothing, accessories, and home goods.'
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'logo_filename' => 'nike.jpg',
                'meta_title' => 'Nike - Just Do It',
                'meta_description' => 'Official Nike products - athletic wear, shoes, and accessories for all sports.'
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'logo_filename' => 'puma.jpg',
                'meta_title' => 'Puma - Forever Faster',
                'meta_description' => 'Puma athletic wear, shoes, and lifestyle products for the modern athlete.'
            ],
            [
                'name' => 'Uniqlo',
                'slug' => 'uniqlo',
                'logo_filename' => 'uniqlo.png',
                'meta_title' => 'Uniqlo - LifeWear',
                'meta_description' => 'Uniqlo casual wear, basics, and functional clothing for everyday life.'
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'logo_filename' => 'zara.png',
                'meta_title' => 'Zara - Fashion Forward',
                'meta_description' => 'Zara trendy fashion clothing for men, women, and children.'
            ]
        ];

        foreach ($brands as $brandData) {
            // Store the filename before removing it
            $logoFilename = $brandData['logo_filename'];
            
            // Upload logo and get the relative path
            $logoUrl = $this->uploadBrandLogo($brandData['slug'], $logoFilename);
            
            // Remove logo_filename from brand data
            unset($brandData['logo_filename']);
            
            // Add the uploaded logo URL
            $brandData['logo_url'] = $logoUrl ?: "images/brands/{$logoFilename}";

            Brand::create($brandData);
        }
    }
}