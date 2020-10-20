<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class Ledger extends Model
{
    protected $table = 'double_entry_ledger';

    protected $fillable = ['company_id', 'account_id', 'ledgerable_id', 'ledgerable_type', 'issued_at', 'entry_type', 'debit', 'credit'];

    public function account()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\Account')->withDefault(['name' => trans('general.na')]);
    }

    public function ledgerable()
    {
        return $this->morphTo();
    }

    /**
     * Scope record.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $id
     * @param $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecord($query, $id, $type)
    {
        return $query->where('ledgerable_id', $id)->where('ledgerable_type', $type);
    }

    public function getDescriptionAttribute()
    {
        $ledgerable = $this->ledgerable;

        if (!$ledgerable) {
            return '';
        }

        switch ($this->ledgerable_type) {
            case 'App\Models\Banking\Transaction':
            case 'Modules\DoubleEntry\Models\Journal':
                return $ledgerable->description;
            case 'App\Models\Sale\Invoice':
                return trans('invoices.invoice_number') . ': ' . $ledgerable->invoice_number;
            case 'App\Models\Sale\InvoiceItem':
            case 'App\Models\Sale\InvoiceItemTax':
                return trans('invoices.invoice_number') . ': ' . $ledgerable->invoice->invoice_number;
            case 'App\Models\Purchase\Bill':
                return trans('bills.bill_number') . ': ' . $ledgerable->bill_number;
            case 'App\Models\Purchase\BillItem':
            case 'App\Models\Purchase\BillItemTax':
                return trans('bills.bill_number') . ': ' . $ledgerable->bill->bill_number;
        }
    }
}
