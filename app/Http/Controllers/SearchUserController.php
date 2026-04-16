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
        $currentUserId = $request->user()?->id ?? 0;

        if (strlen($query) < 1) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->where(function ($builder) use ($query) {
                $builder->where('username', 'like', '%' . $query . '%')
                    ->orWhere('name', 'like', '%' . $query . '%');
            })
            ->where('id', '!=', $currentUserId)
            ->select(['id', 'name', 'username', 'phone_number'])
            ->limit(10)
            ->get();

        return response()->json(['users' => $users]);
    }
}
