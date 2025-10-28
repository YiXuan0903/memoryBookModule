@extends('layouts.app')

@section('content')

@php
    $memoryData = App\Http\Controllers\MemoryController::getMemoryDataForMainDashboard();
    extract($memoryData);
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col  d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-0">📊 Main Dashboard</h1>
                <p class="text-muted">Welcome back, {{ Auth::user()->name }}! Here's your overview.</p>
            </div>
            <a href="{{ url('/memories') }}" class="btn btn-primary">
                <i class="bi bi-arrow-right-circle me-2"></i>Go to Memory Module
            </a>
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
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-globe fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-0">{{ $memoryStats['public'] }}</h4>
                            <small>Public Memories</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lock fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-0">{{ $memoryStats['private'] }}</h4>
                            <small>Private Memories</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-emoji-smile fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-0">{{ $memoryStats['topMood'] ?? 'N/A' }}</h4>
                            <small>Top Mood</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @if(isset($sentimentSummary) && isset($moodSummary))
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">😊 Sentiment Analysis</h5>
                </div>
                <div class="card-body">
                    <canvas id="sentimentChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">🎭 Mood Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="moodChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">🔒 Privacy Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="privacyChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">📈 Memory Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="overviewChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if(isset($sentimentSummary) && isset($moodSummary))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sentiment Pie Chart
    const sentimentCtx = document.getElementById('sentimentChart');
    if (sentimentCtx) {
        new Chart(sentimentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Neutral', 'Negative'],
                datasets: [{
                    data: [
                    //hi this is a comment remove this later
                        {{ $sentimentSummary['positive'] }},
                        {{ $sentimentSummary['neutral'] }},
                        {{ $sentimentSummary['negative'] }}
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    const moodCtx = document.getElementById('moodChart');
    if (moodCtx) {
        new Chart(moodCtx, {
            type: 'bar',
            data: {
                labels: ['Happy', 'Sad', 'Angry', 'Excited', 'Calm', 'Tired'],
                datasets: [{
                    label: 'Number of Memories',
                    data: [
                        {{ $moodSummary['happy'] ?? 0 }},
                        {{ $moodSummary['sad'] ?? 0 }},
                        {{ $moodSummary['angry'] ?? 0 }},
                        {{ $moodSummary['excited'] ?? 0 }},
                        {{ $moodSummary['calm'] ?? 0 }},
                        {{ $moodSummary['tired'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(0, 123, 255, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    const privacyCtx = document.getElementById('privacyChart');
    if (privacyCtx) {
        new Chart(privacyCtx, {
            type: 'pie',
            data: {
                labels: ['Public', 'Private'],
                datasets: [{
                    data: [
                        {{ $memoryStats['public'] ?? 0 }},
                        {{ $memoryStats['private'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    const overviewCtx = document.getElementById('overviewChart');
    if (overviewCtx) {
        new Chart(overviewCtx, {
            type: 'radar',
            data: {
                labels: ['Happy', 'Sad', 'Angry', 'Excited', 'Calm', 'Tired'],
                datasets: [{
                    label: 'Mood Distribution',
                    data: [
                        {{ $moodSummary['happy'] ?? 0 }},
                        {{ $moodSummary['sad'] ?? 0 }},
                        {{ $moodSummary['angry'] ?? 0 }},
                        {{ $moodSummary['excited'] ?? 0 }},
                        {{ $moodSummary['calm'] ?? 0 }},
                        {{ $moodSummary['tired'] ?? 0 }}
                    ],
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(13, 110, 253, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
@endif

@endsection