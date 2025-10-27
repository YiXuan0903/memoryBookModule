@foreach($memories as $memory)
  <div class="col">
    <div class="card h-100 shadow-sm">
        @if($memory->file_path)
            @php $ext = strtolower(pathinfo($memory->file_path, PATHINFO_EXTENSION)); @endphp
            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
              <img src="{{ asset('storage/'.$memory->file_path) }}" class="memory-img card-img-top" alt="memory image">
            @elseif(in_array($ext, ['mp4','avi','mov']))
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

            {{-- 🎨 Template-based preview --}}
            @if($memory->template === 'daily')
                <div class="template-daily">
                    <h5 class="card-title">📝 {{ Str::limit($memory->title, 30) }}</h5>
                    <p class="card-text">{{ Str::limit($memory->content, 80) }}</p>
                </div>
            @elseif($memory->template === 'travel')
                <div class="template-travel">
                    <h5 class="card-title">✈️ {{ Str::limit($memory->title, 30) }}</h5>
                    <p class="card-text">{{ Str::limit($memory->content, 60) }}</p>
                    <p class="text-muted small">{{ $memory->created_at->format('d M Y') }}</p>
                </div>
            @elseif($memory->template === 'gratitude')
                <div class="template-gratitude">
                    <h5 class="card-title">🙏 Gratitude</h5>
                    <ul class="mb-0">
                        @foreach(array_slice(explode("\n", $memory->content), 0, 3) as $line)
                            <li>{{ Str::limit($line, 40) }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif($memory->template === 'study')
                <div class="template-study">
                    <h5 class="card-title">📚 {{ Str::limit($memory->title, 30) }}</h5>
                    <p class="card-text"><strong>Tags:</strong> {{ $memory->tags ?? 'General' }}</p>
                    <p class="card-text">{{ Str::limit($memory->content, 60) }}</p>
                </div>
            @else
                {{-- Default fallback --}}
                <h5 class="card-title">{{ Str::limit($memory->title, 30) }}</h5>
                <p class="card-text">{{ Str::limit($memory->content, 80) }}</p>
            @endif

            <p class="text-muted mt-auto" style="font-size:0.8rem;">{{ $memory->created_at->format('Y-m-d') }}</p>

            <button type="button" class="btn btn-primary btn-sm" 
                data-bs-toggle="modal" data-bs-target="#shareMemoryModal-{{ $memory->id }}">
              <i class="bi bi-share"></i> Share Memory
            </button>

            {{-- Include modal partial --}}
            @include('memories.partials.share-modal',['memory'=>$memory])

            {{-- Actions --}}
            <div class="d-flex gap-1 flex-wrap">
              <a href="/memories/{{ $memory->id }}" class="btn btn-sm btn-primary">View</a>
              <a href="/memories/{{ $memory->id }}/edit" class="btn btn-sm btn-warning">Edit</a>
              <form action="/memories/{{ $memory->id }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this memory?')">Delete</button>
              </form>
            </div>
        </div>
    </div>
  </div>
@endforeach
