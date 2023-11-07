@extends('layouts.default')

@section('title')
    Transactions
@stop

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Transactions</div>

            <div class="module_btns_wrap">

            </div>

        </div>

        @if (count($user_transactions) > 0)

        <div class="module-content no-padding overflow-x-scroll">

            <table class="table table-hover">

                <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th title="Transaction ID">ID</th>
                </tr>
                </thead>

                <tbody class="table-striped">

                @foreach ($user_transactions as $transaction)

                    <tr class="white-space-nowrap">
                        <td>{!! $transaction['title'] !!}</td>

                        @if ($transaction['credit'] == 1)

                            <td class="purple1">{{$transaction['amount_output']}}</td>

                        @else

                            <td>-{{$transaction['amount_output']}}</td>
                        @endif

                        <td>{{$transaction['time_display']}}</td>

                        <td>{{$transaction['id']}}</td>
                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

        @if ($db_transactions->links() != '')

            {{ $db_transactions->appends(['sort' => Request::input('sort')])->links() }}

        @endif

        @else

            <div class="module-content">
                Nothing to see here {{randomEmoji()}}
            </div>

        @endif

    </div>

    </div>

@stop