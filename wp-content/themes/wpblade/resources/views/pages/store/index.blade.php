@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-store" data-element="store-main">
    <article>
      <header>
        <h1>{{ $title }}</h1>
      </header>

      {{-- 固定ページ本文のみ生 HTML 出力を許可（the_content フィルタ済み） --}}
      <div class="pg-store-body">
        {!! $content !!}
      </div>
    </article>
  </main>

  @include('components.footer')
@endsection
