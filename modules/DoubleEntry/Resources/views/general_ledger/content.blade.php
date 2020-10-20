<div class="card">
    <div class="table-responsive">
        <table class="table table-flush table-hover">
            <thead class="thead-light">
                <tr class="row font-size-unset table-head-line">
                    <th class="col-sm-3 text-uppercase">{{ trans('general.date') }}</th>
                    <th class="col-sm-3 text-uppercase">{{ trans('general.description') }}</th>
                    <th class="col-sm-2 text-uppercase text-right">{{ trans('double-entry::general.debit') }}</th>
                    <th class="col-sm-2 text-uppercase text-right">{{ trans('double-entry::general.credit') }}</th>
                    <th class="col-sm-2 text-uppercase text-right">{{ trans('general.balance') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@foreach($class->de_classes as $de_class)
    @foreach($de_class->types->sortBy('id') as $de_type)
        @foreach($class->de_accounts->where('class_id', $de_class->id)->where('type_id', $de_type->id)->sortBy('name') as $account)
            @if (!empty($account->debit_total) || !empty($account->credit_total))
                @php
                    $closing_balance = $account->opening_balance;
                @endphp
                <div class="card">
                    
                    @if(empty(request()->segment(4)))
                        <div class="card-header border-bottom-0">
                            {{ trans($account->name) }} ({{ trans($account->type->name) }})
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-flush">
                            <thead class="thead-light">
                                @if(request()->segment(4) == 'export' || request()->segment(4) == 'print')
                                    <tr class="row font-size-unset table-head-line">
                                        <th class="col-sm-12">{{ trans($account->name) }} ({{ trans($account->type->name) }})</th>
                                    </tr>
                                @endif
                                <tr class="row font-size-unset table-head-line">
                                    <th class="col-sm-3">{{ trans('accounts.opening_balance') }}</th>
                                    <th class="col-sm-3">&nbsp;</th>
                                    <th class="col-sm-2">&nbsp;</th>
                                    <th class="col-sm-2">&nbsp;</th>
                                    <th class="col-sm-2 text-right">@money($account->opening_balance, setting('default.currency'), true)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($account->transactions as $ledger)
                                    @php
                                        $closing_balance += $ledger->debit - $ledger->credit;
                                    @endphp
                                    <tr class="row font-size-unset">
                                        <td class="col-sm-3">@date($ledger->issued_at)</td>
                                        <td class="col-sm-3 long-texts">{{ $ledger->description }}</td>
                                        <td class="col-sm-2 text-right">@if (!empty($ledger->debit)) @money((double) $ledger->debit, setting('default.currency'), true) @endif</td>
                                        <td class="col-sm-2 text-right">@if (!empty($ledger->credit)) @money((double) $ledger->credit, setting('default.currency'), true) @endif</td>
                                        <td class="col-sm-2 text-right">@money((double) abs($closing_balance), setting('default.currency'), true)</td>
                                    </tr>
                                @endforeach
                                <tr class="row font-size-unset table-head-line">
                                    <th class="col-sm-3">{{ trans('double-entry::general.totals_balance') }}</th>
                                    <th class="col-sm-3">&nbsp;</th>
                                    <th class="col-sm-2 text-right">@money($account->debit_total, setting('default.currency'), true)</th>
                                    <th class="col-sm-2 text-right">@money($account->credit_total, setting('default.currency'), true)</th>
                                    <th class="col-sm-2 text-right">@money(abs($closing_balance), setting('default.currency'), true)</th>
                                </tr>
                                <tr class="row font-size-unset table-head-line">
                                    <th class="col-sm-10" colspan="3">{{ trans('double-entry::general.balance_change') }}</th>
                                    <th class="col-sm-2 text-right">@money(abs($closing_balance - $account->opening_balance), setting('default.currency'), true)</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            @endif
        @endforeach
    @endforeach
@endforeach
