<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users for the management page.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('users.view'), 403);

        $search = trim((string) ($request->string('search')->toString() ?: $request->string('q')->toString()));
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();
        $page = max((int) $request->integer('page', 1), 1);
        $itemsPerPage = min(max((int) ($request->integer('per_page') ?: $request->integer('itemsPerPage', 10)), 1), 100);
        $sortBy = $request->string('sortBy')->toString();
        $orderBy = strtolower($request->string('orderBy', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';

        $usersQuery = User::query()
            ->with(['roles.permissions'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->role($role))
            ->when($status === 'Active', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($status === 'Pending', fn ($query) => $query->whereNull('email_verified_at'));

        if (in_array($sortBy, ['name', 'email', 'created_at'], true)) {
            $usersQuery->orderBy($sortBy, $orderBy);
        } else {
            $usersQuery->orderBy('name');
        }

        $users = $usersQuery
            ->paginate($itemsPerPage, ['id', 'name', 'email', 'email_verified_at'], 'page', $page);

        $availableRoles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $resourceUsers = UserResource::collection($users->getCollection())->resolve();
        $legacyUsers = $users->getCollection()
            ->map(fn (User $user) => $this->transformUser($user))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'users' => $resourceUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
                'roles' => $availableRoles,
            ],
            'users' => $legacyUsers,
            'totalUsers' => $users->total(),
            'roles' => $availableRoles,
            'widgets' => [
                [
                    'title' => 'Total Users',
                    'value' => User::query()->count(),
                    'desc' => 'All registered accounts',
                    'icon' => 'ri-group-line',
                    'iconColor' => 'primary',
                ],
                [
                    'title' => 'Verified Users',
                    'value' => User::query()->whereNotNull('email_verified_at')->count(),
                    'desc' => 'Users with verified email',
                    'icon' => 'ri-user-follow-line',
                    'iconColor' => 'success',
                ],
                [
                    'title' => 'Pending Verification',
                    'value' => User::query()->whereNull('email_verified_at')->count(),
                    'desc' => 'Users waiting for verification',
                    'icon' => 'ri-user-search-line',
                    'iconColor' => 'warning',
                ],
                [
                    'title' => 'Admin Accounts',
                    'value' => User::query()->role(['Super Admin', 'Admin'])->count(),
                    'desc' => 'Users with admin access',
                    'icon' => 'ri-vip-crown-line',
                    'iconColor' => 'error',
                ],
            ],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('users.view'), 403);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'user' => UserResource::make($user->loadMissing('roles.permissions'))->resolve(),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $user->syncRoles($request->validated('roles', []));

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'user' => UserResource::make($user->loadMissing('roles.permissions'))->resolve(),
            ],
        ], 201);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->validated('password');
        }

        $user->update($payload);
        $user->syncRoles($request->validated('roles', []));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => UserResource::make($user->fresh()->loadMissing('roles.permissions'))->resolve(),
            ],
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('users.delete'), 403);

        if ($request->user()?->is($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
                'errors' => [
                    'user' => ['You cannot delete your own account.'],
                ],
            ], 422);
        }

        if ($user->hasRole('Super Admin') && User::query()->role('Super Admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'The last Super Admin cannot be deleted.',
                'errors' => [
                    'user' => ['The last Super Admin cannot be deleted.'],
                ],
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'data' => (object) [],
        ]);
    }

    /**
     * Transform a user for API responses.
     *
     * @return array<string, mixed>
     */
    private function transformUser(User $user): array
    {
        $roles = $user->getRoleNames()->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roles->first(),
            'roles' => $roles,
            'status' => $user->email_verified_at ? 'Active' : 'Pending',
            'email_verified_at' => $user->email_verified_at,
        ];
    }
}
