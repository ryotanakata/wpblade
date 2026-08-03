@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-404" data-element="404-main">
    <h1>404</h1>
    <p>The page you're looking for doesn't exist.</p>

    <a href="{{ home_url('/') }}" aria-label="Back to home">Back to Home</a>
  </main>

  @include('components.footer')
@endsection