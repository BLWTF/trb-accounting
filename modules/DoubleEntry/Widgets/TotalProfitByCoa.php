<?php

namespace Modules\DoubleEntry\Widgets;

use App\Abstracts\Widget;
use Modules\DoubleEntry\Models\Account as Coa;

class TotalProfitByCoa extends Widget
{
    public $default_name = 'double-entry::widgets.total_profit_by_coa';

    public $views = [
        'header' => 'partials.widgets.stats_header',
    ];

    public function show()
    {
        $current_income = $open_invoice = $overdue_invoice = 0;
        $current_expenses = $open_bill = $overdue_bill = 0;

        // income types
        $types_incomes = [13, 14, 15];

        Coa::with('ledgers')->inType($types_incomes)->enabled()->each(function ($coa) use (&$current_income, &$open_invoice, &$overdue_invoice) {

            $model = $coa->ledgers()
                ->whereNotNull('credit')
                ->whereHasMorph('ledgerable', ['App\Models\Sale\InvoiceItem', 'App\Models\Banking\Transaction'], function ($query, $type) {
                    if ($type == 'App\Models\Banking\Transaction') {
                        $query->whereNull('document_id');
                    }
                });

            $this->applyFilters($model, ['date_field' => 'issued_at'])->get()->each(function ($ledger) use (&$current_income, &$open_invoice, &$overdue_invoice) {
                $ledgerable = $ledger->ledgerable;

                switch ($ledgerable->getTable()) {
                    case 'invoice_items':
                        list($open_tmp, $overdue_tmp) = $this->calculateDocumentTotals($ledgerable->invoice);

                        $open_invoice += $open_tmp;
                        $overdue_invoice += $overdue_tmp;

                        break;
                    case 'transactions':
                        $current_income += $ledgerable->transaction->getAmountConvertedToDefault();

                        break;
                }

            });

        });

        // income types
        $types_expenses = [11, 12];

        Coa::with('ledgers')->inType($types_expenses)->enabled()->each(function ($coa) use (&$current_expenses, &$open_bill, &$overdue_bill) {

            $model = $coa->ledgers()
                ->whereNotNull('debit')
                ->whereHasMorph('ledgerable', ['App\Models\Purchase\BillItem', 'App\Models\Banking\Transaction'], function ($query, $type) {
                    if ($type == 'App\Models\Banking\Transaction') {
                        $query->whereNull('document_id');
                    }
                });

            $this->applyFilters($model, ['date_field' => 'issued_at'])->get()->each(function ($ledger) use (&$current_expenses, &$open_bill, &$overdue_bill) {
                $ledgerable = $ledger->ledgerable;

                switch ($ledgerable->getTable()) {
                    case 'bill_items':
                        list($open_tmp, $overdue_tmp) = $this->calculateDocumentTotals($ledgerable->bill);

                        $open_bill += $open_tmp;
                        $overdue_bill += $overdue_tmp;

                        break;
                    case 'transactions':
                        $current_expenses += $ledgerable->getAmountConvertedToDefault();

                        break;
                }

            });

        });

        $current = $current_income - $current_expenses;
        $open = $open_invoice - $open_bill;
        $overdue = $overdue_invoice - $overdue_bill;

        $grand = $current + $open + $overdue;

        $totals = [
            'grand'         => money($grand, setting('default.currency'), true),
            'open'          => money($open, setting('default.currency'), true),
            'overdue'       => money($overdue, setting('default.currency'), true),
        ];

        return $this->view('widgets.total_profit', [
            'totals' => $totals,
        ]);
    }
}
