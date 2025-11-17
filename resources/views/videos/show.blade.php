@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-start mb-6">
            <h2 class="text-2xl font-semibold">Video Details</h2>
            <a 
                href="{{ route('videos.index') }}" 
                class="text-indigo-600 hover:text-indigo-800"
            >
                ← Back to List
            </a>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Text Content</h3>
            <p class="text-gray-800 bg-gray-50 p-4 rounded border border-gray-200">
                {{ $video->text }}
            </p>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Status</h3>
            
            <div id="status-container">
                @if($video->status === 'completed')
                    <div class="flex items-center text-green-600 mb-4">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="font-medium">Video Completed!</span>
                    </div>
                @elseif($video->status === 'processing')
                    <div class="flex items-center text-blue-600 mb-4">
                        <svg class="animate-spin w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-medium">Processing...</span>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                        <div 
                            id="progress-bar"
                            class="bg-blue-600 h-4 rounded-full transition-all duration-500"
                            style="width: {{ $video->progress }}%"
                        ></div>
                    </div>
                    <p id="progress-text" class="text-sm text-gray-600">{{ $video->progress }}% complete</p>
                @elseif($video->status === 'failed')
                    <div class="flex items-center text-red-600 mb-4">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="font-medium">Failed</span>
                    </div>
                    @if($video->error_message)
                        <div class="bg-red-50 border border-red-200 rounded p-4">
                            <p class="text-red-700 text-sm">{{ $video->error_message }}</p>
                        </div>
                    @endif
                @else
                    <div class="flex items-center text-yellow-600">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">Pending - waiting to start...</span>
                    </div>
                @endif
            </div>
        </div>

        @if($video->status === 'completed' && $video->getSignedDownloadUrl())
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Download</h3>
                <a 
                    href="{{ $video->getSignedDownloadUrl() }}" 
                    class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-medium"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download Video
                </a>
                <p class="text-sm text-gray-500 mt-2">
                    File size: {{ number_format($video->getVideoSize() / 1024 / 1024, 2) }} MB
                </p>
            </div>
        @endif

        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-gray-800 font-medium">{{ $video->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Updated</dt>
                    <dd class="text-gray-800 font-medium">{{ $video->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@if(in_array($video->status, ['pending', 'processing']))
<script>
    // Poll for status updates
    function checkStatus() {
        fetch('{{ route('videos.status', $video) }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'processing') {
                    // Update progress bar
                    const progressBar = document.getElementById('progress-bar');
                    const progressText = document.getElementById('progress-text');
                    
                    if (progressBar && progressText) {
                        progressBar.style.width = data.progress + '%';
                        progressText.textContent = data.progress + '% complete';
                    }
                } else if (data.status === 'completed' || data.status === 'failed') {
                    // Reload page to show final state
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
            });
    }

    // Check every 3 seconds
    const statusInterval = setInterval(checkStatus, 3000);

    // Clear interval when page is unloaded
    window.addEventListener('beforeunload', () => {
        clearInterval(statusInterval);
    });
</script>
@endif
@endsection