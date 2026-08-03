@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-column-detail" data-element="column-detail-main">
    <article>
      <header>
        <time datetime="{{ $date_attr }}">{{ $date }}</time>
        <h1>{{ $title }}</h1>
      </header>

      @if ($thumbnail)
        <figure class="pg-column-detail-thumbnail">
          <img src="{{ $thumbnail['url'] }}" alt="{{ $thumbnail['alt'] }}" width="{{ $thumbnail['width'] }}" height="{{ $thumbnail['height'] }}" loading="lazy">
        </figure>
      @endif

      {{-- 投稿本文のみ生 HTML 出力を許可（the_content フィルタ済み） --}}
      <div class="pg-column-detail-body">
        {!! $content !!}
      </div>
    </article>

    @if ($prev_post || $next_post)
      <nav class="pg-column-detail-nav" aria-label="Article navigation">
        @if ($prev_post)
          <a href="{{ $prev_permalink }}" rel="prev">← {{ get_the_title($prev_post) }}</a>
        @endif
        @if ($next_post)
          <a href="{{ $next_permalink }}" rel="next">{{ get_the_title($next_post) }} →</a>
        @endif
      </nav>
    @endif
  </main>

  @include('components.footer')
@endsection
