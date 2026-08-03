<header class="c-header">
  <a class="c-header-logo" href="{{ home_url('/') }}" aria-label="{{ get_bloginfo('name') }} home">
    {{ get_bloginfo('name') }}
  </a>

  @if (!empty($shared_header_nav))
    <nav class="c-header-nav" aria-label="Global navigation">
      <ul>
        @foreach ($shared_header_nav as $item)
          <li><a href="{{ home_url($item['path']) }}">{{ $item['label'] }}</a></li>
        @endforeach
      </ul>
    </nav>
  @endif
</header>
