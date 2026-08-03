@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-contact" data-element="contact-main">
    <div>
      <h1>{{ $title }}</h1>

      <div data-element="contact-form"></div>
    </div>
  </main>

  @include('components.footer')
@endsection
