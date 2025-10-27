@extends('layouts.app')
@section('title',$memory->title)

@section('content')

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <ul class="mb-0">
          @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
          @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- Smart Back Button --}}
<div class="mb-3">
    @if(request('ref') === 'shared')
        <a href="{{ url('/memories?tab=shared') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Shared With Me
        </a>
    @else
        <a href="{{ url('/memories') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Memories
        </a>
    @endif
</div>

<div class="card">
  <div class="card-body">

    {{-- Template layout --}}
    @if($memory->template === 'daily')
        <div class="template-daily">
            <h3>{{ $memory->title }}</h3>
            <p class="text-muted">{{ $memory->created_at->format('Y-m-d H:i') }}</p>
            <p>{{ $memory->content }}</p>
        </div>
    @elseif($memory->template === 'travel')
        <div class="template-travel">
            <h3>✈️ {{ $memory->title }}</h3>
            <p class="text-muted">{{ $memory->created_at->format('d M Y') }}</p>
            <p>{{ $memory->content }}</p>
            @if($memory->file_path)
                <img src="{{ asset('storage/'.$memory->file_path) }}" 
                     class="img-fluid rounded mt-2" 
                     style="max-height:250px; object-fit:cover;">
            @endif
        </div>
    @elseif($memory->template === 'gratitude')
        <div class="template-gratitude">
            <h4>🙏 Gratitude Log</h4>
            <ul>
                @foreach(explode("\n", $memory->content) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @elseif($memory->template === 'study')
        <div class="template-study">
            <h3>📚 {{ $memory->title }}</h3>
            <pre>{{ $memory->content }}</pre>
            <p class="text-muted"><strong>Tags:</strong> {{ $memory->tags ?? 'General' }}</p>
        </div>
    @else
        <h3>{{ $memory->title }}</h3>
        <p class="text-muted">{{ $memory->created_at->format('Y-m-d H:i') }}</p>
        <p>{{ $memory->content }}</p>
    @endif

    {{-- File preview --}}
    @if($memory->file_path)
        @php
            $filePath = storage_path('app/public/'.$memory->file_path);
            $ext = strtolower(pathinfo($memory->file_path, PATHINFO_EXTENSION));
            $imageExts = ['jpg','jpeg','png','gif','webp'];
            $videoExts = ['mp4','avi','mov','mkv','webm'];
            $audioExts = ['mp3','wav','ogg','m4a'];
        @endphp

        @if(file_exists($filePath))
            @if(in_array($ext, $imageExts))
                <img src="{{ asset('storage/'.$memory->file_path) }}" class="img-fluid mb-3" style="max-width:80%; height:auto;">
            @elseif(in_array($ext, $videoExts))
                <video controls class="w-100 mb-3">
                    <source src="{{ asset('storage/'.$memory->file_path) }}" type="video/{{ $ext }}">
                </video>
            @elseif(in_array($ext, $audioExts))
                <audio controls class="w-100 mb-3">
                    <source src="{{ asset('storage/'.$memory->file_path) }}" type="audio/{{ $ext }}">
                </audio>
            @else
                <p class="text-muted">File type not supported for preview.</p>
            @endif
        @else
            <p class="text-danger">File not found.</p>
        @endif
    @endif

    {{-- Extra info --}}
    <p><strong>Mood:</strong> {{ $memory->mood ?? 'N/A' }}</p>
    <p><strong>Tags:</strong> {{ $memory->tags ?? 'N/A' }}</p>
    <p><strong>Sentiment:</strong> {{ ucfirst($memory->sentiment) }}</p>
    <p><strong>Privacy:</strong> {{ $memory->is_public ? 'Public' : 'Private' }}</p>
    <p><strong>Template:</strong> {{ ucfirst($memory->template ?? 'N/A') }}</p>

    {{-- Show who shared this memory if it's a shared memory --}}
    @if(isset($memory->user) && Auth::check() && Auth::id() !== $memory->user_id)
        <div class="alert alert-info">
            <i class="bi bi-share"></i> This memory was shared with you by <strong>{{ $memory->user->name }}</strong>
        </div>
    @endif

    {{-- Share controls - only show if user owns the memory --}}
    @if(Auth::check() && Auth::id() === $memory->user_id)
        <div class="mb-3">
            @if($memory->share_token)
                <div class="mb-2 d-flex align-items-center gap-2">
                    <input type="text" id="share-link-detail-{{ $memory->id }}"
                       value="{{ url('/memories/' . $memory->id . '?shared_token=' . $memory->share_token) }}"
                       class="form-control form-control-sm" readonly style="max-width: 400px;">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        onclick="copyShareLinkFixed('share-link-detail-{{ $memory->id }}', this)">
                        📋 Copy Link
                    </button>
                </div>
            @else
                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#shareModal-{{ $memory->id }}">
                    Share
                </button>
            @endif
        </div>
    @endif
      {{-- Only show edit/delete actions if user owns the memory --}}
      @if(Auth::check() && Auth::id() === $memory->user_id)
          <a href="{{ route('memories.create') }}" class="btn btn-success">Add Memory</a>
          <a href="{{ route('memories.edit',$memory) }}" class="btn btn-warning">Edit</a>

          <form action="/memories/{{ $memory->id }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this memory?')">
              Delete
            </button>
          </form>
      @endif
    </div>
  </div>
</div>

{{-- Only include share modal if user owns the memory --}}
@if(Auth::check() && Auth::id() === $memory->user_id)
    {{-- Share Modal --}}
    @include('memories.partials.share-modal',['memory'=>$memory])
@endif

{{-- Copy link script --}}
<script>
function copyShareLinkFixed(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const link = input.value;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(() => {
            showCopySuccess(button);
        }).catch(() => {
            fallbackCopy(input, button);
        });
    } else {
        fallbackCopy(input, button);
    }
}

function fallbackCopy(input, button) {
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(button);
        } else {
            prompt('Please copy manually:', input.value);
        }
    } catch (err) {
        prompt('Please copy manually:', input.value);
    }
}

function showCopySuccess(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '✅ Copied!';
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-primary');
    }, 2000);
}

function showManualPrompt(link) {
    prompt('Please copy this link manually:', link);
}
</script>
@endsection