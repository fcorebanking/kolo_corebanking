@component('mail::message')

<h1> Hello {{$data['name']}}, </h1>
                                      
<p>
{{$data['subtext']}} <b>{{ ($data['account_number'] != '')?$data['account_number']:''}}</b>.
</p>
  
    
<p>  <?php echo $data['desc'] ?></p>

<p>
    If you have any complaints please contact our support.
</p>
    

{{-- @component('mail::button', ['url' => ''])
Button Text
@endcomponent --}}

Thanks,<br>
{{ auth()->user()->company->company_name }}
@endcomponent