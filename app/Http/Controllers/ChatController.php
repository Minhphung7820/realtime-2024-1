<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Reaction;
use App\Models\SeenMessage;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function getPeople(Request $request)
    {
        $userId = auth()->guard('api')->id();
        $origin = $request->header('Origin');
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
            ->select('users.*');
        if ($origin === 'http://localhost:6060') {
            $data = $people->pluck('users.id');
        } else {
            $data = $people->paginate($request['limit'] ?? 10);
        }

        return response()
            ->json(
                $data
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
            $conversation = DB::table('conversations')
                ->join('conversation_participants as cp1', 'conversations.id', '=', 'cp1.conversation_id')
                ->join('conversation_participants as cp2', 'conversations.id', '=', 'cp2.conversation_id')
                ->where('conversations.type', 'private')
                ->where('cp1.user_id', $userId)
                ->where('cp2.user_id', $id)
                ->select('conversations.id as conversation_id')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Không tìm thấy cuộc trò chuyện'], 404);
            }

            $conversationId = $conversation->conversation_id;
        } elseif ($type === 'group') {
            $conversation = DB::table('conversations')
                ->where('id', $id)
                ->where('type', 'group')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Không tìm thấy nhóm'], 404);
            }

            $conversationId = $id;
        } else {
            return response()->json(['message' => 'Loại type không hợp lệ'], 400);
        }

        // Lấy tin nhắn và subquery lấy viewers
        $messages = Message::with('reactions')->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->select([
                'messages.*',
                DB::raw('(CASE WHEN sender_id = ' . $userId . ' THEN "me" ELSE "friends" END) as sender'),
                DB::raw('(SELECT COUNT(*) FROM reactions WHERE reactions.message_id = messages.id) as total_reactions')
            ])
            ->paginate($request['limit'] ?? 10);

        // Lấy lastMessage và viewers trực tiếp
        $viewers = DB::table('seen_messages')
            ->join('users', 'seen_messages.user_id', '=', 'users.id')
            ->join(
                DB::raw('(SELECT id FROM messages WHERE conversation_id = ' . $conversationId . ' AND sender_id = ' . $userId . ' ORDER BY created_at DESC LIMIT 1) as last_message'),
                'seen_messages.message_id',
                '=',
                'last_message.id'
            )
            ->select('users.id', 'users.name', 'users.avatar')
            ->where('seen_messages.conversation_id', $conversationId)
            ->get();

        $response = $messages->toArray();
        $response['viewers'] = $viewers;

        return response()->json($response);
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
                    'ucp2.public_key',
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
                $content = $request['content'];
                return Message::create(
                    [
                        'conversation_id' => $request['conversation_id'],
                        'sender_id' => auth()->guard('api')->id(),
                        'content' => $content,
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

            // Lấy danh sách các cuộc trò chuyện
            $conversations = DB::table('conversations')
                ->leftJoin('conversation_participants as cp1', 'conversations.id', '=', 'cp1.conversation_id') // Người dùng hiện tại
                ->leftJoin('conversation_participants as cp2', 'conversations.id', '=', 'cp2.conversation_id') // Người trò chuyện với bạn
                ->leftJoin('users as ucp2', 'cp2.user_id', '=', 'ucp2.id') // Thông tin user còn lại
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
                        ->where('sender_id', '!=', $userId) // Tin nhắn không phải của người dùng hiện tại
                        ->whereRaw('messages.id > IFNULL((SELECT MAX(sm.message_id)
                                                            FROM seen_messages sm
                                                            WHERE sm.conversation_id = messages.conversation_id
                                                            AND sm.user_id = ?), 0)', [$userId]) // Đếm tin nhắn chưa đọc dựa trên seen_messages
                        ->groupBy('conversation_id'),
                    'messages_unread',
                    function ($join) {
                        $join->on('conversations.id', '=', 'messages_unread.conversation_id');
                    }
                )
                ->where('cp1.user_id', $userId) // Chỉ lấy cuộc trò chuyện của người dùng hiện tại
                ->where('cp2.user_id', '!=', $userId) // Loại bỏ chính người dùng khỏi kết quả
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
                    DB::raw('COALESCE(messages_unread.total_unread, 0) as unread'),
                    DB::raw('false as isOnline') // Cột kiểm tra online (có thể bổ sung sau)
                )
                ->orderByRaw('GREATEST(conversations.created_at, messages.created_at) DESC')
                ->get();

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
                    $lastMessage = DB::table('messages')
                        ->where('conversation_id', $request['conversation_id'])
                        ->where('sender_id', '!=', $userId)
                        ->orderBy('id', 'desc')
                        ->value('id');
                    SeenMessage::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'message_id' => $lastMessage
                        ],
                        [
                            'user_id' => $userId,
                            'message_id' => $request['message_id'],
                            'conversation_id' => $request['conversation_id'],
                            'message_id' => $lastMessage,
                            'seen_at' => now()->format('Y-m-d H:i:s')
                        ]
                    );
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

    public function addReaction(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $userId = auth()->guard('api')->id();
                return response()
                    ->json(Reaction::create([
                        'emoji' => $request['emoji'],
                        'user_id' => $userId,
                        'message_id' => $request['message_id']
                    ]));
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function init(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $userId = auth()->guard('api')->id();

                // Lấy tất cả các thiết bị liên quan đến user
                $deviceQuery = UserDevice::with('user')->where('user_id', $userId);

                // Kiểm tra nếu chỉ có 1 thiết bị và chưa có public_key
                $device = $deviceQuery->first(); // Lấy thiết bị đầu tiên (nếu có)
                if ($device->user && is_null($device->user->public_key)) {
                    return response()->json([
                        'is_first' => true,
                        'sync' => false
                    ]);
                }

                // Kiểm tra thiết bị hiện tại
                $checkDevice = $deviceQuery->where('device_id', $request->device_id)->exists();
                return response()->json([
                    'is_first' => false,
                    'sync' => $checkDevice
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addKey(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $userId = auth()->guard('api')->id();
                $input = $request->only([
                    'publicKey',
                    'privatekeyDecryptedByMasterKey',
                    'masterkeyDecryptedByRecoverycode',
                    'masterkeyDecodeByPin'
                ]);
                return User::where('id', $userId)
                    ->whereNull('public_key')
                    ->update([
                        'public_key' => $input['publicKey'],
                        'encrypted_private_key' => $input['privatekeyDecryptedByMasterKey'],
                        'encrypted_master_key_with_pin' => $input['masterkeyDecodeByPin'],
                        'encrypted_master_key_with_recovery' => $input['masterkeyDecryptedByRecoverycode']
                    ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
