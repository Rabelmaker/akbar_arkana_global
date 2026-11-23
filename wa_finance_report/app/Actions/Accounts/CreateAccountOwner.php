<?php

namespace App\Actions\Accounts;

use App\Actions\Finance\ProvisionFinancialFoundation;
use App\Actions\Subscriptions\ProvisionDefaultTrial;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAccountOwner
{
    public function __construct(
        private ProvisionDefaultTrial $provisionDefaultTrial,
        private ProvisionFinancialFoundation $provisionFinancialFoundation,
    ) {}

    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create($data);
            $account = Account::create([
                'name' => $user->name,
                'slug' => $this->uniqueSlug($user->name),
                'email' => $user->email,
                'created_by' => $user->id,
            ]);

            $account->users()->attach($user->id, [
                'role' => 'account-owner',
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            $user->forceFill(['active_account_id' => $account->id])->save();
            $user->assignRole('user');
            $this->provisionDefaultTrial->handle($account, $user);
            $this->provisionFinancialFoundation->handle($account);

            activity('account')
                ->causedBy($user)
                ->performedOn($account)
                ->withProperties(['account_id' => $account->id])
                ->event('created')
                ->log('Primary account created');

            return $user->load('activeAccount');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'account';
        $slug = $baseSlug;
        $suffix = 1;

        while (Account::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
