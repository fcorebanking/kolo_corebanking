
@if (cando('root|extend-loan-manually'))
<div class='row'>
    <div class='col-sm-6 col-md-6'>
    <label>Extend By</label>
    <select name='extendby' id='extendby' class='form-control form-small' style='margin:5px' >
        <option value='interest'>Interest</option>
        <option value='principal'>Principal</option>
        <option value='Both'>Both - System will take the interest rate from the amount and add to the interest left and put the remaining in principal</option>
    </select>
    <span class='validation-text'></span>
    </div>
    <div class='col-sm-6 col-md-6'>
        <label>Amount</label>
            <input type='number' name='extend_amount' id='extend_amount' class='form-control'>
        <span class='validation-text'></span>
    </div>

</div>
<button type="button" class="btn btn-primary" id="extendLoanPayBTN" onclick='ExtendLoanAMT()'>Pay</button>

@endif

<div class='table-responsive'>

    <table  class="table table-hover non-hover" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Tracking Date</th>
            </tr>
        </thead>
        <tbody >
            @if($infoData->loanExtension)
            <?php $i=1; $totalExtended=0; ?>
                @foreach ( $infoData->loanExtension as $extendedData)
                    <tr><td>{{$i}}</td><td>{{$extendedData->created_at}}</td><td>{{$extendedData->amount}}</td><td>{{$extendedData->end_date}}</td></tr>
            <?php $i++; $totalExtended += $extendedData->amount;  ?>

                @endforeach
            <tr><td></td><td></td><td class='badge badge-danger text-light'>{{$totalExtended}}</td><td></td></tr>

            @endif
        </tbody>
    </table>
    </div>
