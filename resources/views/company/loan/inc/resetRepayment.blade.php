<form action='/company/loan/reverse-loan'>
    <select name='product'>
        @foreach ($loan_product as $loan_product_data)
            <option value='{{$loan_product_data->id}}'>{{$loan_product_data->product_name}}</option>
        @endforeach
    </select>
    <button type='submit'>Initiate</button>
</form>

<form action='/company/loan/resetLoanData'>
    <select name='product'>
        @foreach ($loan_product as $loan_product_data)
            <option value='{{$loan_product_data->id}}'>{{$loan_product_data->product_name}}</option>
        @endforeach
    </select>
    <button type='submit'>Finalize</button>
</form>
