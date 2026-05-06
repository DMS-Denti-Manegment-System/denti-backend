<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name', 'Denti'))</title>
    <link rel="shortcut icon" href="{{ asset('metronic/assets/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @vite(['resources/css/app.css'])
    @stack('styles')
    <style>
        /* Force Layout Alignment */
        @media (min-width: 992px) {
            #kt_aside { 
                width: 265px !important; 
                position: fixed !important; 
                top: 0; 
                bottom: 0; 
                left: 0; 
                z-index: 100 !important; 
                background: #ffffff !important; 
                border-right: 1px solid #e7ebf3 !important;
            }
            #kt_wrapper { 
                padding-left: 265px !important; 
                display: flex !important;
                flex-direction: column !important;
                flex: 1 0 auto !important;
            }
            #kt_header { 
                left: 265px !important; 
                right: 0 !important; 
                position: fixed !important; 
                z-index: 98 !important; 
                background: #10131a !important;
                height: 60px !important;
                display: flex !important;
                align-items: center !important;
            }
            #kt_content {
                padding-top: 60px !important; /* Offset for fixed header */
            }
            
            /* Minimized State */
            body[data-kt-aside-minimize="on"] #kt_aside { width: 70px !important; }
            body[data-kt-aside-minimize="on"] #kt_wrapper { padding-left: 70px !important; }
            body[data-kt-aside-minimize="on"] #kt_header { left: 70px !important; }
        }

        /* Fix sidebar logo area */
        .aside-logo {
            height: 60px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 20px !important;
            border-bottom: 1px solid #e7ebf3 !important;
        }

        /* Fix overlap issues for mobile */
        @media (max-width: 991.98px) {
            #kt_header { height: 60px !important; }
            #kt_content { padding-top: 60px !important; }
        }
    </style>
</head>
<body id="kt_body" 
    class="app-metronic-match header-fixed header-tablet-and-mobile-fixed aside-enabled aside-fixed"
    data-kt-app-layout="light-sidebar" 
    data-kt-app-header-fixed="true" 
    data-kt-app-sidebar-enabled="true" 
    data-kt-app-sidebar-fixed="true" 
    data-kt-app-sidebar-hoverable="true" 
    data-kt-app-sidebar-push-header="true" 
    data-kt-app-sidebar-push-toolbar="true" 
    data-kt-app-sidebar-push-footer="true"
>

    <script>
        var defaultThemeMode = "light";
        var themeMode = localStorage.getItem("data-bs-theme") || defaultThemeMode;
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    </script>

    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            
            <div id="kt_aside" class="aside aside-light aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
                <!-- Sidebar Logo -->
                <div class="aside-logo flex-column-auto" id="kt_aside_logo">
                    <a href="{{ route('dashboard') }}">
                        <img alt="Logo" src="{{ asset('metronic/assets/media/logos/default-dark.svg') }}" class="h-25px logo" />
                    </a>
                    <div id="kt_aside_minimize_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
                        <i class="ki-duotone ki-double-left fs-2 rotate-180">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>
                
                @include('layouts.partials.sidebar')
            </div>

            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                
                @include('layouts.partials.header')

                <div id="kt_content" class="content d-flex flex-column flex-column-fluid">
                    
                    <div class="toolbar" id="kt_toolbar">
                        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
                            <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                                <h1 class="d-flex align-items-center text-dark fw-bold my-1 fs-3">@yield('page-title', 'Denti Management')</h1>
                                <span class="h-20px border-gray-200 border-start mx-4"></span>
                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                                    <li class="breadcrumb-item text-muted">@yield('page-subtitle', 'Operasyon Yönetimi')</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="post d-flex flex-column-fluid" id="kt_post">
                        <div id="kt_content_container" class="container-xxl">
                            @yield('content')
                        </div>
                    </div>

                </div>

                <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
                    <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
                        <div class="text-dark order-2 order-md-1">
                            <span class="text-muted fw-semibold me-1">2026©</span>
                            <a href="#" class="text-gray-800 text-hover-primary">Denti Core</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('modals')

    <!-- Global Scripts -->
    <script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
    <script>
        window.DentiUI = window.DentiUI || {
            init(root) {
                const scope = root || document;

                if (window.jQuery && $.fn.select2) {
                    $(scope).find('[data-control="select2"]').each(function () {
                        const $element = $(this);

                        if ($element.hasClass('select2-hidden-accessible')) {
                            return;
                        }

                        const config = {
                            minimumResultsForSearch: $element.data('hide-search') ? Infinity : 0,
                            placeholder: $element.data('placeholder') || undefined,
                            dropdownParent: $element.closest('.modal').length ? $element.closest('.modal') : $(document.body),
                            width: '100%',
                        };

                        $element.select2(config);
                    });
                }

                const tooltipTriggerList = [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach((element) => {
                    bootstrap.Tooltip.getOrCreateInstance(element);
                });
            },
            notify(type, message) {
                if (window.toastr && typeof toastr[type] === 'function') {
                    toastr[type](message);
                    return;
                }

                console[type === 'error' ? 'error' : 'log'](message);
            },
            showValidationErrors(errors) {
                Object.values(errors || {}).flat().forEach((message) => this.notify('error', message));
            },
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.DentiUI.init(document);
        });
    </script>
    @stack('scripts')
</body>
</html>
