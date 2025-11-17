<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\TTSService;
use App\Services\ImageService;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(TTSService $ttsService, ImageService $imageService, VideoService $videoService)
    {
        try {
            // Update status
            $this->video->update([
                'status' => Video::STATUS_PROCESSING,
                'progress' => 0,
            ]);

            // Step 1: Generate TTS audio (20% progress)
            Log::info('Generating TTS audio for video ' . $this->video->id);
            $audioPath = $ttsService->generateAudio($this->video->text);
            $this->video->update([
                'audio_path' => $audioPath,
                'progress' => 20,
            ]);

            // Step 2: Fetch stock images (40% progress)
            Log::info('Fetching images for video ' . $this->video->id);
            $imagePaths = $imageService->fetchImages($this->video->text);
            $this->video->update(['progress' => 40]);

            // Step 3: Resize images (60% progress)
            Log::info('Resizing images for video ' . $this->video->id);
            $resizedPaths = [];
            foreach ($imagePaths as $imagePath) {
                $resizedPaths[] = $imageService->resizeImage($imagePath);
            }
            $this->video->update(['progress' => 60]);

            // Step 4: Generate subtitles (70% progress)
            Log::info('Generating subtitles for video ' . $this->video->id);
            $subtitlePath = $videoService->generateSubtitles($this->video->text);
            $this->video->update(['progress' => 70]);

            // Step 5: Generate video with FFmpeg (100% progress)
            Log::info('Generating final video for video ' . $this->video->id);
            $videoPath = $videoService->generateVideo(
                $resizedPaths,
                $audioPath,
                $subtitlePath,
                $this->video->text
            );

            $this->video->update([
                'video_path' => $videoPath,
                'status' => Video::STATUS_COMPLETED,
                'progress' => 100,
            ]);

            Log::info('Video generation completed for video ' . $this->video->id);
        } catch (\Exception $e) {
            Log::error('Video generation failed for video ' . $this->video->id . ': ' . $e->getMessage());
            
            $this->video->update([
                'status' => Video::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}