@extends('layouts.app')

@section('content')

@php
    $memoryData = App\Http\Controllers\MemoryController::getMemoryDataForMainDashboard();
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-0">📊 Main Dashboard</h1>
            <p class="text-muted">Welcome back, {{ Auth::user()->name }}! Here's your overview.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-journal-text fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-0">{{ $totalMemories ?? 0 }}</h4>
                            <small>Total Memories</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @if(isset($memoryStats))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📖 Memory Module Overview</h5>
                    <a href="{{ url('/memories') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle me-2"></i>Go to Memory Module
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <h3 class="text-primary">{{ $memoryStats['total'] }}</h3>
                                <p class="mb-0">Total Memories</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <h3 class="text-success">{{ $memoryStats['public'] }}</h3>
                                <p class="mb-0">Public</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <h3 class="text-warning">{{ $memoryStats['private'] }}</h3>
                                <p class="mb-0">Private</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <h3 class="text-info">{{ $memoryStats['topMood'] ?? 'N/A' }}</h3>
                                <p class="mb-0">Top Mood</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">😊 Sentiment Analysis</h6>
                </div>
                <div class="card-body">
                    @if(isset($sentimentSummary))
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="p-2">
                                    <h4 class="text-success">{{ $sentimentSummary['positive'] }}</h4>
                                    <small>Positive</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2">
                                    <h4 class="text-secondary">{{ $sentimentSummary['neutral'] }}</h4>
                                    <small>Neutral</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2">
                                    <h4 class="text-danger">{{ $sentimentSummary['negative'] }}</h4>
                                    <small>Negative</small>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🎭 Mood Distribution</h6>
                </div>
                <div class="card-body">
                    @if(isset($moodSummary))
                        <div class="row">
                            @foreach($moodSummary as $mood => $count)
                                <div class="col-6 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-capitalize">{{ $mood }}</span>
                                        <span class="badge bg-secondary">{{ $count }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection