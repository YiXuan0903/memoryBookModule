@extends('layouts.app')
@section('title','All Memories')

@if(isset($error))
    <div class="alert alert-warning">
        {{ $error }}
    </div>
@endif

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

{{-- Undo Delete Alert with Timer --}}
@if(\App\Http\Controllers\MemoryController::isUndoAvailable())
    <div class="alert alert-warning alert-dismissible fade show d-flex justify-content-between align-items-center" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                <strong>Memory Deleted:</strong>
                <span class="undo-text">
                    "{{\App\Http\Controllers\MemoryController::getUndoMemoryTitle()}}" was deleted. 
                    You have {{ \App\Http\Controllers\MemoryController::getUndoTimeRemaining() }} seconds to undo.
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-warning undo-btn" onclick="undoDelete()" data-original-text="Undo">
                <i class="bi bi-arrow-counterclockwise"></i> Undo
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="clearUndo()"></button>
        </div>
    </div>
@endif

{{-- Share link alert - FIXED VERSION --}}
@if(session('share_link'))
  <div class="alert alert-info alert-dismissible fade show d-flex justify-content-between align-items-center" role="alert">
    <div>
      <strong>Share Link:</strong>
      <a href="{{ session('share_link') }}" target="_blank" id="share-link-display">{{ session('share_link') }}</a>
      <input type="hidden" id="hidden-share-link" value="{{ session('share_link') }}">
    </div>
    <button class="btn btn-sm btn-outline-dark ms-2" onclick="copyShareLinkFromSession()" id="copy-btn-session">📋 Copy</button>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- Toast --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="copyToast" class="toast align-items-center border-0" role="alert">
    <div class="d-flex">
      <div id="copyToastBody" class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

{{-- Filter --}}
<div class="card mb-4">
  <div class="card-body">
    <form id="filterForm" class="row g-3 align-items-end" method="GET" action="/memories">
      <input type="hidden" name="tab" value="all">
      
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="{{ request('search') }}">
      </div>

      <div class="col-md-2">
        <label class="form-label">Mood</label>
        <select name="mood" class="form-select">
          <option value="">All</option>
          @foreach(['happy','sad','angry','excited','calm','tired'] as $m)
            <option value="{{ $m }}" {{ request('mood')==$m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Template</label>
        <select name="template" class="form-select">
          <option value="">All</option>
          <option value="daily" {{ request('template')=='daily' ? 'selected' : '' }}>Daily Journal</option>
          <option value="travel" {{ request('template')=='travel' ? 'selected' : '' }}>Travel Diary</option>
          <option value="gratitude" {{ request('template')=='gratitude' ? 'selected' : '' }}>Gratitude Log</option>
          <option value="study" {{ request('template')=='study' ? 'selected' : '' }}>Study Notes</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
      </div>

      <div class="col-md-2">
        <label class="form-label">Privacy</label>
        <select name="privacy" class="form-select">
          <option value="">All</option>
          <option value="1" {{ request('privacy')==='1' ? 'selected' : '' }}>Public</option>
          <option value="0" {{ request('privacy')==='0' ? 'selected' : '' }}>Private</option>
        </select>
      </div>

      <div class="col-md-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="/memories" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

{{-- Grid --}}
<div id="memoryList" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
  @include('memories.partials.memory_cards', ['memories' => $memories])
</div>

{{-- Loader --}}
<div id="loader" class="text-center my-3" style="display:none;">
  <div class="spinner-border text-primary"></div>
</div>

{{-- Hidden pagination --}}
<div id="pagination" style="display:none;">
  {{ $memories->links() }}
</div>

<script>
let undoTimer = null;
let undoCountdown = null;

function initializeUndoTimer() {
    const expiresAt = new Date('{!! session("undo_expires_at") !!}');
    const now = new Date();
    const timeLeft = Math.max(0, Math.ceil((expiresAt - now) / 1000));
    
    if (timeLeft > 0) {
        startUndoCountdown(timeLeft);
    } else {
        clearUndoOption();
    }
}

function startUndoCountdown(seconds) {
    let remaining = seconds;
    updateUndoButtonText(remaining);
    
    undoCountdown = setInterval(function() {
        remaining--;
        
        if (remaining > 0) {
            updateUndoButtonText(remaining);
        } else {
            clearInterval(undoCountdown);
            clearUndoOption();
        }
    }, 1000);
}

function updateUndoButtonText(seconds) {
    const undoButton = document.querySelector('.undo-btn');
    if (undoButton) {
        const originalText = undoButton.dataset.originalText || 'Undo';
        undoButton.innerHTML = `<i class="bi bi-arrow-counterclockwise"></i> ${originalText} (${seconds}s)`;
    }
}

function clearUndoOption() {
    const undoAlert = document.querySelector('.alert:has(.undo-btn)');
    if (undoAlert) {
        undoAlert.style.transition = 'opacity 0.5s ease-out';
        undoAlert.style.opacity = '0';
        setTimeout(() => undoAlert.remove(), 500);
    }
    
    fetch('/memories?clear_undo=1', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }).catch(err => console.log('Failed to clear undo session:', err));
}

function undoDelete() {
    @if(session('undo_expires_at'))
        const expiresAt = new Date('{!! session("undo_expires_at") !!}');
        const now = new Date();
        
        if (now >= expiresAt) {
            alert('Undo period has expired. The memory cannot be restored.');
            clearUndoOption();
            return;
        }
    @endif
    
    if (confirm('Are you sure you want to restore this memory?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/memories/0';
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="action" value="undo_delete">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearUndo() {
    if (undoCountdown) clearInterval(undoCountdown);
    clearUndoOption();
}

function copyShareLink(linkOrType, memoryId = null) {
    let linkToCopy = '';
    let buttonElement = null;
    
    console.log('copyShareLink called with:', linkOrType, memoryId);
    
    if (memoryId !== null) {
        if (linkOrType === 'existing') {
            const linkInput = document.getElementById('existingShareLink-' + memoryId);
            if (linkInput) {
                linkToCopy = linkInput.value;
                buttonElement = event.target.closest('button');
            }
        } else if (linkOrType === 'new') {
            const linkInput = document.getElementById('newShareLink-' + memoryId);
            if (linkInput) {
                linkToCopy = linkInput.value;
                buttonElement = event.target.closest('button');
            }
        }
    } else {
        linkToCopy = linkOrType;
        buttonElement = event.target ? event.target.closest('button') : null;
    }
    
    console.log('Link to copy:', linkToCopy);
    
    if (!linkToCopy) {
        console.error('No link found to copy');
        showToastMessage('No link found to copy', 'error');
        return;
    }
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(linkToCopy).then(() => {
            console.log('Successfully copied via Clipboard API:', linkToCopy);
            showCopySuccess(buttonElement, 'Link copied to clipboard!');
        }).catch(err => {
            console.error('Clipboard API failed:', err);
            fallbackCopyMethod(linkToCopy, buttonElement);
        });
    } else {
        fallbackCopyMethod(linkToCopy, buttonElement);
    }
}

function copyShareLinkFromSession() {
    const hiddenInput = document.getElementById('hidden-share-link');
    const button = document.getElementById('copy-btn-session');
    
    console.log('copyShareLinkFromSession called');
    
    if (!hiddenInput) {
        console.error('Hidden share link input not found');
        showToastMessage('Share link not found', 'error');
        return;
    }
    
    const shareLink = hiddenInput.value;
    console.log('Session link to copy:', shareLink);
    
    if (!shareLink) {
        console.error('No share link found in hidden input');
        showToastMessage('No share link available', 'error');
        return;
    }
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(shareLink).then(() => {
            console.log('Session link copied successfully via Clipboard API');
            showSessionCopySuccess(button);
        }).catch(err => {
            console.error('Session copy Clipboard API failed:', err);
            fallbackSessionCopy(shareLink, button);
        });
    } else {
        fallbackSessionCopy(shareLink, button);
    }
}

function fallbackCopyMethod(linkText, buttonElement) {
    const tempInput = document.createElement('input');
    tempInput.value = linkText;
    tempInput.style.position = 'absolute';
    tempInput.style.left = '-9999px';
    tempInput.style.top = '0';
    document.body.appendChild(tempInput);
    
    try {
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        const successful = document.execCommand('copy');
        
        if (successful) {
            console.log('Successfully copied via execCommand:', linkText);
            showCopySuccess(buttonElement, 'Link copied!');
        } else {
            console.error('execCommand copy failed');
            showManualCopyPrompt(linkText);
        }
    } catch (err) {
        console.error('execCommand failed:', err);
        showManualCopyPrompt(linkText);
    } finally {
        document.body.removeChild(tempInput);
    }
}

function fallbackSessionCopy(link, button) {
    const tempInput = document.createElement('input');
    tempInput.value = link;
    tempInput.style.position = 'absolute';
    tempInput.style.left = '-9999px';
    tempInput.style.top = '0';
    document.body.appendChild(tempInput);
    
    try {
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        const successful = document.execCommand('copy');
        
        if (successful) {
            console.log('Session copy successful via execCommand:', link);
            showSessionCopySuccess(button);
        } else {
            console.error('Session execCommand copy failed');
            showManualCopyPrompt(link);
        }
    } catch (err) {
        console.error('Session execCommand failed:', err);
        showManualCopyPrompt(link);
    } finally {
        document.body.removeChild(tempInput);
    }
}

function showSessionCopySuccess(button) {
    if (!button) return;
    
    const originalText = button.innerHTML;
    button.innerHTML = '✅ Copied!';
    button.classList.remove('btn-outline-dark');
    button.classList.add('btn-success');

    showToastMessage("Share link copied to clipboard!", "success");
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-dark');
    }, 2000);
}

function showCopySuccess(button, message) {
    if (!button) {
        showToastMessage(message || 'Link copied!', 'success');
        return;
    }
    
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check-circle"></i> Copied!';
    button.classList.remove('btn-outline-secondary', 'btn-outline-dark');
    button.classList.add('btn-success');
    
    showToastMessage(message || 'Link copied!', 'success');
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function showManualCopyPrompt(linkText) {
    const userAgent = navigator.userAgent.toLowerCase();
    if (userAgent.includes('mobile') || userAgent.includes('android') || userAgent.includes('iphone')) {
        prompt('Please copy this link manually:', linkText);
    } else {
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="alert alert-warning" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 99999; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h6><i class="bi bi-exclamation-triangle"></i> Please copy manually:</h6>
                <textarea class="form-control" readonly style="height: 60px; font-family: monospace;">${linkText}</textarea>
                <button class="btn btn-sm btn-secondary mt-2" onclick="this.parentElement.parentElement.remove()">Close</button>
            </div>
        `;
        document.body.appendChild(modal);
        
        const textarea = modal.querySelector('textarea');
        textarea.select();
    }
}

function showToastMessage(message, type) {
    const toastBody = document.getElementById('copyToastBody');
    const toast = document.getElementById('copyToast');
    
    if (toastBody && toast) {
        toastBody.textContent = message;
        toast.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-info', 'text-bg-warning');
        
        let bgClass = 'text-bg-info';
        if (type === 'success') bgClass = 'text-bg-success';
        else if (type === 'error') bgClass = 'text-bg-danger';
        else if (type === 'warning') bgClass = 'text-bg-warning';
        
        toast.classList.add(bgClass);
        
        if (typeof bootstrap !== 'undefined') {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    } else {
        console.log('Toast fallback:', message);
        if (type === 'error') {
            alert('Error: ' + message);
        }
    }
}

function handleShareSubmit(form, alertBox, memoryId) {
    alertBox.innerHTML = '';
    
    const formData = new FormData(form);
    const action = formData.get('action');
    
    console.log('Sharing memory via web API:', { action, memoryId });
    
    if (action === 'shareToIndividualFriends') {
        const selectedFriends = form.querySelectorAll('input[name="friend_ids[]"]:checked');
        if (selectedFriends.length === 0) {
            alertBox.innerHTML = '<div class="alert alert-warning">Please select at least one friend.</div>';
            return;
        }
    } else if (action === 'shareToCategoryFriends') {
        const selectedCategories = form.querySelectorAll('input[name="categories[]"]:checked');
        if (selectedCategories.length === 0) {
            alertBox.innerHTML = '<div class="alert alert-warning">Please select at least one category.</div>';
            return;
        }
    }

    let csrfToken = '';
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        csrfToken = metaToken.getAttribute('content');
    }

    fetch("/api/memories", {
        method: "POST",
        headers: { 
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            memory_id: memoryId,
            action: action,
            friend_ids: action === 'shareToIndividualFriends' ? Array.from(form.querySelectorAll('input[name="friend_ids[]"]:checked')).map(cb => cb.value) : undefined,
            categories: action === 'shareToCategoryFriends' ? Array.from(form.querySelectorAll('input[name="categories[]"]:checked')).map(cb => cb.value) : undefined
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Authentication failed. Please login again.");
            }
            if (response.status === 422) {
                return response.json().then(data => {
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
        console.log('Share success:', data);
        
        if (data.success) {
            alertBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            
            form.reset();
            
            if (data.share_link) {
                const linkId = `generatedShareLink-${memoryId}-${Date.now()}`;
                const linkHtml = `
                    <div class="alert alert-info mt-2">
                        <h6><i class="bi bi-link-45deg"></i> Your Public Share Link:</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${data.share_link}" readonly id="${linkId}">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink('${data.share_link}')">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                `;
                alertBox.innerHTML += linkHtml;
            }
            
            if (data.action !== 'generatePublicLink') {
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById(`shareMemoryModal-${memoryId}`));
                    if (modal) modal.hide();
                }, 3000);
            }
            
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger">${data.message || 'Unknown error occurred'}</div>`;
        }
    })
    .catch(err => {
        console.error('Share error:', err);
        alertBox.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
    });
}

function toggleAllIndividualFriends(memoryId, select) {
    const checkboxes = document.querySelectorAll(`#shareIndividualForm-${memoryId} input[name="friend_ids[]"]:not(:disabled)`);
    checkboxes.forEach(cb => cb.checked = select);
}

function toggleAllCategories(memoryId, select) {
    const checkboxes = document.querySelectorAll(`#shareCategoryForm-${memoryId} input[name="categories[]"]`);
    checkboxes.forEach(cb => cb.checked = select);
}

let loading = false;
window.onscroll = function () {
    if (loading) return;
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
        loadMore();
    }
};

function loadMore() {
    let nextPage = document.querySelector('#pagination .pagination a[rel="next"]');
    if (!nextPage) return;

    loading = true;
    document.getElementById("loader").style.display = "block";

    fetch(nextPage.href, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(res => res.text())
        .then(data => {
            document.getElementById("loader").style.display = "none";

            let parser = new DOMParser();
            let htmlDoc = parser.parseFromString(data, 'text/html');
            let newCards = htmlDoc.querySelectorAll('.col');
            newCards.forEach(card => document.querySelector("#memoryList").appendChild(card));

            let newPagination = htmlDoc.querySelector('#pagination');
            document.querySelector("#pagination").innerHTML = newPagination ? newPagination.innerHTML : "";

            loading = false;
        })
        .catch(() => { loading = false; });
}

function debugSessionData() {
    console.log('=== SESSION DEBUG ===');
    const hiddenInput = document.getElementById('hidden-share-link');
    if (hiddenInput) {
        console.log('Hidden input value:', hiddenInput.value);
        console.log('Hidden input length:', hiddenInput.value.length);
    } else {
        console.log('No hidden input found');
    }
    console.log('Share link from PHP:', {!! json_encode(session('share_link')) !!});
    console.log('Memory ID from PHP:', {!! json_encode(session('shared_memory_id')) !!});
    console.log('====================');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SESSION DEBUG INFO ===');
    debugSessionData();
    
    @if(session('undo_available') && session('undo_expires_at'))
        initializeUndoTimer();
    @endif
    
    @if(session('share_link'))
        console.log('Share link found in session:', {!! json_encode(session('share_link')) !!});
        console.log('Shared memory ID:', {!! json_encode(session('shared_memory_id')) !!});
        
        const memoryId = {!! json_encode(session('shared_memory_id')) !!};
        const modal = document.getElementById('shareMemoryModal-' + memoryId);
        if (modal) {
            console.log('Opening modal for memory:', memoryId);
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        } else {
            console.error('Modal not found for memory:', memoryId);
        }
    @else
        console.log('No share link in session');
    @endif
    
    console.log('Page URL:', window.location.href);
    console.log('========================');
    
    const shareModals = document.querySelectorAll('[id^="shareMemoryModal-"]');
    shareModals.forEach(modal => {
        const memoryId = modal.id.replace('shareMemoryModal-', '');
        
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
});

window.addEventListener('beforeunload', function() {
    if (undoCountdown) {
        clearInterval(undoCountdown);
    }
});
</script>

@endsection