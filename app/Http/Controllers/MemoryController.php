<?php

namespace App\Http\Controllers;

use App\Models\Memory;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemoryController extends Controller
{
    public static function getMemoryDataForMainDashboard()
    {
        if (!auth()->check()) {
            return [
                'totalMemories' => 0,
                'memoryStats' => null,
                'sentimentSummary' => null,
                'moodSummary' => null
            ];
        }

        $memories = Memory::where('user_id', auth()->id())->get();

        $memoryStats = [
            'total'   => $memories->count(),
            'public'  => $memories->where('is_public', true)->count(),
            'private' => $memories->where('is_public', false)->count(),
            'topMood' => $memories->groupBy('mood')
                                  ->sortByDesc(fn($g) => $g->count())
                                  ->keys()
                                  ->first(),
        ];

        $sentimentSummary = [
            'positive' => $memories->where('sentiment', 'positive')->count(),
            'negative' => $memories->where('sentiment', 'negative')->count(),
            'neutral'  => $memories->where('sentiment', 'neutral')->count(),
        ];

        $moods = ['happy','sad','angry','excited','calm','tired'];
        $moodSummary = [];
        foreach ($moods as $m) {
            $moodSummary[$m] = $memories->where('mood', $m)->count();
        }

        return [
            'totalMemories' => $memories->count(),
            'memoryStats' => $memoryStats,
            'sentimentSummary' => $sentimentSummary,
            'moodSummary' => $moodSummary
        ];
    }

    public function switchTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:light,dark,pastel'
        ]);

        $theme = $request->theme;

        if (Auth::check()) {
            $user = Auth::user();
            $user->theme = $theme;
            $user->save();
        } else {
            session(['template' => $theme]);
        }

        return response()->json(['success' => true, 'theme' => $theme]);
    }

    public function index(Request $request)
    {
        Log::info('MemoryController@index called', [
            'url' => $request->fullUrl(),
            'tab' => $request->query('tab'),
            'method' => $request->method()
        ]);

        if ($request->has('clear_undo')) {
            session()->forget(['undo_memory', 'undo_available', 'deleted_memory_title']);
            
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            
            return redirect('/memories');
        }

        $tab = $request->query('tab');

        if ($tab === 'friends') return $this->friends($request);
        if ($tab === 'settings') {
            $user = Auth::user();
            return view('memories.settings', compact('user'));
        }
        if ($tab === 'shared') return $this->sharedWithMe();

        $query = Memory::where('user_id', Auth::id());
        
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->filled('mood')) {
            $query->where('mood', $request->get('mood'));
        }

        if ($request->filled('privacy')) {
            $privacy = $request->get('privacy');
            if ($privacy == '1') {
                $query->where('is_public', true);
            } elseif ($privacy == '0') {
                $query->where('is_public', false);
            }
        }

        if ($request->filled('template')) {
            $query->where('template', $request->get('template'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $memories = $query->orderBy('created_at', 'desc')->paginate(6)->withQueryString();

        if ($request->ajax()) {
            return view('memories.partials.memory-cards', compact('memories'))->render();
        }

        return view('memories.index', compact('memories'));
    }

    public function create(Request $request)
    {
        Log::info('MemoryController@create called');
        
        if ($request->query('tab') === 'friends') {
            return view('memories.friend_create');
        }

        return view('memories.create');
    }

    public function store(Request $request)
    {
        try {
            if ($request->query('tab') === 'settings') {
                
                if ($request->has('delete')) {
                    $user = Auth::user();
                    Auth::logout();
                    $user->delete();
                    return redirect('/')->with('success', 'Your account has been deleted.');
                }

                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email,' . Auth::id(),
                    'password' => 'nullable|confirmed|min:6',
                    'email_notifications' => 'required|boolean',
                ]);

                $user = Auth::user();
                $user->name = $request->input('name');
                $user->email = $request->input('email');
                $user->email_notifications = $request->boolean('email_notifications');

                if ($request->filled('password')) {
                    $user->password = Hash::make($request->input('password'));
                }

                $user->save();

                return back()->with('success', 'Profile updated successfully!');
            }

            if ($request->input('action') === 'store' && $request->filled('friend_email')) {
                Log::info('Processing friend addition');
                
                $request->validate([
                    'friend_email' => 'required|email|exists:users,email',
                    'category'     => 'nullable|string|max:50',
                ]);

                $friendUser = User::where('email', $request->friend_email)->first();

                if ($friendUser->id === Auth::id()) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You cannot add yourself as a friend.'
                        ]);
                    }
                    return redirect()->back()->with('error', 'You cannot add yourself as a friend.');
                }

                $exists = Friend::where('user_id', Auth::id())
                            ->where('friend_id', $friendUser->id)
                            ->exists();

                if ($exists) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This friend already exists.'
                        ]);
                    }
                    return redirect()->back()->with('error','This friend already exists.');
                }

                $friend = Friend::create([
                    'user_id'   => Auth::id(),
                    'friend_id' => $friendUser->id,
                    'category'  => $request->category,
                ]);

                Log::info('Friend added successfully');
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Friend added successfully!',
                        'friend' => [
                            'id' => $friend->id,
                            'name' => $friendUser->name,
                            'email' => $friendUser->email,
                            'category' => $friend->category ?? '-'
                        ]
                    ]);
                }
                
                return redirect('/memories?tab=friends')->with('success', 'Friend added successfully!');
            }

            $request->validate([
                'title'     => 'required|string|max:255',
                'content'   => 'nullable|string',
                'mood'      => 'nullable|string|max:50',
                'tags'      => 'nullable|string|max:255',
                'template'  => 'nullable|string|max:50',
                'file'      => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mp3,wav|max:10240',
            ]);

            $data = [
                'user_id'   => Auth::id(),
                'title'     => $request->title,
                'content'   => $request->content,
                'mood'      => $request->mood,
                'tags'      => $request->tags,
                'template'  => $request->template,
                'is_public' => $request->has('is_public'),
                'sentiment' => $this->analyzeSentiment($request->content),
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('memories', $filename, 'public');
                $data['file_path'] = $path;
                $data['file_type'] = $file->getMimeType();
            }

            $memory = Memory::create($data);

            Log::info('Memory created successfully', ['memory_id' => $memory->id]);

            return redirect('/memories')->with('success', 'Memory added successfully!');

        } catch (\Exception $e) {
            Log::error('Store method error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        Log::info('MemoryController@show called', [
            'id' => $id,
            'has_shared_token' => $request->filled('shared_token'),
            'shared_token' => $request->get('shared_token'),
            'user_authenticated' => Auth::check(),
            'request_url' => $request->fullUrl()
        ]);

        if ($request->filled('shared_token')) {
            $memory = Memory::where('share_token', $request->shared_token)
                           ->where('id', $id) 
                           ->first();
            
            if (!$memory) {
                $memory = Memory::where('share_token', $request->shared_token)->first();
            }
            
            if (!$memory) {
                abort(404, 'Shared memory not found or link is invalid.');
            }
            
            $memory->load('user');
            
            Log::info('Public shared memory accessed', [
                'memory_id' => $memory->id,
                'memory_title' => $memory->title,
                'owner' => $memory->user->name ?? 'Unknown',
                'share_token' => $memory->share_token
            ]);
            
            return view('memories.show_shared', compact('memory'));
        }

        if (!Auth::check()) {
            Log::info('Unauthenticated access attempt to memory', ['id' => $id]);
            return redirect('/login')->with('error', 'Please login to view this memory.');
        }

        $userId = Auth::id();
        $memory = Memory::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('sharedUsers', fn($q2) => $q2->where('users.id', $userId));
            })
            ->first();

        if (!$memory) {
            Log::warning('Memory not found or access denied', ['id' => $id, 'user_id' => $userId]);
            abort(404, 'Memory not found or you do not have permission to view it.');
        }

        Log::info('Authenticated memory access', [
            'memory_id' => $memory->id,
            'user_id' => $userId,
            'is_owner' => $memory->user_id === $userId
        ]);

        return view('memories.show', compact('memory'));
    }

    public function edit($id)
    {
        Log::info('MemoryController@edit called', ['id' => $id]);
        
        $memory = Memory::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        return view('memories.edit', compact('memory'));
    }

    public function update(Request $request, $id)
    {
        Log::info('=== UPDATE METHOD CALLED ===', [
            'id' => $id,
            'all_data' => $request->all(),
            'url' => $request->fullUrl(),
            'is_ajax' => $request->ajax()
        ]);

        try {
            if ($request->has('action') && $request->input('action') === 'undo_delete') {
                return $this->handleUndoDelete($request);
            }

            if ($request->input('action') === 'delete' && $request->filled('friend_id')) {
                return $this->deleteFriend($request);
            }

            if ($request->input('action') === 'unshare' && $request->filled('friend_id')) {
                return $this->unshareFromFriend($request);
            }

            if ($request->query('tab') === 'settings' || $request->has('email_notifications') || ($request->has('name') && !$request->has('title'))) {
                Log::info('Processing settings update');
                return $this->updateSettings($request);
            }

            if ($request->input('action') === 'share') {
                Log::info('Processing memory share');
                return $this->handleMemoryShare($request, $id);
            }

            if ($request->input('action') === 'shareToFriend' || $request->has('memory_id')) {
                Log::info('Processing share to friend');
                return $this->handleShareToFriend($request);
            }

            $memory = Memory::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

            $request->validate([    
        'title'     => 'required|string|max:255',
        'content'   => 'nullable|string',
        'mood'      => 'nullable|string|max:50',
        'tags'      => 'nullable|string|max:255',
        'template'  => 'nullable|string|max:50',
        'file'      => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mp3,wav|max:10240', // Add file validation
    ]);

    $data = [
        'title'     => $request->title,
        'content'   => $request->content,
        'mood'      => $request->mood,
        'tags'      => $request->tags,
        'template'  => $request->template,
        'is_public' => $request->has('is_public'),
        'sentiment' => $this->analyzeSentiment($request->content),
    ];

    if ($request->hasFile('file')) {
        if ($memory->file_path && Storage::disk('public')->exists($memory->file_path)) {
            Storage::disk('public')->delete($memory->file_path);
        }
    
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('memories', $filename, 'public');
        $data['file_path'] = $path;
        $data['file_type'] = $file->getMimeType();
    }

    $memory->update($data);

    Log::info('Memory updated successfully');
    return redirect('/memories')->with('success', 'Memory updated successfully!');

        } catch (\Exception $e) {
            Log::error('Update method error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    "success" => false,
                    "message" => $e->getMessage()
                ], 500);
            }

            return redirect('/memories')->with('error', 'An error occurred while updating memory: ' . $e->getMessage());
        }
    }

    private function handleUndoDelete(Request $request){
        Log::info('Processing undo delete');

        $undoMemory = session('undo_memory');
        $undoExpiresAt = session('undo_expires_at');
    
        if (!$undoMemory) {
            return redirect('/memories')->with('error', 'No memory to restore or undo period has expired.');
        }

        if ($undoExpiresAt && now()->greaterThan(\Carbon\Carbon::parse($undoExpiresAt))) {
            session()->forget(['undo_memory', 'undo_available', 'deleted_memory_title', 'undo_expires_at']);
            return redirect('/memories')->with('error', 'Undo period has expired (5 seconds). Memory cannot be restored.');
        }

        if ($undoMemory['user_id'] !== Auth::id()) {
            return redirect('/memories')->with('error', 'Unauthorized to restore this memory.');
        }

        try {
            $restoredMemory = Memory::create([
                'user_id' => $undoMemory['user_id'],
                'title' => $undoMemory['title'],
                'content' => $undoMemory['content'],
                'mood' => $undoMemory['mood'],
                'tags' => $undoMemory['tags'],
                'template' => $undoMemory['template'],
                'file_path' => $undoMemory['file_path'],
                'file_type' => $undoMemory['file_type'],
                'is_public' => $undoMemory['is_public'],
                'sentiment' => $undoMemory['sentiment'],
                'share_token' => $undoMemory['share_token'],
            ]);

            $restoredMemory->created_at = $undoMemory['created_at'];
            $restoredMemory->save();

            session()->forget(['undo_memory', 'undo_available', 'deleted_memory_title', 'undo_expires_at']);

            Log::info('Memory restored successfully', ['restored_id' => $restoredMemory->id]);

            return redirect('/memories')
                ->with('success', 'Memory "' . $undoMemory['title'] . '" has been restored successfully!');

        } catch (\Exception $e) {
            Log::error('Undo delete error', [
                'error' => $e->getMessage(),
                'undo_data' => $undoMemory
            ]);

            return redirect('/memories')
                ->with('error', 'Failed to restore memory: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        Log::info('MemoryController@destroy called', ['id' => $id]);
    
        $memory = Memory::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $memoryTitle = $memory->title;
    
        session(['undo_memory' => [
            'id' => $memory->id,
            'title' => $memoryTitle,
            'user_id' => $memory->user_id,
            'content' => $memory->content,
            'mood' => $memory->mood,
            'tags' => $memory->tags,
            'template' => $memory->template,
            'file_path' => $memory->file_path,
            'file_type' => $memory->file_type,
            'is_public' => $memory->is_public,
            'sentiment' => $memory->sentiment,
            'share_token' => $memory->share_token,
            'created_at' => $memory->created_at->toISOString(),
            'deleted_at' => now()->toISOString(),
        ]]);
    
        $memory->delete();

        return redirect('/memories')
            ->with('success', 'Memory deleted.')
            ->with('undo_available', true)
            ->with('deleted_memory_title', $memoryTitle)
            ->with('undo_expires_at', now()->addSeconds(5)->toISOString()); 
    }

    private function handleMemoryShare(Request $request, $id)
    {
        try {
            $memory = Memory::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

            if ($request->has('public') && $request->input('public') == '1') {
                $newToken = Str::random(32);
                $memory->share_token = $newToken;
                $memory->save();

                $shareLink = url('/memories/' . $memory->id . '?shared_token=' . $newToken);

                return redirect()->back()->with([
                    'success' => 'Public share link generated successfully!',
                    'share_link' => $shareLink,
                    'shared_memory_id' => $memory->id
                ]);
            }

            return redirect()->back()->with('error', 'Please select at least one sharing option.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to share memory: ' . $e->getMessage());
        }
    }

    private function friends(Request $request)
    {
        Log::info('Friends method called');
        
        $query = Friend::where('user_id', Auth::id())->with(['friendUser']);
        
        if ($request->filled('filter_category')) {
            $query->where('category', $request->filter_category);
        }
        
        $friends = $query->get();
        
        foreach ($friends as $friend) {
            $friend->sharedMemories = Memory::whereHas('sharedUsers', function($q) use ($friend) {
                $q->where('users.id', $friend->friend_id);
            })->where('user_id', Auth::id())->get(['id', 'title']);
        }
        
        return view('memories.friend_index', compact('friends'));
    }

    private function deleteFriend(Request $request)
    {
        try {
            $friend = Friend::where('id', $request->friend_id)
                          ->where('user_id', Auth::id())
                          ->firstOrFail();
            
            $friend->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Friend deleted successfully!'
                ]);
            }
            
            return redirect()->back()->with('success', 'Friend removed successfully!');
            
        } catch (\Exception $e) {
            Log::error('Delete friend error', ['error' => $e->getMessage()]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete friend: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Failed to remove friend.');
        }
    }

    private function unshareFromFriend(Request $request)
    {
        try {
            $friend = Friend::where('id', $request->friend_id)
                          ->where('user_id', Auth::id())
                          ->with('friendUser')
                          ->firstOrFail();

            if ($request->filled('memory_id')) {
                $memory = Memory::where('id', $request->memory_id)
                              ->where('user_id', Auth::id())
                              ->firstOrFail();
                
                $memory->sharedUsers()->detach($friend->friendUser->id);
                $message = 'Memory unshared successfully!';
            } else {
                $memories = Memory::where('user_id', Auth::id())->get();
                foreach ($memories as $memory) {
                    $memory->sharedUsers()->detach($friend->friendUser->id);
                }
                $message = 'All memories unshared from this friend!';
            }
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Unshare from friend error', ['error' => $e->getMessage()]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unshare: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()->with('error', 'Failed to unshare memories.');
        }
    }

    private function handleShareToFriend(Request $request)
    {
        try {
            $request->validate([
                'memory_id' => 'required|exists:memories,id',
                'friend_ids' => 'required|array',
                'friend_ids.*' => 'exists:friends,id'
            ]);
            
            $memory = Memory::where('id', $request->memory_id)
                           ->where('user_id', Auth::id())
                           ->firstOrFail();
            
            $sharedCount = 0;
            foreach ($request->friend_ids as $friendId) {
                $friend = Friend::where('id', $friendId)
                              ->where('user_id', Auth::id())
                              ->with('friendUser')
                              ->first();
                
                if ($friend && $friend->friendUser) {
                    $exists = $memory->sharedUsers()
                                   ->where('users.id', $friend->friendUser->id)
                                   ->exists();
                    
                    if (!$exists) {
                        $memory->sharedUsers()->attach($friend->friendUser->id);
                        $sharedCount++;
                    }
                }
            }
            
            if ($sharedCount > 0) {
                return redirect()->back()->with('success', 'Memory shared successfully!');
            } else {
                return redirect()->back()->with('info', 'Memory was already shared with this friend.');
            }
            
        } catch (\Exception $e) {
            Log::error('HandleShareToFriend error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to share memory: ' . $e->getMessage());
        }
    }

    public function apiChangeCategory(Request $request, $id)
{
    try {
        // Validate the request
        $request->validate([
            'category' => 'required|string|in:Family,Classmate,Colleague,Other'
        ]);

        // Get authenticated user
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        // Find the friend relationship
        $friend = Friend::where('user_id', $user->id)
                       ->where('id', $id)
                       ->firstOrFail();
        
        if (!$friend) {
            return response()->json([
                'success' => false,
                'message' => 'Friend not found.'
            ], 404);
        }

        $newCategory=$request->input('category');
        $friend->category = $newCategory;
        $friend->save();
        
        // Log the change
        Log::info('Friend category updated', [
            'friend_id' => $friend->id,
            'new_category' => $newCategory,
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'new_category' => $friend->category,
            'friend' => [
                'id' => $friend->id,
                'category' => $friend->category
            ]
        ]);
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid category value.',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('API change category error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'friend_id' => $id,
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to update category: ' . $e->getMessage()
        ], 500);
    }
}

    private function sharedWithMe()
    {
        Log::info('SharedWithMe method called');
        
        $userId = auth()->id();
        $memories = Memory::select('memories.*')
            ->join('memory_user', 'memories.id', '=', 'memory_user.memory_id')
            ->where('memory_user.user_id', $userId)
            ->with('user')
            ->orderBy('memories.created_at', 'desc')
            ->get();

        return view('memories.shared_with_me', compact('memories'));
    }

    protected function updateSettings(Request $request)
    {
        Log::info('UpdateSettings method called');
        
        $user = Auth::user();

        if ($request->has('delete')) {
            Auth::logout();
            $user->delete();
            return redirect('/')->with('success', 'Account deleted successfully.');
        }

        if ($request->has('theme')) {
            session(['template' => $request->theme]);
            $user->theme = $request->theme;
            $user->save();
            return response()->json(['success' => true, 'theme' => $request->theme]);
        }

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'email_notifications' => 'nullable|boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->has('email_notifications')) {
            $user->email_notifications = $request->boolean('email_notifications');
        }
        
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        
        $user->save();

        return redirect('/memories?tab=settings')->with('success', 'Settings updated successfully.');
    }

    public function apiShare(Request $request, $id)
    {
        try {
            $memory = Memory::where('id', $id)
                           ->where('user_id', auth()->id())
                           ->firstOrFail();

            if ($request->has('public') && $request->input('public') == '1') {
                $newToken = Str::random(32);
                $memory->share_token = $newToken;
                $memory->save();

                $shareLink = url('/memories/' . $memory->id . '?shared_token=' . $newToken);

                return response()->json([
                    'success' => true,
                    'message' => 'Public share link generated successfully!',
                    'share_link' => $shareLink
                ]);
            }

            if ($request->has('friend_ids') && is_array($request->friend_ids)) {
                $sharedCount = 0;
                $friendNames = [];

                foreach ($request->friend_ids as $friendId) {
                    $friend = Friend::where('id', $friendId)
                                              ->where('user_id', auth()->id())
                                              ->with('friendUser')
                                              ->first();

                    if ($friend && $friend->friendUser) {
                        $exists = $memory->sharedUsers()
                                       ->where('users.id', $friend->friendUser->id)
                                       ->exists();

                        if (!$exists) {
                            $memory->sharedUsers()->attach($friend->friendUser->id);
                            $sharedCount++;
                            $friendNames[] = $friend->friendUser->name;
                        }
                    }
                }

                if ($sharedCount > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Memory shared with ' . implode(', ', $friendNames) . ' successfully!',
                        'shared_count' => $sharedCount
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Memory was already shared with selected friends.'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Please specify sharing options (public or friend_ids).'
            ], 400);

        } catch (\Exception $e) {
            Log::error('API share memory error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to share memory: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiFriends(Request $request)
    {
        try {
            $query = Friend::where('user_id', auth()->id())->with(['friendUser']);
            
            if ($request->filled('filter_category')) {
                $query->where('category', $request->filter_category);
            }
            
            $friends = $query->get();
            
            foreach ($friends as $friend) {
                $friend->sharedMemories = Memory::whereHas('sharedUsers', function($q) use ($friend) {
                    $q->where('users.id', $friend->friend_id);
                })->where('user_id', auth()->id())->get(['id', 'title']);
            }
            
            return response()->json([
                'success' => true,
                'data' => $friends
            ]);

        } catch (\Exception $e) {
            Log::error('API get friends error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get friends: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiAddFriend(Request $request)
    {
        try {
            $request->validate([
                'friend_email' => 'required|email|exists:users,email',
                'category' => 'nullable|string|max:50',
            ]);

            $friendUser = User::where('email', $request->friend_email)->first();

            if ($friendUser->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot add yourself as a friend.'
                ], 400);
            }

            $exists = Friend::where('user_id', auth()->id())
                        ->where('friend_id', $friendUser->id)
                        ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This friend already exists.'
                ], 400);
            }

            $friend = Friend::create([
                'user_id' => auth()->id(),
                'friend_id' => $friendUser->id,
                'category' => $request->category,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Friend added successfully!',
                'friend' => [
                    'id' => $friend->id,
                    'name' => $friendUser->name,
                    'email' => $friendUser->email,
                    'category' => $friend->category ?? '-'
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API add friend error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add friend: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDeleteFriend($id, Request $request)
    {
        try {
            $friend = Friend::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->first();

            if (!$friend) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friend not found or you do not have permission to delete this friend.'
                ], 404);
            }

            $friendName = optional($friend->friendUser)->name ?? 'Unknown';
            $friend->delete();

            return response()->json([
                'success' => true,
                'message' => "Friend {$friendName} removed successfully!"
            ]);

        } catch (\Exception $e) {
            Log::error('API delete friend error', ['error' => $e->getMessage()]);
            
            return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete friend: ' . $e->getMessage()
                ], 500);
            }
        }

    public function ViewShared($token)
    {
        try {
            $memory = Memory::where('share_token', $token)->with('user')->first();
            
            if (!$memory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shared memory not found or invalid token.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $memory->id,
                    'title' => $memory->title,
                    'content' => $memory->content,
                    'mood' => $memory->mood,
                    'tags' => $memory->tags,
                    'template' => $memory->template,
                    'file_path' => $memory->file_path ? asset('storage/' . $memory->file_path) : null,
                    'file_type' => $memory->file_type,
                    'sentiment' => $memory->sentiment,
                    'created_at' => $memory->created_at,
                    'owner' => [
                        'name' => $memory->user->name,
                        'email' => $memory->user->email
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API view shared memory error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load shared memory: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function isUndoAvailable()
    {
        $undoAvailable = session('undo_available', false);
        $undoMemory = session('undo_memory');
        $undoExpiresAt = session('undo_expires_at'); 
    
        if (!$undoAvailable || !$undoMemory || !$undoExpiresAt) { 
            return false;
        }
    
        if (now()->greaterThan(\Carbon\Carbon::parse($undoExpiresAt))) {
            session()->forget(['undo_memory', 'undo_available', 'deleted_memory_title', 'undo_expires_at']);
            return false;
        }
    
        return true;
    }

    public static function getUndoTimeRemaining()
    {
        $undoExpiresAt = session('undo_expires_at');
    
        if (!$undoExpiresAt) {
            return 0;
        }
    
        $expiresAt = \Carbon\Carbon::parse($undoExpiresAt);
        $remaining = now()->diffInSeconds($expiresAt, false);
    
        return max(0, $remaining);
    }

    public static function getUndoMemoryTitle()
    {
        return session('deleted_memory_title', 'Unknown Memory');
    }

    private function analyzeSentiment(?string $text): string
    {
        if (!$text) return 'neutral';
        
        $text = strtolower($text);
        $positiveWords = ['happy', 'great', 'love', 'excited', 'wonderful', 'amazing', 'good', 'nice', 'fun'];
        $negativeWords = ['sad', 'angry', 'hate', 'tired', 'terrible', 'awful', 'bad', 'lonely', 'stress'];
        
        $score = 0;
        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) $score++;
        }
        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) $score--;
        }
        
        if ($score > 0) return 'positive';
        if ($score < -2) return 'negative';
        return 'neutral';
    }
}