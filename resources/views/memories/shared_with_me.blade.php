@extends('layouts.app')
@section('title','Shared With Me')

@section('content')
<h2 class="mb-4">📂 Memories Shared With Me</h2>

@if($memories->isEmpty())
  <div class="alert alert-info">No memories have been shared with you yet.</div>
@else
  <div class="row row-cols-1 row-cols-md-3 g-4">
    @foreach($memories as $memory)
      <div class="col">
        <div class="card h-100 shadow-sm">
          {{-- Media Preview --}}
          @if($memory->file_path)
            @php 
              $ext = strtolower(pathinfo($memory->file_path, PATHINFO_EXTENSION)); 
            @endphp

            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
              <img src="{{ asset('storage/'.$memory->file_path) }}" class="card-img-top" alt="Memory Image">
            @elseif(in_array($ext, ['mp4','avi','mov','webm']))
              <video class="w-100" style="max-height:200px;" controls>
                <source src="{{ asset('storage/'.$memory->file_path) }}">
              </video>
            @elseif(in_array($ext, ['mp3','wav','ogg']))
              <audio class="w-100" controls>
                <source src="{{ asset('storage/'.$memory->file_path) }}">
              </audio>
            @endif
          @endif

          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ $memory->title }}</h5>
            <p class="card-text">{{ Str::limit($memory->content, 80) }}</p>
            <p class="text-muted small mt-auto">
              Shared by: {{ $memory->user->name ?? 'Unknown' }} 
              ({{ $memory->created_at?->format('Y-m-d') ?? '' }})
            </p>
            {{-- Fix: Use web route with referrer parameter --}}
            <a href="{{ url('/memories/' . $memory->id . '?ref=shared') }}" class="btn btn-primary btn-sm mt-2">View</a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif
@endsection