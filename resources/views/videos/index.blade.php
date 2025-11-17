@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-4">Create New Video</h2>
        
        <form action="{{ route('videos.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="text" class="block text-gray-700 font-medium mb-2">
                    Enter Text for TTS Video
                </label>
                <textarea 
                    name="text" 
                    id="text" 
                    rows="4" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Enter your text here (max 500 characters)..."
                    maxlength="500"
                    required
                >{{ old('text') }}</textarea>
                
                @error('text')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                
                <p class="text-gray-500 text-sm mt-1">
                    Character count: <span id="char-count">0</span>/500
                </p>
            </div>

            <button 
                type="submit" 
                class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-medium"
            >
                Generate Video
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">Recent Videos</h2>
        
        @if($videos->count() > 0)
            <div class="space-y-4">
                @foreach($videos as $video)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-gray-800 mb-2">{{ Str::limit($video->text, 100) }}</p>
                                
                                <div class="flex items-center space-x-4 text-sm">
                                    <span class="flex items-center">
                                        @if($video->status === 'completed')
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span class="text-green-600 font-medium">Completed</span>
                                        @elseif($video->status === 'processing')
                                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span>
                                            <span class="text-blue-600 font-medium">Processing ({{ $video->progress }}%)</span>
                                        @elseif($video->status === 'failed')
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            <span class="text-red-600 font-medium">Failed</span>
                                        @else
                                            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                            <span class="text-yellow-600 font-medium">Pending</span>
                                        @endif
                                    </span>
                                    
                                    <span class="text-gray-500">
                                        {{ $video->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>

                            <a 
                                href="{{ route('videos.show', $video) }}" 
                                class="bg-indigo-100 text-indigo-600 px-4 py-2 rounded hover:bg-indigo-200 transition text-sm font-medium"
                            >
                                View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $videos->links() }}
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No videos yet. Create your first one above!</p>
        @endif
    </div>
</div>

<script>
    const textarea = document.getElementById('text');
    const charCount = document.getElementById('char-count');
    
    textarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
    // Initialize count
    charCount.textContent = textarea.value.length;
</script>
@endsection