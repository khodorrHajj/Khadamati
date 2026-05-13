@extends('template.base')

@section('body')
<body class="hold-transition sidebar-mini layout-navbar-fixed">
    <div class="wrapper">
        @include('includes.municipality-navbar')
        @include('includes.municipality-sidebar')

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('page-title')</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        @include('includes.municipality-footer')
    </div>
</body>
@endsection
