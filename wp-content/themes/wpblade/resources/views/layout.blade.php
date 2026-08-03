<!DOCTYPE html>
<html @php(language_attributes())>
  <head>
    @include('base.meta')
    @include('base.link')
    @include('base.script')

    @php(wp_head())

    @include('base.jsonld')
  </head>
  <body @php(body_class())>
    @include('base.noscript')

    @php(wp_body_open())

    @yield('content')

    @php(wp_footer())
  </body>
</html>
