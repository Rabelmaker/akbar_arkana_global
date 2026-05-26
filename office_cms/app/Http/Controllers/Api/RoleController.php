<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with available permissions.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        $search = trim((string) $request->string('search')->toString());
        $page = max((int) $request->integer('page', 1), 1);
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with(['permissions'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $legacyRoles = $roles->getCollection()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users_count' => 0,
                'users_preview' => [],
            ])
            ->values();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'roles' => RoleResource::collection($roles->getCollection())->resolve(),
                'permissions' => PermissionResource::collection($permissions)->resolve(),
                'pagination' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                ],
            ],
            'roles' => $legacyRoles,
            'permissions' => $permissions->pluck('name')->values(),
        ]);
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);
        abort_unless($role->guard_name === 'web', 404);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'role' => RoleResource::make($role->loadMissing('permissions'))->resolve(),
            ],
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => [
                'role' => RoleResource::make($role->loadMissing('permissions'))->resolve(),
            ],
            'role' => $this->transformRole($role->load(['permissions'])),
        ], 201);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => [
                'role' => RoleResource::make($role->loadMissing('permissions'))->resolve(),
            ],
            'role' => $this->transformRole($role->load(['permissions'])),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('roles.delete'), 403);
        abort_unless($role->guard_name === 'web', 404);

        if ($role->name === 'Super Admin' && $role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role Super Admin cannot be deleted while it is still assigned to users.',
                'errors' => [
                    'role' => ['Role Super Admin cannot be deleted while it is still assigned to users.'],
                ],
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
            'data' => (object) [],
        ]);
    }

    /**
     * Display users and their roles for the roles page.
     */
    public function users(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('users.view'), 403);

        $users = User::query()
            ->with('roles:name')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
            ])
            ->values();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Transform a role for API responses.
     *
     * @return array<string, mixed>
     */
    private function transformRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->values(),
            'users_count' => 0,
            'users_preview' => [],
        ];
    }
}
