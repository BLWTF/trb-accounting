<?php

namespace Modules\DoubleEntry\Observers\Banking;

use App\Abstracts\Observer;
use App\Models\Banking\Account as Model;
use App\Models\Module\Module;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\AccountBank;

class Account extends Observer
{
    /**
     * Listen to the created event.
     *
     * @param  Model  $account
     * @return void
     */
    public function created(Model $account)
    {
        if (!Module::alias('double-entry')->companyId($account->company_id)->enabled()->first()) {
            return;
        }

        if ($account->bank_name == 'chart-of-accounts') {
            $account->bank_name = '';
            $account->save();
            return;
        }

        $coa = Coa::create([
            'company_id' => $account->company_id,
            'type_id' => setting('double-entry.types_bank', 6),
            'code' => Coa::max('code') + 1,
            'name' => $account->name,
            'enabled' => $account->enabled,
        ]);

        AccountBank::create([
            'company_id' => $account->company_id,
            'account_id' => $coa->id,
            'bank_id' => $account->id,
        ]);
    }

    /**
     * Listen to the created event.
     *
     * @param  Model  $account
     * @return void
     */
    public function updated(Model $account)
    {
        $rel = AccountBank::where('bank_id', $account->id)->first();

        if (!$rel) {
            return;
        }

        $coa = $rel->account;

        $coa->update([
            'name' => $account->name,
            'code' => $coa->code,
            'type_id' => $coa->type_id,
            'enabled' => $account->enabled,
        ]);
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $account
     * @return void
     */
    public function deleted(Model $account)
    {
        $rel = AccountBank::where('bank_id', $account->id)->first();

        if (!$rel) {
            return;
        }

        $rel->account->delete();
    }
}
