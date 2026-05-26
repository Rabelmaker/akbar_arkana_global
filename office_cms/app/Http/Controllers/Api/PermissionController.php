<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'permissions' => PermissionResource::collection($permissions)->resolve(),
            ],
            'permissions' => $permissions->pluck('name')->values(),
        ]);
    }
}
