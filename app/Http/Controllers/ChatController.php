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
        return response()
            ->json(
                User::where('id', '!=', auth()
                    ->guard('api')
                    ->id())
                    ->paginate($request['limit'] ?? 10)
            );
    }
}
