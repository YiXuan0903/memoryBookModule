{{-- Enhanced Share Memory Modal --}}
<div class="modal fade" id="shareMemoryModal-{{ $memory->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content theme-card">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-share"></i> Share Memory: {{ $memory->title }}
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="shareAlert-{{ $memory->id }}"></div>

        {{-- Sharing Type Tabs --}}
        <ul class="nav nav-pills mb-3" id="shareTypeTabs-{{ $memory->id }}" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="individual-tab-{{ $memory->id }}" data-bs-toggle="pill" 
                    data-bs-target="#individual-{{ $memory->id }}" type="button" role="tab">
              <i class="bi bi-person"></i> Individual Friends
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="category-tab-{{ $memory->id }}" data-bs-toggle="pill" 
                    data-bs-target="#category-{{ $memory->id }}" type="button" role="tab">
              <i class="bi bi-people"></i> Friend Categories
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="public-tab-{{ $memory->id }}" data-bs-toggle="pill" 
                    data-bs-target="#public-{{ $memory->id }}" type="button" role="tab">
              <i class="bi bi-link-45deg"></i> Public Link
            </button>
          </li>
        </ul>

        <div class="tab-content" id="shareTypeContent-{{ $memory->id }}">
          {{-- Individual Friends Tab --}}
          <div class="tab-pane fade show active" id="individual-{{ $memory->id }}" role="tabpanel">
            <form id="shareIndividualForm-{{ $memory->id }}">
              @csrf
              <input type="hidden" name="memory_id" value="{{ $memory->id }}">
              <input type="hidden" name="action" value="shareToIndividualFriends">

              <div class="mb-3">
                <label class="form-label fw-semibold">Select individual friends:</label>
                <div class="border rounded p-3 max-height-300 overflow-auto">
                  @php
                    $friends = auth()->user()->friends ?? collect();
                  @endphp
                  
                  @forelse($friends as $friend)
                    @php
                      $isAlreadyShared = $memory->sharedUsers->contains('id', optional($friend->friendUser)->id);
                    @endphp
                    <div class="form-check mb-2 d-flex align-items-center">
                      <input class="form-check-input me-2" type="checkbox" 
                             name="friend_ids[]" value="{{ $friend->id }}" 
                             id="individual-friend-{{ $friend->id }}-{{ $memory->id }}"
                             {{ $isAlreadyShared ? 'disabled' : '' }}>
                      <label class="form-check-label flex-grow-1" 
                             for="individual-friend-{{ $friend->id }}-{{ $memory->id }}">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <strong>{{ optional($friend->friendUser)->name ?? 'Unknown' }}</strong>
                            <br>
                            <small class="text-muted">{{ optional($friend->friendUser)->email ?? '-' }}</small>
                            @if($friend->category)
                              <span class="badge bg-secondary ms-1">{{ $friend->category }}</span>
                            @endif
                          </div>
                          @if($isAlreadyShared)
                            <span class="badge bg-success">Already Shared</span>
                          @endif
                        </div>
                      </label>
                    </div>
                  @empty
                    <p class="text-muted text-center mb-0">
                      <i class="bi bi-person-x"></i> No friends added yet.
                      <a href="/memories?tab=friends" class="text-decoration-none">Add friends first</a>
                    </p>
                  @endforelse
                </div>
              </div>

              @if($friends->count() > 0)
                <div class="d-flex justify-content-between">
                  <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="toggleAllIndividualFriends({{ $memory->id }}, true)">Select All</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="toggleAllIndividualFriends({{ $memory->id }}, false)">Deselect All</button>
                  </div>
                  <button type="submit" class="btn btn-success">
                    <i class="bi bi-share"></i> Share with Selected
                  </button>
                </div>
              @endif
            </form>
          </div>

          {{-- Category Friends Tab --}}
          <div class="tab-pane fade" id="category-{{ $memory->id }}" role="tabpanel">
            <form id="shareCategoryForm-{{ $memory->id }}">
              @csrf
              <input type="hidden" name="memory_id" value="{{ $memory->id }}">
              <input type="hidden" name="action" value="shareToCategoryFriends">

              <div class="mb-3">
                <label class="form-label fw-semibold">Select friend categories:</label>
                @php
                  $categories = auth()->user()->friends->pluck('category')->filter()->unique()->sort();
                @endphp
                
                @if($categories->count() > 0)
                  <div class="border rounded p-3">
                    @foreach($categories as $category)
                      @php
                        $categoryFriends = auth()->user()->friends->where('category', $category);
                        $categoryFriendNames = $categoryFriends->map(fn($f) => optional($f->friendUser)->name ?? 'Unknown')->filter()->take(3);
                        $totalInCategory = $categoryFriends->count();
                        $alreadySharedCount = $categoryFriends->filter(function($friend) use ($memory) {
                          return $memory->sharedUsers->contains('id', optional($friend->friendUser)->id);
                        })->count();
                      @endphp
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" 
                               name="categories[]" value="{{ $category }}" 
                               id="category-{{ Str::slug($category) }}-{{ $memory->id }}">
                        <label class="form-check-label" for="category-{{ Str::slug($category) }}-{{ $memory->id }}">
                          <div>
                            <strong class="text-primary">{{ $category }}</strong>
                            <span class="badge bg-info text-dark ms-2">{{ $totalInCategory }} friends</span>
                            @if($alreadySharedCount > 0)
                              <span class="badge bg-warning text-dark ms-1">{{ $alreadySharedCount }} already shared</span>
                            @endif
                            <br>
                            <small class="text-muted">
                              Friends: {{ $categoryFriendNames->take(2)->implode(', ') }}
                              @if($totalInCategory > 2) and {{ $totalInCategory - 2 }} more @endif
                            </small>
                          </div>
                        </label>
                      </div>
                    @endforeach
                  </div>
                  
                  <div class="d-flex justify-content-between mt-3">
                    <div>
                      <button type="button" class="btn btn-outline-secondary btn-sm" 
                              onclick="toggleAllCategories({{ $memory->id }}, true)">Select All</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" 
                              onclick="toggleAllCategories({{ $memory->id }}, false)">Deselect All</button>
                    </div>
                    <button type="submit" class="btn btn-success">
                      <i class="bi bi-people"></i> Share with Categories
                    </button>
                  </div>
                @else
                  <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No friend categories found. Friends need to have categories assigned for group sharing.
                  </div>
                @endif
              </div>
            </form>
          </div>

          {{-- Public Link Tab --}}
          <div class="tab-pane fade" id="public-{{ $memory->id }}" role="tabpanel">
            @if($memory->share_token)
              <div class="alert alert-info">
                <h6><i class="bi bi-link-45deg"></i> Existing Public Share Link:</h6>
                <div class="input-group">
                  <input type="text" class="form-control" 
                         value="{{ url('/memories/' . $memory->id . '?shared_token=' . $memory->share_token) }}" 
                         readonly id="existingShareLink-{{ $memory->id }}">
                  <button class="btn btn-outline-secondary" type="button" 
                          onclick="copyShareLink('existing', {{ $memory->id }})">
                    <i class="bi bi-clipboard"></i> Copy
                  </button>
                </div>
                <small class="text-muted">This memory already has a public share link</small>
              </div>
            @endif

            <form id="sharePublicForm-{{ $memory->id }}">
              @csrf
              <input type="hidden" name="memory_id" value="{{ $memory->id }}">
              <input type="hidden" name="action" value="generatePublicLink">

              <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Warning:</strong> Anyone with this link will be able to view your memory.
              </div>

              <button type="submit" class="btn btn-warning">
                <i class="bi bi-link-45deg"></i> 
                {{ $memory->share_token ? 'Generate New Public Link' : 'Generate Public Link' }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
.max-height-300 {
    max-height: 300px;
}

.tab-content {
    min-height: 300px;
}

.form-check-label {
    cursor: pointer;
}

.form-check-input:disabled + .form-check-label {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const memoryId = {{ $memory->id }};
    
    const individualForm = document.getElementById('shareIndividualForm-' + memoryId);
    const categoryForm = document.getElementById('shareCategoryForm-' + memoryId);
    const publicForm = document.getElementById('sharePublicForm-' + memoryId);
    const alertBox = document.getElementById('shareAlert-' + memoryId);

    if (individualForm && alertBox) {
        individualForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleShareSubmit(individualForm, alertBox, memoryId);
        });
    }

    if (categoryForm && alertBox) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleShareSubmit(categoryForm, alertBox, memoryId);
        });
    }

    if (publicForm && alertBox) {
        publicForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleShareSubmit(publicForm, alertBox, memoryId);
        });
    }
});

function toggleAllIndividualFriends(memoryId, select) {
    const checkboxes = document.querySelectorAll(`#shareIndividualForm-${memoryId} input[name="friend_ids[]"]:not(:disabled)`);
    checkboxes.forEach(cb => cb.checked = select);
}

function toggleAllCategories(memoryId, select) {
    const checkboxes = document.querySelectorAll(`#shareCategoryForm-${memoryId} input[name="categories[]"]`);
    checkboxes.forEach(cb => cb.checked = select);
}
</script>