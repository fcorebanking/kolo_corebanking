
<div class="col-md-2 col-sm-6">
    <label>Loan Product</label>
<select name='loan_products' id='loan_products' class='form-control form-small' style='margin:5px' >
    @forelse(getProductList() as $productData)
    <option value='{{Crypt::encryptString($productData->id)}}'>{{$productData->product_name." ".$productData->interest_rate."%"}}</option>
    @empty
    @endforelse
</select>
</div>
