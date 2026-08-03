@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-column" data-element="column-archive-main">
    <header>
      <h1>{{ $archive_title }}</h1>
    </header>

    <div>
      <ul class="pg-column-list">
        @forelse ($columns as $column)
          <li>
            <article class="c-column-card">
              <a href="{{ $column['permalink'] }}">
                @if ($column['thumbnail'])
                  <figure>
                    <img src="{{ $column['thumbnail']['url'] }}" alt="" aria-hidden="true" width="{{ $column['thumbnail']['width'] }}" height="{{ $column['thumbnail']['height'] }}" loading="lazy">
                  </figure>
                @endif
                <time datetime="{{ $column['date_attr'] }}">{{ $column['date'] }}</time>
                <h2>{{ $column['title'] }}</h2>
                <p>{{ $column['excerpt'] }}</p>
              </a>
            </article>
          </li>
        @empty
          <li><p>No articles found.</p></li>
        @endforelse
      </ul>

      @if ($total_pages > 1)
        <nav class="pg-column-pagination" aria-label="Pagination">
          <ul>
            @foreach ($page_numbers as $i)
              <li>
                @if ($i === $current)
                  <span class="--active" aria-current="page">{{ $i }}</span>
                @else
                  <a href="{{ $pagination_links[$i] }}" aria-label="Page {{ $i }}">{{ $i }}</a>
                @endif
              </li>
            @endforeach
          </ul>
        </nav>
      @endif
    </div>
  </main>

  @include('components.footer')
@endsection
