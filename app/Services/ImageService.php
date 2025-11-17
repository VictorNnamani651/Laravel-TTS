<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.pexels.api_key');
    }

    /**
     * Fetch 3 stock images from Pexels based on text query
     */
    public function fetchImages(string $query): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])->get('https://api.pexels.com/v1/search', [
            'query' => $query,
            'per_page' => 3,
            'orientation' => 'portrait', // For 1080x1920 video
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch images: ' . $response->body());
        }

        $photos = $response->json()['photos'] ?? [];
        
        if (empty($photos)) {
            throw new \Exception('No images found for query: ' . $query);
        }

        $imagePaths = [];
        
        foreach (array_slice($photos, 0, 3) as $index => $photo) {
            // Download portrait version (suitable for vertical video)
            $imageUrl = $photo['src']['portrait'];
            $imageContent = Http::get($imageUrl)->body();
            
            $filename = 'images/' . uniqid() . "_{$index}.jpg";
            Storage::put($filename, $imageContent);
            
            $imagePaths[] = $filename;
        }

        return $imagePaths;
    }

    /**
     * Resize image to 1080x1920 (portrait)
     */
    public function resizeImage(string $imagePath): string
    {
        $fullPath = Storage::path($imagePath);
        $outputPath = 'images/' . uniqid() . '_resized.jpg';
        $fullOutputPath = Storage::path($outputPath);

        // Use ImageMagick via shell for better quality
        $command = sprintf(
            'convert %s -resize 1080x1920^ -gravity center -extent 1080x1920 %s',
            escapeshellarg($fullPath),
            escapeshellarg($fullOutputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to resize image');
        }

        return $outputPath;
    }
}