<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Jobs\ProcessVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Show the video creation form
     */
    public function index()
    {
        $videos = Video::orderBy('created_at', 'desc')->paginate(10);
        return view('videos.index', compact('videos'));
    }

    /**
     * Store a new video request
     */
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:500',
        ]);

        $video = Video::create([
            'text' => $request->text,
            'status' => Video::STATUS_PENDING,
            'progress' => 0,
        ]);

        // Dispatch the job
        ProcessVideoJob::dispatch($video);

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video generation started!');
    }

    /**
     * Show video details
     */
    public function show(Video $video)
    {
        return view('videos.show', compact('video'));
    }

    /**
     * Get video status (AJAX endpoint)
     */
    public function status(Video $video)
    {
        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
            'progress' => $video->progress,
            'error_message' => $video->error_message,
            'download_url' => $video->getSignedDownloadUrl(),
            'created_at' => $video->created_at->diffForHumans(),
        ]);
    }

    /**
     * Download video (signed URL)
     */
    public function download(Request $request, Video $video)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired download link');
        }

        if ($video->status !== Video::STATUS_COMPLETED || !$video->video_path) {
            abort(404, 'Video not found or not ready');
        }

        if (!Storage::exists($video->video_path)) {
            abort(404, 'Video file not found');
        }

        return Storage::download($video->video_path, 'video_' . $video->id . '.mp4');
    }
}