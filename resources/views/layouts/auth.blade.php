@extends('template.base')

@section('body')
<body class="hold-transition @yield('auth-body-class', 'login-page')">
    <div class="@yield('auth-box-class', 'login-box')">
        @yield('content')
    </div>
</body>
@endsection
