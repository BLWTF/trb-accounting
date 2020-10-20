<?php

namespace Modules\DoubleEntry\Widgets;

use App\Abstracts\Widget;
use App\Traits\Currencies;
use Modules\DoubleEntry\Models\Account as Coa;

class TotalIncomeByCoa extends Widget
{
    use Currencies;

    public $default_name = 'double-entry::widgets.total_income_by_coa';

    public $views = [
        'header' => 'partials.widgets.stats_header',
    ];

    public function show()
    {
        $current = $open = $overdue = 0;

        // income types
        $types = [13, 14, 15];

        Coa::with('ledgers')->inType($types)->enabled()->each(function ($coa) use (&$current, &$open, &$overdue) {

            $model = $coa->ledgers()
                ->whereNotNull('credit')
                ->whereHasMorph('ledgerable', ['App\Models\Sale\InvoiceItem', 'App\Models\Banking\Transaction'], function ($query, $type) {
                    if ($type == 'App\Models\Banking\Transaction') {
                        $query->whereNull('document_id');
                    }
                });

            $this->applyFilters($model, ['date_field' => 'issued_at'])->get()->each(function ($ledger) use (&$current, &$open, &$overdue) {
                $ledgerable = $ledger->ledgerable;

                switch ($ledgerable->getTable()) {
                    case 'invoice_items':
                        list($open_tmp, $overdue_tmp) = $this->calculateDocumentTotals($ledgerable->invoice);

                        $open += $open_tmp;
                        $overdue += $overdue_tmp;

                        break;
                    case 'transactions':
                        $current += $ledgerable->getAmountConvertedToDefault();

                        break;
                }

            });

        });

        $grand = $current + $open + $overdue;

        $totals = [
            'grand' => money($grand, setting('default.currency'), true),
            'open' => money($open, setting('default.currency'), true),
            'overdue' => money($overdue, setting('default.currency'), true),
        ];

        return $this->view('widgets.total_income', [
            'totals' => $totals,
        ]);
    }
}
