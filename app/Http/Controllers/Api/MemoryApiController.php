<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Memory;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemoryApiController extends Controller
{
    public function index(Request $request)
    {
        $memories = Memory::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $memories
        ]);
    }

    public function store(Request $request)
    {
        Log::info('API MemoryController@store called', [
            'data' => $request->all(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'auth_guard' => auth()->getDefaultDriver(),
            'session_id' => session()->getId(),
        ]);

        if (!auth()->check()) {
            Log::error('User not authenticated in API');
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = auth()->user();
        Log::info('Authenticated user', ['id' => $user->id, 'email' => $user->email]);

        if ($request->input('action') === 'shareToCategoryFriends') {
            return $this->shareToCategoryFriends($request);
        }

        if ($request->input('action') === 'shareToIndividualFriends') {
            return $this->shareToIndividualFriends($request);
        }

        if ($request->input('action') === 'generatePublicLink') {
            return $this->generatePublicLink($request);
        }

        if ($request->input('action') === 'shareToFriend') {
            return $this->shareToFriend($request);
        }

        if ($request->input('action') === 'delete_friend') {
            return $this->deleteFriendFromStore($request);
        }

        if ($request->filled('friend_email') || $request->input('action') === 'store') {
            return $this->addFriend($request);
        }

        $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'nullable|string',
            'mood'      => 'nullable|string|max:50',
            'tags'      => 'nullable|string|max:255',
            'template'  => 'nullable|string|max:50',
            'is_public' => 'nullable|boolean',
        ]);

        $memory = Memory::create([
            'title'     => $request->title,
            'content'   => $request->content,
            'mood'      => $request->mood,
            'tags'      => $request->tags,
            'template'  => $request->template,
            'is_public' => $request->boolean('is_public'),
            'user_id'   => $user->id,
            'sentiment' => $this->analyzeSentiment($request->content),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Memory created successfully!',
            'data' => $memory
        ], 201);
    }

    private function addFriend(Request $request)
    {
        try {
            Log::info('API addFriend called', $request->all());

            $request->validate([
                'friend_email' => 'required|email|exists:users,email',
                'category' => 'nullable|string|max:50',
            ]);

            $friendUser = User::where('email', $request->friend_email)->first();

            if ($friendUser->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot add yourself as a friend.'
                ], 400);
            }

            $exists = Friend::where('user_id', $request->user()->id)
                        ->where('friend_id', $friendUser->id)
                        ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This friend already exists.'
                ], 400);
            }

            $friend = Friend::create([
                'user_id' => $request->user()->id,
                'friend_id' => $friendUser->id,
                'category' => $request->category,
            ]);

            Log::info('Friend added successfully via API', ['friend_id' => $friend->id]);

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
            Log::error('API addFriend error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function shareToIndividualFriends(Request $request)
    {
        try {
            $request->validate([
                'memory_id' => 'required|exists:memories,id',
                'friend_ids' => 'required|array|min:1',
                'friend_ids.*' => 'exists:friends,id'
            ]);

            $memory = Memory::where('id', $request->memory_id)
                           ->where('user_id', auth()->id())
                           ->first();

            if (!$memory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Memory not found or you do not have permission to share this memory.'
                ], 404);
            }

            return $this->processIndividualSharing($memory, $request->friend_ids);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API shareToIndividualFriends error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to share memory: ' . $e->getMessage()
            ], 500);
        }
    }

    private function shareToCategoryFriends(Request $request)
    {
        try {
            $request->validate([
                'memory_id' => 'required|exists:memories,id',
                'categories' => 'required|array|min:1',
                'categories.*' => 'string|max:50'
            ]);

            $memory = Memory::where('id', $request->memory_id)
                           ->where('user_id', auth()->id())
                           ->first();

            if (!$memory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Memory not found or you do not have permission to share this memory.'
                ], 404);
            }

            $categoryFriends = Friend::where('user_id', auth()->id())
                                   ->whereIn('category', $request->categories)
                                   ->pluck('id')
                                   ->toArray();

            if (empty($categoryFriends)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No friends found in the selected categories.'
                ], 404);
            }

            $result = $this->processIndividualSharing($memory, $categoryFriends);
            
            if ($result->getData()->success) {
                $responseData = $result->getData(true);
                $categoryNames = implode(', ', $request->categories);
                $responseData['message'] = str_replace(
                    'Memory shared successfully with',
                    "Memory shared successfully with friends in categories ({$categoryNames}):",
                    $responseData['message']
                );
                return response()->json($responseData);
            }
            
            return $result;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API shareToCategoryFriends error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to share memory: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generatePublicLink(Request $request)
    {
        try {
            $request->validate([
                'memory_id' => 'required|exists:memories,id'
            ]);

            $memory = Memory::where('id', $request->memory_id)
                           ->where('user_id', auth()->id())
                           ->first();

            if (!$memory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Memory not found or you do not have permission to share this memory.'
                ], 404);
            }

            if (!$memory->share_token) {
                $memory->share_token = \Illuminate\Support\Str::random(32);
                $memory->save();
            }

            $shareLink = url('/memories/' . $memory->id . '?shared_token=' . $memory->share_token);

            Log::info('Public link generated for memory', [
                'memory_id' => $memory->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Public share link generated successfully!',
                'share_link' => $shareLink,
                'action' => 'generatePublicLink'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API generatePublicLink error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate public link: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processIndividualSharing(Memory $memory, array $friendIds)
    {
        $sharedCount = 0;
        $alreadySharedCount = 0;
        $friendNames = [];

        foreach ($friendIds as $friendId) {
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
                } else {
                    $alreadySharedCount++;
                }
            }
        }

        $message = '';
        if ($sharedCount > 0) {
            $friendsList = implode(', ', array_slice($friendNames, 0, 3));
            if (count($friendNames) > 3) {
                $friendsList .= ' and ' . (count($friendNames) - 3) . ' others';
            }
            $message = "Memory shared successfully with {$friendsList}!";
        }

        if ($alreadySharedCount > 0) {
            if ($message) $message .= " ";
            $message .= "({$alreadySharedCount} friend(s) already had access to this memory)";
        }

        if ($sharedCount === 0 && $alreadySharedCount === 0) {
            $message = "No memories were shared.";
        }

        Log::info('Individual sharing processed successfully', [
            'memory_id' => $memory->id,
            'shared_count' => $sharedCount,
            'already_shared_count' => $alreadySharedCount,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'shared_count' => $sharedCount,
            'already_shared_count' => $alreadySharedCount
        ]);
    }

    private function shareToFriend(Request $request)
    {
        try {
            $request->validate([
                'memory_id' => 'required|exists:memories,id',
                'friend_ids' => 'required|array|min:1',
                'friend_ids.*' => 'exists:friends,id'
            ]);

            $memory = Memory::where('id', $request->memory_id)
                           ->where('user_id', auth()->id())
                           ->first();

            if (!$memory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Memory not found or you do not have permission to share this memory.'
                ], 404);
            }

            $sharedCount = 0;
            $alreadySharedCount = 0;
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
                    } else {
                        $alreadySharedCount++;
                    }
                }
            }

            $message = '';
            if ($sharedCount > 0) {
                $friendsList = implode(', ', array_slice($friendNames, 0, 3));
                if (count($friendNames) > 3) {
                    $friendsList .= ' and ' . (count($friendNames) - 3) . ' others';
                }
                $message = "Memory shared successfully with {$friendsList}!";
            }

            if ($alreadySharedCount > 0) {
                if ($message) $message .= " ";
                $message .= "({$alreadySharedCount} friend(s) already had access to this memory)";
            }

            if ($sharedCount === 0 && $alreadySharedCount === 0) {
                $message = "No memories were shared.";
            }

            Log::info('Memory shared to friends successfully via API', [
                'memory_id' => $request->memory_id,
                'shared_count' => $sharedCount,
                'already_shared_count' => $alreadySharedCount,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'shared_count' => $sharedCount,
                'already_shared_count' => $alreadySharedCount
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API shareToFriend error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to share memory: ' . $e->getMessage()
            ], 500);
        }
    }

    private function deleteFriendFromStore(Request $request)
    {
        try {
            $request->validate([
                'friend_id' => 'required|exists:friends,id',
            ]);

            $friend = Friend::where('id', $request->friend_id)
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

            Log::info('Friend deleted successfully via API store method', [
                'friend_id' => $request->friend_id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Friend {$friendName} removed successfully!"
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API deleteFriendFromStore error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete friend: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Memory $memory, Request $request)
{
    $userId = $request->user()->id;
    
    $hasAccess = $memory->user_id === $userId || 
                 $memory->sharedUsers()->where('users.id', $userId)->exists();
    
    if (!$hasAccess) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $memory->load('user');

    return response()->json([
        'success' => true,
        'data' => $memory
    ]);
}

    public function update(Request $request, Memory $memory)
    {
        if ($memory->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'nullable|string',
            'mood'      => 'nullable|string|max:50',
            'tags'      => 'nullable|string|max:255',
            'template'  => 'nullable|string|max:50',
            'is_public' => 'nullable|boolean',
        ]);

        $memory->update([
            'title'     => $request->title,
            'content'   => $request->content,
            'mood'      => $request->mood,
            'tags'      => $request->tags,
            'template'  => $request->template,
            'is_public' => $request->boolean('is_public'),
            'sentiment' => $this->analyzeSentiment($request->content),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Memory updated successfully!',
            'data' => $memory
        ]);
    }

    public function destroy(Memory $memory, Request $request)
    {
        if ($memory->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $memory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Memory deleted successfully!'
        ]);
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

    public function deleteFriend($friendId, Request $request)
    {
        try {
            Log::info('API deleteFriend called', ['friend_id' => $friendId, 'user_id' => auth()->id()]);

            $friend = Friend::where('id', $friendId)
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

            Log::info('Friend deleted successfully via API', ['friend_id' => $friendId]);

            return response()->json([
                'success' => true,
                'message' => "Friend {$friendName} removed successfully!"
            ]);

        } catch (\Exception $e) {
            Log::error('API deleteFriend error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete friend: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFriends(Request $request)
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
            Log::error('API getFriends error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get friends: ' . $e->getMessage()
            ], 500);
        }
    }

    public function viewShared($token)
{
    try {
        $memory = Memory::where('share_token', $token)
                        ->with('user')
                        ->first();

        if (!$memory) {
            return response()->json([
                'success' => false,
                'message' => 'Shared memory not found or token is invalid.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $memory,
            'shared_by' => $memory->user->name ?? 'Unknown'
        ]);

    } catch (\Exception $e) {
        Log::error('API viewShared error', ['error' => $e->getMessage()]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve shared memory: ' . $e->getMessage()
        ], 500);
    }
}
}