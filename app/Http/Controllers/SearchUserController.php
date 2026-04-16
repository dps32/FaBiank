<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchUserController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 1) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->where('username', 'like', '%' . $query . '%')
            ->where('id', '!=', auth()->id() ?? 0)
            ->select(['id', 'username', 'phone_number'])
            ->limit(10)
            ->get();

        return response()->json(['users' => $users]);
    }
}
