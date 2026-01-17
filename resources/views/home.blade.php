@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h3 class="mb-3">Dashboard</h3>

    <div class="card">
        <div class="card-body">
            Вы вошли как: <b>{{ auth()->user()->user_full_name }}</b> ({{ auth()->user()->user_login }})
        </div>
    </div>
@endsection
