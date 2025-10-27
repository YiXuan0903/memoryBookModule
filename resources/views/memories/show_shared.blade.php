<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $memory->title }} - Shared Memory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .shared-container {
            padding: 2rem 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .memory-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            max-width: 800px;
            width: 100%;
        }
        .memory-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .memory-content {
            padding: 2rem;
        }
        .memory-img { 
            object-fit: cover; 
            width: 100%; 
            max-height: 400px; 
            border-radius: 10px; 
        }
        .template-daily { border-left: 4px solid #0d6efd; padding-left: 1rem; }
        .template-travel { background: #f0f8ff; padding: 1rem; border-radius: 10px; }
        .template-gratitude ul { list-style: "💡 "; }
        .template-study pre { font-family: monospace; white-space: pre-wrap; background: #f8f9fa; padding: 1rem; border-radius: 5px; }
        .badge-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="shared-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="memory-card">
                        {{-- Header --}}
                        <div class="memory-header">
                            <h1 class="mb-3">
                                <i class="bi bi-share"></i> 
                                {{ $memory->title }}
                            </h1>
                            <p class="mb-0 opacity-75">
                                <i class="bi bi-person-circle"></i> 
                                Shared by {{ $memory->user->name ?? 'Anonymous' }}
                            </p>
                            <small class="opacity-50">
                                <i class="bi bi-calendar"></i> 
                                {{ $memory->created_at->format('F j, Y \a\t g:i A') }}
                            </small>
                        </div>

                        {{-- Content --}}
                        <div class="memory-content">
                            {{-- File preview --}}
                            @if($memory->file_path)
                                @php
                                    $filePath = storage_path('app/public/'.$memory->file_path);
                                    $ext = strtolower(pathinfo($memory->file_path, PATHINFO_EXTENSION));
                                    $imageExts = ['jpg','jpeg','png','gif','webp'];
                                    $videoExts = ['mp4','avi','mov','mkv','webm'];
                                    $audioExts = ['mp3','wav','ogg','m4a'];
                                @endphp

                                <div class="text-center mb-4">
                                    @if(file_exists($filePath))
                                        @if(in_array($ext, $imageExts))
                                            <img src="{{ asset('storage/'.$memory->file_path) }}" class="memory-img" alt="Memory image">
                                        @elseif(in_array($ext, $videoExts))
                                            <video controls class="memory-img">
                                                <source src="{{ asset('storage/'.$memory->file_path) }}" type="video/{{ $ext }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @elseif(in_array($ext, $audioExts))
                                            <audio controls class="w-100">
                                                <source src="{{ asset('storage/'.$memory->file_path) }}" type="audio/{{ $ext }}">
                                                Your browser does not support the audio tag.
                                            </audio>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="bi bi-file-earmark"></i> File attachment: {{ basename($memory->file_path) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> File not found
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Template-based content --}}
                            @if($memory->template === 'daily')
                                <div class="template-daily">
                                    <h3><i class="bi bi-journal-text"></i> Daily Journal</h3>
                                    <p class="text-muted">{{ $memory->created_at->format('l, F j, Y') }}</p>
                                    <div class="mt-3">{{ $memory->content }}</div>
                                </div>
                            @elseif($memory->template === 'travel')
                                <div class="template-travel">
                                    <h3><i class="bi bi-airplane"></i> Travel Memory</h3>
                                    <p class="text-muted">{{ $memory->created_at->format('F j, Y') }}</p>
                                    <div class="mt-3">{{ $memory->content }}</div>
                                </div>
                            @elseif($memory->template === 'gratitude')
                                <div class="template-gratitude">
                                    <h4><i class="bi bi-heart"></i> Gratitude Log</h4>
                                    <ul class="mt-3">
                                        @foreach(explode("\n", $memory->content) as $line)
                                            @if(trim($line))
                                                <li>{{ trim($line) }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif($memory->template === 'study')
                                <div class="template-study">
                                    <h3><i class="bi bi-book"></i> Study Notes</h3>
                                    <pre>{{ $memory->content }}</pre>
                                    @if($memory->tags)
                                        <p class="mt-3"><strong>Tags:</strong> {{ $memory->tags }}</p>
                                    @endif
                                </div>
                            @else
                                {{-- Default template --}}
                                <div class="mb-4">
                                    <p style="font-size: 1.1rem; line-height: 1.6;">{{ $memory->content }}</p>
                                </div>
                            @endif

                            {{-- Memory metadata --}}
                            <div class="row mt-4 pt-4 border-top">
                                @if($memory->mood)
                                    <div class="col-md-3 mb-2">
                                        <small class="text-muted d-block">Mood</small>
                                        <span class="badge-custom">{{ ucfirst($memory->mood) }}</span>
                                    </div>
                                @endif
                                
                                @if($memory->tags)
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted d-block">Tags</small>
                                        @foreach(explode(',', $memory->tags) as $tag)
                                            <span class="badge bg-light text-dark me-1">#{{ trim($tag) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                
                                @if($memory->sentiment)
                                    <div class="col-md-3 mb-2">
                                        <small class="text-muted d-block">Sentiment</small>
                                        @php
                                            $sentimentClass = $memory->sentiment === 'positive' ? 'success' : 
                                                            ($memory->sentiment === 'negative' ? 'danger' : 'secondary');
                                        @endphp
                                        <span class="badge bg-{{ $sentimentClass }}">{{ ucfirst($memory->sentiment) }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="text-center mt-4 pt-4 border-top">
                                <p class="text-muted mb-2">
                                    <i class="bi bi-info-circle"></i> 
                                    This memory was shared with you via a public link
                                </p>
                                <a href="/" class="btn btn-outline-primary">
                                    <i class="bi bi-house"></i> Visit Memory Book
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>