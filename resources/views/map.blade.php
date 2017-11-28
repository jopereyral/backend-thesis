@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Mapa de calor</div>

                <div class="panel-body">
                    <incident-map></incident-map>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
