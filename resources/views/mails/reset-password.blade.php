@component('mail::message',['data'=>$data])

<h1> Hello {{$data['name']}}, </h1>

<p>
    There was a request to change your password!
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

Thanks,<br>

@endcomponent