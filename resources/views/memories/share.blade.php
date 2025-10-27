<div class="modal fade" id="shareModal-{{ $memory->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content theme-card">
      <div class="modal-header">
        <h5 class="modal-title">Share Memory: {{ $memory->title }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      {{-- Display existing share link if memory already has one --}}
      @if($memory->share_token)
      <div class="alert alert-info mx-3 mt-3">
        <h6>Existing Public Share Link:</h6>
        <div class="input-group">
          <input type="text" class="form-control" value="/memories/{{ $memory->id }}?shared_token={{ $memory->share_token }}" readonly id="existingShareLink-{{ $memory->id }}">
          <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink('existing', {{ $memory->id }})">
            <i class="bi bi-clipboard"></i> Copy
          </button>
        </div>
        <small class="text-muted">This memory already has a public share link</small>
      </div>
      @endif

      {{-- Display newly generated share link if it exists in session --}}
      @if(session('share_link'))
      <div class="alert alert-success mx-3 mt-3" id="newShareLinkAlert-{{ $memory->id }}">
        <h6>New Public Share Link Generated:</h6>
        <div class="input-group">
          <input type="text" class="form-control" value="{{ session('share_link') }}" readonly id="newShareLink-{{ $memory->id }}">
          <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink('new', {{ $memory->id }})">
            <i class="bi bi-clipboard"></i> Copy
          </button>
        </div>
        <small class="text-muted">Anyone with this link can view your memory</small>
      </div>
      @endif

      <form method="POST" action="/memories/{{ $memory->id }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="share">
        
        <div class="modal-body">
          <p>Select how you want to share this memory:</p>

          {{-- Public Option --}}
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="public" value="1" id="public-{{ $memory->id }}">
            <label class="form-check-label" for="public-{{ $memory->id }}">
              <i class="bi bi-link-45deg"></i> Generate {{ $memory->share_token ? 'New' : '' }} Public Link
            </label>
            <small class="d-block text-muted">
              @if($memory->share_token)
                Generate a new public link (the old one will still work)
              @else
                Anyone with the link can view this memory
              @endif
            </small>
          </div>

          <hr>

          {{-- Friends Option --}}
          <div class="mb-3">
            <label class="form-label">
              <i class="bi bi-people"></i> Share with Specific Friends
            </label>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
              @php
                $friends = \App\Models\Friend::where('user_id', Auth::id())->with('friendUser')->get();
              @endphp
              
              @forelse($friends as $friend)
                @if($friend->friendUser)
                  @php
                    $isAlreadyShared = $memory->sharedUsers()->where('users.id', $friend->friendUser->id)->exists();
                  @endphp
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="friend_ids[]" 
                           value="{{ $friend->id }}" id="friend-{{ $memory->id }}-{{ $friend->id }}"
                           {{ $isAlreadyShared ? 'disabled' : '' }}>
                    <label class="form-check-label" for="friend-{{ $memory->id }}-{{ $friend->id }}">
                      {{ $friend->friendUser->name }} 
                      <small class="text-muted">({{ $friend->category ?? 'Uncategorized' }})</small>
                      @if($isAlreadyShared)
                        <span class="badge bg-success ms-2">Already Shared</span>
                      @endif
                    </label>
                  </div>
                @endif
              @empty
                <p class="text-muted mb-0">No friends available. <a href="/memories?tab=friends">Add friends first</a>.</p>
              @endforelse
            </div>
          </div>

          <hr>

          {{-- Category Option --}}
          @if($friends->whereNotNull('category')->count() > 0)
            <div class="mb-3">
              <label class="form-label">
                <i class="bi bi-tags"></i> Or Share with Entire Category
              </label>
              <select name="category" class="form-select">
                <option value="">-- Select Category --</option>
                @foreach($friends->pluck('category')->unique()->filter() as $cat)
                  <option value="{{ $cat }}">
                    {{ ucfirst($cat) }} ({{ $friends->where('category', $cat)->count() }} friends)
                  </option>
                @endforeach
              </select>
            </div>
          @endif
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-share"></i> Share Memory
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Enhanced JavaScript for copy functionality --}}
<script>
function copyShareLink(type, memoryId) {
    let linkInput;
    if (type === 'existing') {
        linkInput = document.getElementById('existingShareLink-' + memoryId);
    } else {
        linkInput = document.getElementById('newShareLink-' + memoryId);
    }
    
    if (!linkInput) return;
    
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    try {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(linkInput.value).then(() => {
                showCopySuccess(event.target.closest('button'));
            }).catch(() => {
                fallbackCopy(linkInput, event.target.closest('button'));
            });
        } else {
            fallbackCopy(linkInput, event.target.closest('button'));
        }
    } catch (err) {
        console.error('Copy failed:', err);
        alert('Failed to copy link. Please copy manually.');
    }
}

function fallbackCopy(linkInput, copyBtn) {
    try {
        document.execCommand('copy');
        showCopySuccess(copyBtn);
    } catch (err) {
        alert('Failed to copy link. Please copy manually.');
    }
}

function showCopySuccess(copyBtn) {
    const originalHTML = copyBtn.innerHTML;
    copyBtn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    copyBtn.classList.add('btn-success');
    copyBtn.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        copyBtn.innerHTML = originalHTML;
        copyBtn.classList.remove('btn-success');
        copyBtn.classList.add('btn-outline-secondary');
    }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    @if(session('share_link'))
        @if(session('shared_memory_id'))
            const modal = document.getElementById('shareModal-{{ session("shared_memory_id") }}');
            if (modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            }
        @endif
    @endif
});
</script>
