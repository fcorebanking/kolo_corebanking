@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        <div class="row mb-3">

            <div class="col-md-6">
                <h4 class="mb-0">Profit & Loss Dashboard</h4>
            </div>

            <div class="col-md-6 text-end">
                <a href="/dashboard" class="btn btn-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>

        </div>

        <form method="GET" action="{{ route('company.accounting.profit.loss') }}">

            <div class="row mb-4 align-items-end">

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Branch</label>

                    <select name="branch_id" class="form-control form-control-sm">

                        <option value="">All Branch</option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>

                                {{ $branch->branch_name }}

                            </option>
                        @endforeach

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">Staff</label>

                    <select name="staff_id" class="form-control form-control-sm">

                        <option value="">All Staff</option>

                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>

                                {{ $s->name }}

                            </option>
                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <button class="btn btn-primary btn-sm w-100">
                        Filter
                    </button>

                </div>

            </div>

        </form>


        <div class="row">

            <!-- REVENUE -->

            <div class="col-lg-6">

                <div class="card border-success shadow-sm">

                    <div class="card-header bg-success text-white py-2">
                        Revenue
                    </div>

                    <div class="card-body p-2">

                        <div class="table-responsive">

                            <table class="table table-sm">

                                <tbody>

                                    {{-- FIRST LOOP --}}
                                    @foreach ($profitGrouped as $typeName => $items)
                                        <tr>
                                            <td>{{ $typeName }}</td>
                                            <td class="text-end fw-bold">
                                                ₦{{ number_format(
                                                    $items->sum(function ($t) {
                                                        if (in_array($t->transaction_type_id, [27, 125])) {
                                                            return abs($t->balance_after - ($t->amount + $t->balance_before));
                                                        }
                                                        return $t->amount ?? 0;
                                                    }),
                                                    2,
                                                ) }}
                                            </td>
                                        </tr>
                                    @endforeach


                                    {{-- SECOND LOOP --}}
                                    @foreach ($profitMissingGrouped as $typeName => $items)
                                        <tr>
                                            <td>{{ $typeName }}</td>
                                            <td class="text-end fw-bold">
                                                ₦{{ number_format($items->sum(fn($t) => $t->amount ?? 0), 2) }}
                                            </td>
                                        </tr>
                                    @endforeach


                                    {{-- EMPTY STATE --}}
                                    @if ($profitGrouped->isEmpty() && $profitMissingGrouped->isEmpty())
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">
                                                No revenue data
                                            </td>
                                        </tr>
                                    @endif

                                </tbody>

                            </table>

                        </div>

                        <div class="alert alert-success py-2 mb-0 text-center">

                            <strong>Total Revenue : ₦{{ number_format($totalProfit, 2) }}</strong>

                        </div>

                    </div>

                </div>

            </div>



            <!-- EXPENSES -->

            <div class="col-lg-6">

                <div class="card border-danger shadow-sm">

                    <div class="card-header bg-danger text-white py-2">
                        Expenses
                    </div>

                    <div class="card-body p-2">

                        <div class="table-responsive">

                            <table class="table table-sm">

                                <tbody>

                                    {{-- FIRST LOOP --}}
                                    @foreach ($lossGrouped as $typeName => $items)
                                        <tr>
                                            <td>{{ $typeName }}</td>
                                            <td class="text-end fw-bold">
                                                ₦{{ number_format(
                                                    $items->sum(function ($t) {
                                                        if (in_array($t->transaction_type_id, [27, 125])) {
                                                            return abs($t->balance_after - ($t->amount + $t->balance_before));
                                                        }
                                                        return $t->amount ?? 0;
                                                    }),
                                                    2,
                                                ) }}
                                            </td>
                                        </tr>
                                    @endforeach


                                    {{-- SECOND LOOP --}}
                                    @foreach ($lossMissingGrouped as $typeName => $items)
                                        <tr>
                                            <td>{{ $typeName }}</td>
                                            <td class="text-end fw-bold">
                                                ₦{{ number_format($items->sum(fn($t) => $t->amount ?? 0), 2) }}
                                            </td>
                                        </tr>
                                    @endforeach


                                    {{-- EMPTY STATE --}}
                                    @if ($lossGrouped->isEmpty() && $lossMissingGrouped->isEmpty())
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">
                                                No expense data
                                            </td>
                                        </tr>
                                    @endif



                                </tbody>

                            </table>

                        </div>

                        <div class="alert alert-danger py-2 mb-0 text-center">

                            <strong>Total Expenses : ₦{{ number_format($totalLoss, 2) }}</strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- NET RESULT -->

        <div class="row mt-3">

            <div class="col-lg-12">

                @if ($net >= 0)
                    <div class="alert alert-success text-center">

                        <h5 class="mb-0">
                            NET PROFIT : ₦{{ number_format($net, 2) }}
                        </h5>

                    </div>
                @else
                    <div class="alert alert-danger text-center">

                        <h5 class="mb-0">
                            NET LOSS : ₦{{ number_format($net, 2) }}
                        </h5>

                    </div>
                @endif

            </div>

        </div>

    </div>
@endsection
