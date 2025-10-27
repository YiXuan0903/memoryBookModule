@extends('layouts.app')

@section('title', 'Add Friend')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">➕ Add Friend</h5>
                </div>
                <div class="card-body">

                    <div id="friendAlert">
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
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
                    </div>

                    <form method="POST" action="{{ route('memories.store') }}">
                        @csrf
                        <input type="hidden" name="action" value="store">

                        <div class="mb-3">
                            <label for="friend_email" class="form-label">Friend's Email</label>
                            <input type="email" name="friend_email" id="friend_email" 
                                   class="form-control" placeholder="example@email.com" 
                                   value="{{ old('friend_email') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category (optional)</label>
                            <select name="category" class="form-select">
                                <option value="">Select Category</option>
                                <option value="Family" {{ old('category') == 'Family' ? 'selected' : '' }}>Family</option>
                                <option value="Classmate" {{ old('category') == 'Classmate' ? 'selected' : '' }}>Classmate</option>
                                <option value="Colleague" {{ old('category') == 'Colleague' ? 'selected' : '' }}>Colleague</option>
                                <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">Add Friend</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection