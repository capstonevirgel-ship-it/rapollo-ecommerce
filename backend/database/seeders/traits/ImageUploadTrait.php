<?php

namespace Database\Seeders\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

trait ImageUploadTrait
{
    /**
     * Upload image from source to storage and return the relative path
     *
     * @param string $sourcePath Source file path
     * @param string $destinationDirectory Destination directory in storage
     * @param string $filename Desired filename
     * @return string|null Relative path or null if failed
     */
    protected function uploadImage(string $sourcePath, string $destinationDirectory, string $filename): ?string
    {
        // Check if source file exists
        if (!File::exists($sourcePath)) {
            $this->command->warn("Source image not found: {$sourcePath}");
            return null;
        }

        // Create storage directory if it doesn't exist
        Storage::makeDirectory("public/{$destinationDirectory}");

        // Destination path in storage (relative path only, no /storage/ prefix)
        $relativePath = "{$destinationDirectory}/{$filename}";
        
        // Copy file to storage using File facade
        $destinationFullPath = storage_path("app/public/{$relativePath}");
        
        try {
            File::copy($sourcePath, $destinationFullPath);
            $this->command->info("Uploaded image: {$relativePath}");
            return $relativePath;
        } catch (\Exception $e) {
            $this->command->error("Failed to upload image {$sourcePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload multiple images from a directory
     *
     * @param string $sourceDirectory Source directory path
     * @param string $destinationDirectory Destination directory in storage
     * @param array $imageMappings Array of [source_filename => destination_filename]
     * @return array Array of uploaded relative paths
     */
    protected function uploadMultipleImages(string $sourceDirectory, string $destinationDirectory, array $imageMappings): array
    {
        $uploadedPaths = [];

        foreach ($imageMappings as $sourceFilename => $destinationFilename) {
            $sourcePath = base_path("{$sourceDirectory}/{$sourceFilename}");
            $uploadedPath = $this->uploadImage($sourcePath, $destinationDirectory, $destinationFilename);
            
            if ($uploadedPath) {
                $uploadedPaths[] = $uploadedPath;
            }
        }

        return $uploadedPaths;
    }

    /**
     * Get the base images directory path
     *
     * @return string
     */
    protected function getImagesBasePath(): string
    {
        return '../images';
    }

    /**
     * Upload brand logo
     *
     * @param string $brandSlug Brand slug for filename
     * @param string $imageFilename Source image filename
     * @return string|null
     */
    protected function uploadBrandLogo(string $brandSlug, string $imageFilename): ?string
    {
        $sourcePath = base_path("{$this->getImagesBasePath()}/brands/{$imageFilename}");
        $destinationFilename = "{$brandSlug}_logo." . pathinfo($imageFilename, PATHINFO_EXTENSION);
        
        return $this->uploadImage($sourcePath, 'brands', $destinationFilename);
    }

    /**
     * Upload event poster
     *
     * @param string $eventSlug Event slug for filename
     * @param string $imageFilename Source image filename
     * @return string|null
     */
    protected function uploadEventPoster(string $eventSlug, string $imageFilename): ?string
    {
        $sourcePath = base_path("{$this->getImagesBasePath()}/events/{$imageFilename}");
        $destinationFilename = "{$eventSlug}_poster." . pathinfo($imageFilename, PATHINFO_EXTENSION);
        
        return $this->uploadImage($sourcePath, 'events', $destinationFilename);
    }

    /**
     * Upload product images
     *
     * @param string $productSlug Product slug for filename
     * @param array $imageFilenames Array of source image filenames
     * @return array Array of uploaded relative paths
     */
    protected function uploadProductImages(string $productSlug, array $imageFilenames): array
    {
        $imageMappings = [];
        
        foreach ($imageFilenames as $imageFilename) {
            $destinationFilename = "{$productSlug}_{$imageFilename}";
            $imageMappings[$imageFilename] = $destinationFilename;
        }

        return $this->uploadMultipleImages("{$this->getImagesBasePath()}/products", 'products', $imageMappings);
    }
}
