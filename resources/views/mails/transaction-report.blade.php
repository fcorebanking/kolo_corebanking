@component('mail::message',['data'=>$data])

<h1> Hello Admin, </h1>
                                      
     
<p>  <?php echo $data['desc'] ?> /p>

<p>  {{$data['reason'] }}/p>
    

{{-- @component('mail::button', ['url' => ''])
Button Text
@endcomponent --}}

Thanks,<br>
{{ $data['user']}}
@endcomponent