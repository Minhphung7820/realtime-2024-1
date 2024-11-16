<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
            ->orderBy('created_at', 'asc')
            ->select([
                'messages.*',
                DB::raw('(CASE WHEN sender_id = ' . $userId . ' THEN "me" ELSE "friends" END) as sender')
            ])
            ->paginate($request['limit'] ?? 10);

        return response()->json($messages);
    }
}
