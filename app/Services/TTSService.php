<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TTSService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_tts.api_key');
    }

    /**
     * Generate TTS audio from text using Google Cloud TTS
     */
    public function generateAudio(string $text): string
    {
        $response = Http::post("https://texttospeech.googleapis.com/v1/text:synthesize?key={$this->apiKey}", [
            'input' => [
                'text' => $text,
            ],
            'voice' => [
                'languageCode' => 'en-US',
                'name' => 'en-US-Neural2-J',
                'ssmlGender' => 'NEUTRAL',
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'speakingRate' => 1.0,
                'pitch' => 0.0,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to generate TTS audio: ' . $response->body());
        }

        $audioContent = base64_decode($response->json()['audioContent']);
        
        $filename = 'audio/' . uniqid() . '.mp3';
        Storage::put($filename, $audioContent);

        return $filename;
    }

    /**
     * Get audio duration in seconds using ffprobe
     */
    public function getAudioDuration(string $audioPath): float
    {
        $fullPath = Storage::path($audioPath);
        $ffprobe = config('services.ffmpeg.ffprobe_path', '/usr/bin/ffprobe');
        
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellcmd($ffprobe),
            escapeshellarg($fullPath)
        );

        $duration = shell_exec($command);
        
        return (float) trim($duration);
    }
}