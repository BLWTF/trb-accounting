<?php

namespace Modules\DoubleEntry\Listeners;

use App\Abstracts\Listeners\Report as Listener;
use App\Events\Report\DataLoaded;
use App\Models\Setting\Currency;
use Modules\DoubleEntry\Models\DEClass;

class AddJournalDataToCoreReports extends Listener
{
    public $classes = [
        'App\Reports\IncomeSummary',
        'App\Reports\ExpenseSummary',
        'App\Reports\IncomeExpenseSummary',
        'App\Reports\ProfitLoss',
        'Modules\DoubleEntry\Reports\BalanceSheet',
        'Modules\DoubleEntry\Reports\GeneralLedger',
        'Modules\DoubleEntry\Reports\TrialBalance',
    ];

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param DataLoaded $event
     * @return void
     */
    public function handle(DataLoaded $event)
    {
        if ($this->skipThisClass($event)) {
            return;
        }

        $report = $event->class;

        $currency = Currency::where('code', '=', setting('default.currency', 'USD'))->first();

        $journal_entries = [];

        DEClass::where('name', 'double-entry::classes.income')->with(['accounts'])->each(function ($de_class) use (&$journal_entries, &$currency) {

            $de_class->accounts()->enabled()->each(function ($account) use (&$journal_entries, &$currency) {

                $account->ledgers()->where('ledgerable_type', 'Modules\DoubleEntry\Models\Journal')->each(function ($ledger) use (&$journal_entries, &$currency) {
                    $journal = $ledger->ledgerable;
                    $journal->type = 'income';
                    $journal->currency_code = $currency->code;
                    $journal->currency_rate = $currency->rate;

                    array_push($journal_entries, $journal);
                });

            });

        });

        $report->setTotals($journal_entries, 'issued_at', false, 'default', false);

        $journal_entries = [];

        DEClass::where('name', 'double-entry::classes.expenses')->with(['accounts'])->each(function ($de_class) use (&$journal_entries, &$currency) {

            $de_class->accounts()->enabled()->each(function ($account) use (&$journal_entries, &$currency) {

                $account->ledgers()->where('ledgerable_type', 'Modules\DoubleEntry\Models\Journal')->each(function ($ledger) use (&$journal_entries, &$currency) {
                    $journal = $ledger->ledgerable;
                    $journal->type = 'expense';
                    $journal->currency_code = $currency->code;
                    $journal->currency_rate = $currency->rate;

                    array_push($journal_entries, $journal);
                });

            });

        });

        $report->setTotals($journal_entries, 'issued_at', false, 'default', false);
    }
}
