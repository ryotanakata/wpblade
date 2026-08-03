@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-top" data-element="top-main">
    <section class="pg-top-hero" data-show-insight="show_top_section" data-param-insight="hero">
      <div>
        <h1>Starts Here.</h1>
        <p>A WordPress starter with Docker, Vite, Blade, React &amp; Claude Code.</p>
        <div>
          <a href="{{ home_url('/contact') }}" data-click-insight="click_top_hero_cta" data-param-insight="contact">Contact</a>
          <a href="{{ $column_archive_link }}" data-click-insight="click_top_hero_cta" data-param-insight="column">Read Articles</a>
        </div>
      </div>
    </section>

    <section class="pg-top-columns" aria-labelledby="top-columns-heading" data-show-insight="show_top_section" data-param-insight="columns">
      <div>
        <h2 id="top-columns-heading">Latest Articles</h2>

        <ul class="pg-top-columns-list">
          @forelse ($latest_columns as $column)
            <li>
              <article class="c-column-card">
                <a href="{{ $column['permalink'] }}" data-click-insight="click_top_columns_card" data-param-insight="{{ $column['id'] }}">
                  <time datetime="{{ $column['date_attr'] }}">{{ $column['date'] }}</time>
                  <h3>{{ $column['title'] }}</h3>
                </a>
              </article>
            </li>
          @empty
            <li><p>No articles found.</p></li>
          @endforelse
        </ul>

        <a href="{{ $column_archive_link }}" aria-label="View all articles" data-click-insight="click_top_columns_link">All Articles →</a>
      </div>
    </section>
  </main>

  @include('components.footer')
@endsection
