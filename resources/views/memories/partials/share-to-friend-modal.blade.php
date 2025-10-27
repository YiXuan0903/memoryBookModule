{{-- Quick Share to Friend Modal --}}
<div class="modal fade" id="shareToFriendModal-{{ $friend->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content theme-card">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-share"></i>
          Share Memory with {{ optional($friend->friendUser)->name ?? 'Friend' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="{{ route('memories.update', 1) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="shareToFriend">
        <input type="hidden" name="friend_ids[]" value="{{ $friend->id }}">

        <div class="modal-body">
          <p>Select which memory you want to share with this friend:</p>
          
          @php
            $userMemories = \App\Models\Memory::where('user_id', Auth::id())
                                            ->orderBy('created_at', 'desc')
                                            ->get();
          @endphp
          
          @if($userMemories->count() > 0)
            <select name="memory_id" class="form-select" required>
              <option value="">-- Select a Memory --</option>
              @foreach($userMemories as $memory)
                <option value="{{ $memory->id }}">
                  {{ $memory->title }} 
                  <small>({{ $memory->created_at->format('M d, Y') }})</small>
                </option>
              @endforeach
            </select>
          @else
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i>
              You don't have any memories to share yet. 
              <a href="/memories/create">Create your first memory</a>.
            </div>
          @endif
        </div>

        <div class="modal-footer">
          @if($userMemories->count() > 0)
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-share"></i> Share Memory
            </button>
          @endif
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>