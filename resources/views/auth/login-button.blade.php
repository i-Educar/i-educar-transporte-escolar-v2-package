@php
    $transporteUrl = \iEducar\Packages\BuritiTransporteEscolar\Support\PortalAberto::url();
@endphp
@if(!empty($transporteUrl))
    <a class="school-transport" href="{{ $transporteUrl }}">
        <i class="fa fa-bus" aria-hidden="true"></i>
        Transporte
    </a>
@endif
