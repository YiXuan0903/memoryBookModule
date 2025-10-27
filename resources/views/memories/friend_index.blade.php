@extends('layouts.app')

@section('title','Friends')

@section('content')

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">My Friends</h4>
        {{-- Add Friend Button triggers modal --}}
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
            ➕ Add Friend
        </button>
    </div>

    {{-- Filter by Category --}}
    <form method="GET" action="{{ url('/memories') }}" class="row g-2 mb-3">
      <input type="hidden" name="tab" value="friends">
      <div class="col-md-4">
        <select name="filter_category" class="form-select">
          <option value="">All Categories</option>
          <option value="Family" {{ request('filter_category')==='Family' ? 'selected' : '' }}>Family</option>
          <option value="Classmate" {{ request('filter_category')==='Classmate' ? 'selected' : '' }}>Classmate</option>
          <option value="Colleague" {{ request('filter_category')==='Colleague' ? 'selected' : '' }}>Colleague</option>
          <option value="Other" {{ request('filter_category')==='Other' ? 'selected' : '' }}>Other</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>

    {{-- Friend List --}}
    <div class="table-responsive">
      <table class="table table-bordered align-middle" id="friendsTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Category</th>
            <th>Shared Memories</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($friends as $friend)
            <tr id="friendRow-{{ $friend->id }}">
              <td>{{ optional($friend->friendUser)->name ?? 'Unknown' }}</td>
              <td>{{ optional($friend->friendUser)->email ?? '-' }}</td>
              <td>{{ $friend->category ?? '-' }}</td>
              <td>
                @php $sharedMemories = $friend->sharedMemories ?? collect(); @endphp
                @if($sharedMemories->isEmpty())
                  <span class="badge bg-secondary">None</span>
                @else
                  @foreach($sharedMemories as $m)
                    <div class="d-flex align-items-center mb-1">
                      <span class="badge bg-info text-dark me-2">{{ Str::limit($m->title, 20) }}</span>
                      <button type="button" class="btn btn-sm btn-outline-danger" 
                              onclick="unshareMemory({{ $friend->id }}, {{ $m->id }}, '{{ Str::limit($m->title, 15) }}')">✖</button>
                    </div>
                  @endforeach
                @endif
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-danger delete-friend-btn" 
                        data-friend-id="{{ $friend->id }}" 
                        data-friend-name="{{ optional($friend->friendUser)->name ?? 'Unknown' }}"
                        onclick="deleteFriend(this)">Delete</button>
                <button type="button" class="btn btn-warning btn-sm" 
                        onclick="unshareAllMemories({{ $friend->id }}, '{{ optional($friend->friendUser)->name ?? 'Unknown' }}')">Unshare All</button>
              </td>
            </tr>
          @empty
            <tr id="noFriendsRow">
              <td colspan="5" class="text-center">No friends found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Add Friend Modal --}}
<div class="modal fade" id="addFriendModal" tabindex="-1" aria-labelledby="addFriendModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content theme-card border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white rounded-top-4">
        <h5 class="modal-title fw-bold" id="addFriendModalLabel">➕ Add Friend</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="addFriendAlert"></div>
        <form id="addFriendForm">
          @csrf
          <input type="hidden" name="action" value="store">

          <div class="mb-3">
            <label class="form-label fw-semibold">Friend's Email</label>
            <input type="email" name="friend_email" class="form-control rounded-3 shadow-sm" placeholder="example@email.com" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Category</label>
            <select name="category" id="categorySelect" class="form-select">
              <option value="">Select Category</option>
              <option value="Family">Family</option>
              <option value="Classmate">Classmate</option>
              <option value="Colleague">Colleague</option>
              <option value="Other">Other</option>
            </select>
            <input type="text" name="category_other" id="categoryOther" placeholder="Specify if Other" class="form-control mt-2" style="display:none;">
          </div>

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">✅ Save Friend</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- AJAX Script --}}
<script>
document.addEventListener("DOMContentLoaded", function(){
    
    console.log('Friend index page loaded, setting up AJAX for API');

    const categorySelect = document.getElementById('categorySelect');
    const categoryOther = document.getElementById('categoryOther');
    if(categorySelect) {
        categorySelect.addEventListener('change', function(){
            if(this.value === 'Other') {
                categoryOther.style.display = 'block';
            } else {
                categoryOther.style.display = 'none';
            }
        });
    }

    const addForm = document.getElementById('addFriendForm');
    const alertBox = document.getElementById('addFriendAlert');

    if(addForm && alertBox) {
        console.log('Found add friend form and alert box');
        
        addForm.addEventListener('submit', function(e){
            e.preventDefault();
            console.log('Form submitted via API AJAX');
            
            alertBox.innerHTML = '';

            const formData = new FormData(addForm);
            
            if(categorySelect.value === 'Other'){
                formData.set('category', categoryOther.value || 'Other');
            } else {
                formData.set('category', categorySelect.value);
            }

            console.log('Form data being sent to API:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            let csrfToken = '';
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                csrfToken = metaToken.getAttribute('content');
            } else {
                const tokenInput = addForm.querySelector('input[name="_token"]');
                if (tokenInput) {
                    csrfToken = tokenInput.value;
                }
            }
            
            console.log('CSRF Token:', csrfToken);

            fetch("/api/memories", {
                method: "POST",
                headers: { 
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData,
                credentials: 'same-origin' 
            })
            .then(response => {
                console.log('API Response status:', response.status);
                console.log('API Response headers:', response.headers);
                
                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error("CSRF token expired. Please refresh the page and try again.");
                    }
                    if (response.status === 401) {
                        throw new Error("Authentication failed. Please login again.");
                    }
                    if (response.status === 422) {
                        return response.json().then(data => {
                            console.error('Validation errors:', data);
                            let errorMsg = data.message || "Validation failed";
                            if (data.errors) {
                                errorMsg += ": " + Object.values(data.errors).flat().join(", ");
                            }
                            throw new Error(errorMsg);
                        });
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error('API returned non-JSON response:', text);
                        throw new Error("Server returned unexpected response format");
                    });
                }
            })
            .then(data => {
                console.log('API Success response:', data);
                
                if(data.success){
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addFriendModal'));
                    if (modal) modal.hide();

                    
                    addForm.reset();
                    if(categoryOther) categoryOther.style.display = 'none';

                    
                    const noFriendsRow = document.getElementById('noFriendsRow');
                    if(noFriendsRow) {
                        noFriendsRow.remove();
                    }

                    
                    const table = document.getElementById('friendsTable').querySelector('tbody');
                    if (table) {
                        const newRow = document.createElement('tr');
                        newRow.id = "friendRow-" + data.friend.id;
                        newRow.innerHTML = `
                            <td>${data.friend.name}</td>
                            <td>${data.friend.email}</td>
                            <td>${data.friend.category}</td>
                            <td><span class="badge bg-secondary">None</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger delete-friend-btn" 
                                        data-friend-id="${data.friend.id}" 
                                        data-friend-name="${data.friend.name}"
                                        onclick="deleteFriend(this)">Delete</button>
                                <button type="button" class="btn btn-warning btn-sm" 
                                        onclick="unshareAllMemories(${data.friend.id}, '${data.friend.name}')">Unshare All</button>
                            </td>
                        `;
                        table.appendChild(newRow);
                    }

                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show';
                    successAlert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    const cardBody = document.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.insertBefore(successAlert, cardBody.firstChild);
                        
                        setTimeout(() => {
                            if (successAlert) {
                                successAlert.remove();
                            }
                        }, 5000);
                    }

                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger">${data.message || 'Unknown error occurred'}</div>`;
                }
            })
            .catch(err => {
                console.error('API AJAX Error Details:', err);
                
                let errorMessage = err.message;
                if (errorMessage.includes("CSRF token expired") || errorMessage.includes("Authentication failed")) {
                    errorMessage += ' <a href="#" onclick="location.reload()" class="alert-link">Click here to refresh</a>';
                }
                
                alertBox.innerHTML = `<div class="alert alert-danger">Error: ${errorMessage}</div>`;
            });
        });
    } else {
        console.error('Add friend form or alert box not found!');
    }
});


function deleteFriend(button) {
    const friendId = button.getAttribute('data-friend-id');
    const friendName = button.getAttribute('data-friend-name');
    
    if (!confirm(`Are you sure you want to remove ${friendName} from your friends list?`)) {
        return;
    }
    

    let csrfToken = '';
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        csrfToken = metaToken.getAttribute('content');
    }
    
    console.log('Deleting friend via API:', { friendId, friendName });
    
    const formData = new FormData();
    formData.append('action', 'delete_friend');
    formData.append('friend_id', friendId);
    formData.append('_token', csrfToken);
    
    fetch("/api/memories", {
        method: "POST",
        headers: { 
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        },
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Delete API Response status:', response.status);
        
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Authentication failed. Please login again.");
            }
            if (response.status === 404) {
                throw new Error("Friend not found.");
            }
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.error('API returned non-JSON response:', text);
                throw new Error("Server returned unexpected response format");
            });
        }
    })
    .then(data => {
        console.log('Delete success:', data);
        
        if(data.success){
            const friendRow = document.getElementById(`friendRow-${friendId}`);
            if (friendRow) {
                friendRow.remove();
            }
            
            const tbody = document.querySelector('#friendsTable tbody');
            if (tbody && tbody.children.length === 0) {
                const noFriendsRow = document.createElement('tr');
                noFriendsRow.id = 'noFriendsRow';
                noFriendsRow.innerHTML = '<td colspan="5" class="text-center">No friends found.</td>';
                tbody.appendChild(noFriendsRow);
            }
            
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const cardBody = document.querySelector('.card-body');
            if (cardBody) {
                cardBody.insertBefore(successAlert, cardBody.firstChild);
                
                setTimeout(() => {
                    if (successAlert) {
                        successAlert.remove();
                    }
                }, 5000);
            }
        } else {
            throw new Error(data.message || 'Failed to delete friend');
        }
    })
    .catch(err => {
        console.error('Delete friend error:', err);
        
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
        errorAlert.innerHTML = `
            Error deleting friend: ${err.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const cardBody = document.querySelector('.card-body');
        if (cardBody) {
            cardBody.insertBefore(errorAlert, cardBody.firstChild);
            
            setTimeout(() => {
                if (errorAlert) {
                    errorAlert.remove();
                }
            }, 5000);
        }
    });
}


function getApiToken() {

    return '';
}

function unshareMemory(friendId, memoryId, memoryTitle) {
    if (!confirm(`Unshare "${memoryTitle}" from this friend?`)) {
        return;
    }
    
    let csrfToken = '';
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        csrfToken = metaToken.getAttribute('content');
    }
    
    console.log('Unsharing memory via API:', { friendId, memoryId, memoryTitle });
    
    const formData = new FormData();
    formData.append('action', 'unshare');
    formData.append('friend_id', friendId);
    formData.append('memory_id', memoryId);
    formData.append('_token', csrfToken);
    formData.append('_method', 'PUT');
    
    fetch('/memories/0', {
        method: 'POST',
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        },
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to unshare memory');
        }
    })
    .catch(err => {
        console.error('Unshare memory error:', err);
        alert('Error unsharing memory: ' + err.message);
    });
}
  
function unshareAllMemories(friendId, friendName) {
    if (!confirm(`Unshare all memories from ${friendName}?`)) {
        return;
    }
    
    let csrfToken = '';
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        csrfToken = metaToken.getAttribute('content');
    }
    
    console.log('Unsharing all memories via API:', { friendId, friendName });
    
    const formData = new FormData();
    formData.append('action', 'unshare');
    formData.append('friend_id', friendId);
    formData.append('_token', csrfToken);
    formData.append('_method', 'PUT');
    
    fetch('/memories/0', {
        method: 'POST',
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        },
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to unshare memories');
        }
    })
    .catch(err => {
        console.error('Unshare all memories error:', err);
        alert('Error unsharing memories: ' + err.message);
    });
}
</script>
@endsection