<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    protected $accessKey;

    public function __construct()
    {
        $this->accessKey = config('services.unsplash.access_key');
    }

    /**
     * Fetch 3 stock images from Unsplash based on text query
     */
    public function fetchImages(string $query): array
    {
        $response = Http::get('https://api.unsplash.com/search/photos', [
            'query' => $query,
            'per_page' => 3,
            'orientation' => 'portrait', // For 1080x1920 video
            'client_id' => $this->accessKey,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch images from Unsplash: ' . $response->body());
        }

        $results = $response->json()['results'] ?? [];
        
        if (empty($results)) {
            // If no results, try a generic search
            $response = Http::get('https://api.unsplash.com/search/photos', [
                'query' => 'nature landscape',
                'per_page' => 3,
                'orientation' => 'portrait',
                'client_id' => $this->accessKey,
            ]);
            
            $results = $response->json()['results'] ?? [];
        }

        if (empty($results)) {
            throw new \Exception('No images found on Unsplash for query: ' . $query);
        }

        $imagePaths = [];
        
        foreach (array_slice($results, 0, 3) as $index => $photo) {
            // Download the regular size image (good quality, not too large)
            $imageUrl = $photo['urls']['regular'];
            
            // Trigger download tracking (required by Unsplash API terms)
            if (isset($photo['links']['download_location'])) {
                Http::get($photo['links']['download_location'], [
                    'client_id' => $this->accessKey,
                ]);
            }
            
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

        // Get FFmpeg path from config
        $ffmpegPath = config('services.ffmpeg.ffmpeg_path', 'ffmpeg');

        // Use FFmpeg for resizing (more reliable than ImageMagick on Windows)
        $command = sprintf(
            '"%s" -i "%s" -vf "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920" -q:v 2 "%s" 2>&1',
            $ffmpegPath,
            $fullPath,
            $fullOutputPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($fullOutputPath)) {
            // Fallback: if FFmpeg fails, try ImageMagick
            $convertCommand = sprintf(
                'convert "%s" -resize 1080x1920^ -gravity center -extent 1080x1920 "%s"',
                $fullPath,
                $fullOutputPath
            );

            exec($convertCommand, $convertOutput, $convertReturnCode);

            if ($convertReturnCode !== 0 || !file_exists($fullOutputPath)) {
                // If both fail, just copy the original
                copy($fullPath, $fullOutputPath);
            }
        }

        return $outputPath;
    }
}