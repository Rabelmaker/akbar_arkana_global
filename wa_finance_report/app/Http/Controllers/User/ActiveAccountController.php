<?php

namespace App\Http\Controllers\User;

use App\Actions\Accounts\SwitchActiveAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SwitchActiveAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;

class ActiveAccountController extends Controller
{
    public function __invoke(
        SwitchActiveAccountRequest $request,
        Account $account,
        SwitchActiveAccount $switchActiveAccount,
    ): RedirectResponse {
        $switchActiveAccount->handle($request->user(), $account, $request);

        return redirect()
            ->route('dashboard')
            ->with('success', "Account aktif diubah ke {$account->name}.");
    }
}
