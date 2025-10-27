@extends('layouts.app')
@section('title','Settings')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <h2 class="mb-4"><i class="bi bi-gear"></i> Settings</h2>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-circle me-2"></i> Profile & Preferences
                </div>
                <div class="card-body">
                    <form method="POST" action="/memories?tab=settings">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                   class="form-control shadow-sm" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                                   class="form-control shadow-sm" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock"></i> Password 
                                <span class="text-muted small">(leave blank to keep current)</span>
                            </label>
                            <input type="password" name="password" class="form-control shadow-sm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-shield-check"></i> Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control shadow-sm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-bell"></i> Receive Email Notifications</label>
                            <select name="email_notifications" class="form-select shadow-sm">
                                <option value="1" {{ $user->email_notifications ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$user->email_notifications ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-palette me-2"></i> Appearance
                </div>
                <div class="card-body">
                    <label class="form-label">Theme</label>
                    <select id="themeSelector" class="form-select mb-3">
                        <option value="light"  {{ (session('theme') ?? $user->theme) == 'light' ? 'selected' : '' }}>Light</option>
                        <option value="dark"   {{ (session('theme') ?? $user->theme) == 'dark' ? 'selected' : '' }}>Dark</option>
                        <option value="pastel" {{ (session('theme') ?? $user->theme) == 'pastel' ? 'selected' : '' }}>Pastel</option>
                    </select>
                    <small class="text-muted">Theme will be applied instantly and saved.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-exclamation-triangle me-2"></i> Danger Zone
        </div>
        <div class="card-body">
            <form action="/memories?tab=settings&delete=1" method="POST" 
                  onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone!');">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Delete Account
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const themeSelector = document.getElementById('themeSelector');
    if(themeSelector){
        themeSelector.addEventListener('change', function(){
            const selected = this.value;
            document.body.className = selected + '-theme';
            localStorage.setItem('theme', selected);

            fetch("/memories/switch-theme", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ theme: selected })
            })
            .then(res => res.json().catch(() => null))
            .then(data => console.log("Theme updated", data))
            .catch(err => console.error(err));
        });
    }
</script>
@endsection
