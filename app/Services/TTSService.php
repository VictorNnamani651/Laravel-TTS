<?php

namespace App\Services;

use Aws\Polly\PollyClient;
use Illuminate\Support\Facades\Storage;

class TTSService
{
    protected $pollyClient;

    public function __construct()
    {
        $this->pollyClient = new PollyClient([
            'version' => 'latest',
            'region' => config('services.aws.region'),
            'credentials' => [
                'key' => config('services.aws.access_key_id'),
                'secret' => config('services.aws.secret_access_key'),
            ],
        ]);
    }

    /**
     * Generate TTS audio from text using Amazon Polly
     */
    public function generateAudio(string $text): string
    {
        try {
            $result = $this->pollyClient->synthesizeSpeech([
                'Text' => $text,
                'OutputFormat' => 'mp3',
                'VoiceId' => 'Joanna', // Female US English voice
                'Engine' => 'neural', // Use neural engine for better quality
                'TextType' => 'text',
            ]);

            // Get the audio stream
            $audioStream = $result->get('AudioStream')->getContents();
            
            $filename = 'audio/' . uniqid() . '.mp3';
            Storage::put($filename, $audioStream);

            return $filename;
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate TTS audio with Polly: ' . $e->getMessage());
        }
    }

    /**
     * Get audio duration in seconds using ffprobe
     */
    public function getAudioDuration(string $audioPath): float
    {
        $fullPath = Storage::path($audioPath);
        $ffprobe = config('services.ffmpeg.ffprobe_path', 'ffprobe');
        
        $command = sprintf(
            '"%s" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
            $ffprobe,
            $fullPath
        );

        $duration = shell_exec($command);
        
        return (float) trim($duration);
    }
}