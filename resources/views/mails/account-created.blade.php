@component('mail::message',['data'=>$data])

<h1> Hello {{$data['name']}}, </h1>

<p>
    {{$data['subtext']}}
</p>

<p>
    <?php echo $data['desc'] ?>
</p>

<p>
    If you have any complaints please contact our support.
</p>


{{-- @component('mail::button', ['url' => ur('verify-account/34344343434343434')])
Button Text
@endcomponent --}}

Warm regards,<br>
OYINDAMOLA OLUYEMI,<br>
Executive Director,<br>
Finova Africa


@endcomponent