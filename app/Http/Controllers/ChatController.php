<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\SeenMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function getPeople(Request $request)
    {
        $userId = auth()->guard('api')->id();

        $people = DB::table('friendships')
            ->join('users', function ($join) use ($userId) {
                $join->on('friendships.user_id', '=', 'users.id')
                    ->orOn('friendships.friend_id', '=', 'users.id');
            })
            ->where(function ($query) use ($userId) {
                $query->where('friendships.user_id', $userId)
                    ->orWhere('friendships.friend_id', $userId);
            })
            ->where('friendships.status', 'accepted')
            ->where('users.id', '!=', $userId) // Loại bỏ chính user khỏi danh sách
            ->select('users.*')
            ->paginate($request['limit'] ?? 10);

        return response()
            ->json(
                $people
            );
    }

    public function getMessage(Request $request)
    {
        $userId = auth()->guard('api')->id();
        $type = $request->input('type');
        $id = $request->input('id');

        if (!$type || !$id) {
            return response()->json(['message' => 'Thiếu tham số type hoặc id'], 400);
        }

        $conversationId = null;

        if ($type === 'private') {
            // Tìm conversation_id cho type = private
            $conversation = DB::table('conversations')
                ->join('conversation_participants as cp1', 'conversations.id', '=', 'cp1.conversation_id')
                ->join('conversation_participants as cp2', 'conversations.id', '=', 'cp2.conversation_id')
                ->where('conversations.type', 'private') // Điều kiện type
                ->where('cp1.user_id', $userId) // Người hiện tại
                ->where('cp2.user_id', $id) // Người bạn đang trò chuyện
                ->select('conversations.id as conversation_id')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Không tìm thấy cuộc trò chuyện'], 404);
            }

            $conversationId = $conversation->conversation_id;
        } elseif ($type === 'group') {
            // Xác minh id là conversation_id của một nhóm
            $conversation = DB::table('conversations')
                ->where('id', $id)
                ->where('type', 'group') // Điều kiện type
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Không tìm thấy nhóm'], 404);
            }

            $conversationId = $id;
        } else {
            return response()->json(['message' => 'Loại type không hợp lệ'], 400);
        }

        // Lấy tin nhắn theo conversation_id
        $messages = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->select([
                'messages.*',
                DB::raw('(CASE WHEN sender_id = ' . $userId . ' THEN "me" ELSE "friends" END) as sender')
            ])
            ->paginate($request['limit'] ?? 10);

        return response()->json($messages);
    }

    public function detailConversation(Request $request)
    {
        $userId = auth()->guard('api')->id();
        $type = $request->input('type');
        $id = $request->input('id');

        if (!$type || !$id) {
            return response()->json(['message' => 'Thiếu tham số type hoặc id'], 400);
        }

        if ($type === 'private') {
            $conversation = DB::table('conversations')
                ->join('conversation_participants as cp1', 'conversations.id', '=', 'cp1.conversation_id')
                ->join('conversation_participants as cp2', 'conversations.id', '=', 'cp2.conversation_id')
                ->join('users as ucp2', 'cp2.user_id', '=', 'ucp2.id')
                ->where('conversations.type', 'private')
                ->where('cp1.user_id', $userId)
                ->where('cp2.user_id', $id)
                ->select([
                    'ucp2.id',
                    'ucp2.name',
                    'ucp2.avatar',
                    'ucp2.last_active',
                    'conversations.id as conversation_id'
                ])
                ->first();

            return response()
                ->json($conversation);
        }

        if ($type === 'group') {
            return response()
                ->json(DB::table('groups')
                    ->where('id', $id)
                    ->select([
                        'id',
                        'name'
                    ])
                    ->first());
        }
    }

    public function saveMessage(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                return Message::create(
                    [
                        'conversation_id' => $request['conversation_id'],
                        'sender_id' => auth()->guard('api')->id(),
                        'content' => $request['content'],
                        'type' => $request['type'],
                        'created_at' => now()->format('Y-m-d H:i:s')
                    ]
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listConversation(Request $request)
    {
        try {
            $userId = auth()->guard('api')->id();

            // Lấy danh sách các cuộc trò chuyện và người đang trò chuyện với bạn
            $conversations = DB::table('conversations')
                ->leftJoin('conversation_participants as cp1', 'conversations.id', '=', 'cp1.conversation_id') // Join để tìm cuộc trò chuyện của bạn
                ->leftJoin('conversation_participants as cp2', 'conversations.id', '=', 'cp2.conversation_id') // Join để tìm người khác trong cuộc trò chuyện
                ->leftJoin('users as ucp2', 'cp2.user_id', '=', 'ucp2.id')
                ->leftJoin(DB::raw(
                    "(
                       SELECT conversation_id, MAX(id) as latest_id FROM messages GROUP BY conversation_id
                    ) as message_latest"
                ), 'message_latest.conversation_id', '=', 'conversations.id')
                ->leftJoin('messages', function ($join) {
                    $join->on('conversations.id', '=', 'messages.conversation_id');
                    $join->on('message_latest.latest_id', '=', 'messages.id');
                })
                ->leftJoinSub(
                    DB::table('messages')
                        ->select(
                            'conversation_id',
                            DB::raw('COUNT(*) as total_unread')
                        )
                        ->where('seen', 0)
                        ->where('sender_id', '!=', $userId)
                        ->groupBy('conversation_id'),
                    'messages_unread',
                    function ($join) {
                        $join->on('conversations.id', '=', 'messages_unread.conversation_id');
                    }
                )
                ->where('cp1.user_id', $userId) // Điều kiện: cuộc trò chuyện của bạn
                ->where('cp2.user_id', '!=', $userId) // Điều kiện: người khác
                ->select(
                    'ucp2.id',
                    'ucp2.name',
                    'ucp2.avatar',
                    'ucp2.last_active',
                    'conversations.type',
                    DB::raw("CASE WHEN messages.sender_id = $userId THEN 'me' ELSE 'friend' END as sender"),
                    'conversations.id as conversation_id',
                    'messages.content as lastMessage',
                    'messages.created_at as sent_at',
                    DB::raw('COALESCE(total_unread, 0) as unread'),
                    DB::raw('false as isOnline')
                )
                ->orderByRaw('GREATEST(conversations.created_at, messages.created_at) DESC')
                // ->limit(5)
                ->get();

            // Hiển thị kết quả
            return response()->json($conversations);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function seenMessage(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if (isset($request['conversation_id'])) {
                    $userId = auth()->guard('api')->id();
                    SeenMessage::updateOrCreate(['user_id' => $userId, 'message_id' => $request['message_id']], ['user_id' => $userId, 'message_id' => $request['message_id'], 'conversation_id' => $request['conversation_id'], 'seen_at' => now()->format('Y-m-d H:i:s')]);
                }
                return response()->json(true);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    function getOtherUSers(Request $request)
    {
        try {
            $userId = auth()->guard('api')->id();

            $users = User::query()
                ->when(isset($request['keyword']), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $query->where('name', 'LIKE', "%" . $request['keyword'] . "%");
                        $query->orWhere('email', 'LIKE', "%" . $request['keyword'] . "%");
                    });
                })
                ->where('id', '<>', $userId)
                ->addSelect([
                    'status_friend' => function ($query) use ($userId) {
                        $query->select('status')
                            ->from('friendships')
                            ->where(function ($q) use ($userId) {
                                $q->whereColumn('friendships.user_id', 'users.id')
                                    ->where('friendships.friend_id', $userId)
                                    ->orWhere(function ($q2) use ($userId) {
                                        $q2->whereColumn('friendships.friend_id', 'users.id')
                                            ->where('friendships.user_id', $userId);
                                    });
                            })
                            ->limit(1);
                    },
                ])
                ->paginate($request['limit'] ?? 5);

            // Thêm giá trị mặc định "not_friend" nếu không có status
            $users->getCollection()->transform(function ($user) {
                $user->status_friend = $user->status_friend ?? 'not_friend';
                return $user;
            });

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendRequestFriend(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if (isset($request['friend_id'])) {
                    $userId = auth()->guard('api')->id();

                    $checkExists = Friendship::where(function ($query) use ($request, $userId) {
                        $query->where('user_id', $userId);
                        $query->where('friend_id', $request['friend_id']);
                    })->orWhere(function ($query) use ($request, $userId) {
                        $query->where('friend_id', $userId);
                        $query->where('user_id', $request['friend_id']);
                    })->first();

                    if ($checkExists) {
                        return response()->json([
                            'message' => ''
                        ], 500);
                    }

                    return response()
                        ->json(Friendship::create([
                            'user_id' => $userId,
                            'friend_id' => $request['friend_id'],
                            'status' => 'pending'
                        ]));
                }
                return response()->json([
                    'message' => 'Tham số sai !'
                ], 500);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changeRequestFriend(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                if (isset($request['status'])) {
                    $requestFriend = Friendship::with('user:id,name,avatar', 'friend:id,name,avatar')->findOrFail($id);

                    $conversationId = null; // Biến lưu trữ conversation_id

                    if ($request['status'] === 'accepted') {
                        $conversation = Conversation::create(['name' => 'Default', 'type' => 'private']);
                        if ($conversation) {
                            $conversationId = $conversation['id']; // Lưu conversation_id
                            $participants = [
                                [
                                    'conversation_id' => $conversationId,
                                    'user_id' => $requestFriend['user_id'],
                                    'role' => 'member',
                                    'joined_at' => now()->format('Y-m-d H:i:s')
                                ],
                                [
                                    'conversation_id' => $conversationId,
                                    'user_id' => $requestFriend['friend_id'],
                                    'role' => 'member',
                                    'joined_at' => now()->format('Y-m-d H:i:s')
                                ]
                            ];
                            ConversationParticipant::insert($participants);
                        }
                        $requestFriend->status = $request['status'];
                        $requestFriend->save();
                    }

                    if ($request['status'] === 'blocked') {
                        $requestFriend->delete();
                    }
                    // Gán thêm conversation_id vào response
                    $requestFriend->conversation_id = $conversationId;

                    return $requestFriend;
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRequestFriend(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $userId = auth()->guard('api')->id();
                return response()
                    ->json(Friendship::where('friendships.user_id', '<>', $userId)
                        ->join('users as user_request', 'friendships.user_id', '=', 'user_request.id')
                        ->where('friendships.friend_id', $userId)
                        ->where('friendships.status', 'pending')
                        ->select('friendships.id', 'user_request.name', 'user_request.avatar')
                        ->orderBy('friendships.created_at', 'desc')
                        ->paginate($request['limit'] ?? 5));
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
