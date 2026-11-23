<?php

namespace App\Http\Controllers\User;

use App\Actions\Accounts\UpdateAccountProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var Account $account */
        $account = $request->attributes->get('activeAccount');

        Gate::authorize('update', $account);

        $accounts = $request->user()
            ->accounts()
            ->orderBy('accounts.name')
            ->get()
            ->map(fn (Account $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status->value,
                'role' => $item->pivot->role,
                'is_owner' => (bool) $item->pivot->is_owner,
            ]);

        return Inertia::render('Settings/Account', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'phone' => $account->phone,
                'timezone' => $account->timezone,
                'currency' => $account->currency,
                'locale' => $account->locale,
                'status' => $account->status->value,
            ],
            'accounts' => $accounts,
        ]);
    }

    public function update(
        UpdateAccountRequest $request,
        UpdateAccountProfile $updateAccountProfile,
    ): RedirectResponse {
        /** @var Account $account */
        $account = $request->attributes->get('activeAccount');

        $updateAccountProfile->handle($account, $request->user(), $request->validated(), $request);

        return redirect()
            ->route('account.settings.edit')
            ->with('success', 'Pengaturan account berhasil diperbarui.');
    }
}
