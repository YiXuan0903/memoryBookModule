<div class="template-daily">
  <h4>📝 Daily Journal</h4>
  <p>{{ $memory->content }}</p>

  @if($memory->file_path)
    @php $ext = strtolower(pathinfo($memory->file_path, PATHINFO_EXTENSION)); @endphp

    <div class="mt-2">
      @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
        <img src="{{ asset('storage/'.$memory->file_path) }}" class="img-fluid rounded shadow" alt="daily image">
      @elseif(in_array($ext, ['mp4','avi','mov']))
        <video class="w-100 rounded shadow" style="max-height:300px;" controls>
          <source src="{{ asset('storage/'.$memory->file_path) }}">
        </video>
      @elseif(in_array($ext, ['mp3','wav','ogg']))
        <audio class="w-100" controls>
          <source src="{{ asset('storage/'.$memory->file_path) }}">
        </audio>
      @endif
    </div>
  @endif
</div>
