@extends('layouts.app')

@section('content')
    <div class="container">

        <h4 class="mb-4">Profit & Loss Dashboard</h4>

        <form method="GET" action="{{ route('profit.loss') }}">
            <div class="row mb-4">

                <div class="col-md-2">
                    <label>From</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>

                <div class="col-md-2">
                    <label>To</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>

                <div class="col-md-3">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All Branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Staff</label>
                    <select name="staff_id" class="form-control">
                        <option value="">All Staff</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label></label>
                    <button class="btn btn-primary form-control">Filter</button>
                </div>

            </div>
        </form>

        <div class="row">

            <!-- PROFIT -->
            <div class="col-md-6">

                <div class="card border-success">

                    <div class="card-header bg-success text-white">
                        Revenue
                    </div>

                    <div class="card-body">

                        <table class="table">

                            @foreach ($profitGrouped as $type => $items)
                                <tr>
                                    <td>{{ $items->first()->type->name ?? 'Type ' . $type }}</td>
                                    <td class="text-end">
                                        ₦{{ number_format($items->sum('amount'), 2) }}
                                    </td>
                                </tr>
                            @endforeach

                        </table>

                        <div class="alert alert-success">
                            <strong>Total Revenue : ₦{{ number_format($totalProfit, 2) }}</strong>
                        </div>

                    </div>
                </div>
            </div>

            <!-- LOSS -->
            <div class="col-md-6">

                <div class="card border-danger">

                    <div class="card-header bg-danger text-white">
                        Expenses
                    </div>

                    <div class="card-body">

                        <table class="table">

                            @foreach ($lossGrouped as $type => $items)
                                <tr>
                                    <td>{{ $items->first()->type->name ?? 'Type ' . $type }}</td>
                                    <td class="text-end">
                                        ₦{{ number_format($items->sum('amount'), 2) }}
                                    </td>
                                </tr>
                            @endforeach

                        </table>

                        <div class="alert alert-danger">
                            <strong>Total Expenses : ₦{{ number_format($totalLoss, 2) }}</strong>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- NET -->

        <div class="mt-4">

            @if ($net >= 0)
                <div class="alert alert-success text-center">
                    <h4>NET PROFIT : ₦{{ number_format($net, 2) }}</h4>
                </div>
            @else
                <div class="alert alert-danger text-center">
                    <h4>NET LOSS : ₦{{ number_format($net, 2) }}</h4>
                </div>
            @endif

        </div>

    </div>
@endsection
