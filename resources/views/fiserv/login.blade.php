@extends('fiserv.layout')

@section('title', 'Sign in')
@section('body_class', 'narrow')
@section('nav')<!-- login page: no staff nav until authenticated -->@endsection

@push('styles')
    body.narrow { margin-top: 80px; }
    .login-logo { display: block; height: 60px; max-width: 100%; width: auto; margin: 0 auto 16px; }
    .login-heading { text-align: center; }
    .login-heading .subtitle { margin-bottom: 24px; }
@endpush

@section('content')
    <img class="login-logo" src="{{ asset('images/miami-life-logo.png') }}" alt="Miami Life Cosmetic Center">
    <div class="login-heading">
        <p class="subtitle">Fiserv Commerce Hub sandbox · Bank of America Merchant Services — sign in with your team credentials to continue.</p>
    </div>

    @if ($errors->any())
        <div class="status-banner bad">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('fiserv.demo.login.submit') }}">
            @csrf
            <label>Username</label>
            <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" autofocus required>
            <label>Password</label>
            <input type="password" name="password" autocomplete="current-password" required>
            <button type="submit">Sign in</button>
        </form>
    </div>
@endsection
