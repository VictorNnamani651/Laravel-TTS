<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'status',
        'progress',
        'audio_path',
        'video_path',
        'error_message',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function getSignedDownloadUrl()
    {
        if ($this->status !== self::STATUS_COMPLETED || !$this->video_path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'video.download',
            now()->addHours(24),
            ['video' => $this->id]
        );
    }

    public function getVideoSize()
    {
        if ($this->video_path && Storage::exists($this->video_path)) {
            return Storage::size($this->video_path);
        }
        return 0;
    }
}