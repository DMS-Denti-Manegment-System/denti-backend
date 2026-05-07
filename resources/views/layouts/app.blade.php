<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name', 'Denti'))</title>
    <link rel="shortcut icon" href="{{ asset('ui-kit/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('ui-kit/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('ui-kit/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @php
        $hasViteManifest = file_exists(public_path('build/manifest.json'));
    @endphp
    @if ($hasViteManifest && ! app()->runningUnitTests())
        @vite(['resources/css/app.css'])
    @endif
    @stack('styles')
</head>
<body id="kt_body" 
    class="app-metronic-match aside-enabled"
    data-kt-app-layout="light-sidebar" 
    data-kt-app-header-fixed="false" 
    data-kt-app-sidebar-enabled="true" 
    data-kt-app-sidebar-fixed="false" 
    data-kt-app-sidebar-hoverable="true" 
    data-kt-app-sidebar-push-header="false" 
    data-kt-app-sidebar-push-toolbar="false" 
    data-kt-app-sidebar-push-footer="false"
>

    <script>
        var defaultThemeMode = "light";
        var themeMode = localStorage.getItem("data-bs-theme") || defaultThemeMode;
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    </script>

    <div class="d-flex flex-column flex-root">
        <!-- Navbar (Header) - Full Width -->
        @include('layouts.partials.header')

        <div class="page d-flex flex-row flex-column-fluid mt-20">
            
            <div id="kt_aside" class="aside aside-light" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'265px', '300px': '265px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
                @include('layouts.partials.sidebar')
            </div>

            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper" style="padding: 20px 20px 20px 10px;">
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
    <script src="{{ asset('ui-kit/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('ui-kit/js/scripts.bundle.js') }}"></script>
    <script>
        window.DentiUI = window.DentiUI || {
            debug: true,
            components: [],
            registerComponent(name, element, meta) {
                const entry = {
                    name,
                    meta: meta || {},
                    element: element || null,
                    timestamp: new Date().toISOString(),
                };

                this.components.push(entry);

                if (this.debug) {
                    console.debug('[DentiUI]', name, entry.meta, element || null);
                }

                return entry;
            },
            inspectComponents() {
                console.table(this.components.map((component, index) => ({
                    index: index + 1,
                    name: component.name,
                    timestamp: component.timestamp,
                    meta: JSON.stringify(component.meta),
                })));
            },
            init(root) {
                const scope = root || document;

                if (window.jQuery && $.fn.select2) {
                    const select2Elements = $(scope).find('[data-control="select2"]');
                    select2Elements.each(function () {
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

                    if (select2Elements.length) {
                        this.registerComponent('select2', scope, { count: select2Elements.length });
                    }
                }

                const tooltipTriggerList = [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach((element) => {
                    bootstrap.Tooltip.getOrCreateInstance(element);
                });

                if (tooltipTriggerList.length) {
                    this.registerComponent('tooltip', scope, { count: tooltipTriggerList.length });
                }
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
            parseApiError(xhr) {
                const payload = xhr?.responseJSON || {};
                const status = xhr?.status || 0;

                if (status === 401) {
                    return { type: 'auth', message: 'Oturum gecersiz. Lutfen tekrar giris yapin.', errors: null };
                }

                if (status === 403) {
                    return { type: 'forbidden', message: payload.message || 'Bu islem icin yetkiniz yok.', errors: null };
                }

                if (status === 422 && payload.errors) {
                    return { type: 'validation', message: payload.message || 'Dogrulama hatasi.', errors: payload.errors };
                }

                return { type: 'generic', message: payload.message || 'Islem sirasinda bir hata olustu.', errors: payload.errors || null };
            },
            handleApiError(xhr) {
                const parsed = this.parseApiError(xhr);
                if (parsed.type === 'validation' && parsed.errors) {
                    this.showValidationErrors(parsed.errors);
                    return;
                }
                this.notify('error', parsed.message);
            },
            createModule(config) {
                if (!window.jQuery) {
                    console.error('jQuery bulunamadi, modül baslatilamadi.', config);
                    return null;
                }

                const settings = $.extend({
                    root: null,
                    name: 'module',
                    indexUrl: window.location.pathname,
                    tableContainer: '[data-module-table]',
                    modalHost: null,
                    filterForm: '.app-module-toolbar form',
                    searchInput: 'input[name="search"]',
                    filterChangeSelector: 'select, input[type="date"], input[type="checkbox"]',
                    createSelector: '[data-module-create]',
                    editSelector: '[data-module-edit]',
                    actionFormSelector: '[data-module-action]',
                    cleanUrl: null,
                    onAfterLoad: null,
                    onModalLoaded: null,
                    initialLoad: true,
                }, config || {});

                const $root = $(settings.root);
                if (!$root.length) {
                    return null;
                }

                const $tableContainer = $root.find(settings.tableContainer).first();
                const $filterForm = $root.find(settings.filterForm).first();
                const $modalHost = settings.modalHost ? $(settings.modalHost) : $();
                const cleanUrl = settings.cleanUrl || settings.indexUrl;
                let currentUrl = settings.indexUrl;
                let modalInstance = null;
                let searchTimer = null;
                let currentTableRequest = null;
                let currentModalRequest = null;

                const api = {
                    reload(resetUrl) {
                        loadData(resetUrl !== false);
                    },
                    open(url) {
                        openRemote(url);
                    },
                };

                window.DentiUI.registerComponent('module:' + settings.name, $root[0], {
                    indexUrl: settings.indexUrl,
                });

                function applyResponse(response) {
                    if (response.tableHtml && $tableContainer.length) {
                        $tableContainer.html(response.tableHtml);
                        window.DentiUI.init($tableContainer[0]);
                    }

                    if (typeof settings.onAfterLoad === 'function') {
                        settings.onAfterLoad(response, $root);
                    }
                }

                function loadData(resetUrl) {
                    if (resetUrl) {
                        currentUrl = settings.indexUrl;
                    }

                    const query = $filterForm.length ? $filterForm.serialize() : '';
                    const targetUrl = currentUrl + (query ? (currentUrl.indexOf('?') === -1 ? '?' : '&') + query : '');

                    $tableContainer.css('opacity', '0.5');

                    if (currentTableRequest && currentTableRequest.readyState !== 4) {
                        currentTableRequest.abort();
                    }
                    currentTableRequest = $.ajax({
                        url: targetUrl,
                        type: 'GET',
                        dataType: 'json',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success(response) {
                            applyResponse(response);
                            $tableContainer.css('opacity', '1');
                        },
                        error(xhr) {
                            if (window.DentiUI.debug) console.error('[DentiUI] AJAX Error:', xhr);
                            window.DentiUI.handleApiError(xhr);
                            $tableContainer.css('opacity', '1');
                        }
                    });
                }

                function clearModal() {
                    if (!$modalHost.length) {
                        return;
                    }

                    $modalHost.empty();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
                    modalInstance = null;
                }

                function showModalMarkup(markup) {
                    if (!$modalHost.length || !markup) {
                        return;
                    }

                    clearModal();
                    $modalHost.html(markup);

                    // Try to find a modal inside the host (for full modal markups)
                    let modalEl = $modalHost.find('.modal').first();
                    
                    // If not found, check if the host itself is inside a modal (for partial content markups)
                    if (!modalEl.length) {
                        modalEl = $modalHost.closest('.modal');
                    }

                    if (!modalEl.length) {
                        if (window.DentiUI.debug) console.warn('[DentiUI] Modal element not found for markup.');
                        return;
                    }

                    window.DentiUI.init(modalEl[0]);
                    window.DentiUI.registerComponent('modal:' + settings.name, modalEl[0], {
                        id: modalEl.attr('id') || null,
                    });

                    modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl[0]);
                    modalInstance.show();

                    modalEl.on('hidden.bs.modal', function () {
                        clearModal();
                        window.history.replaceState({}, document.title, cleanUrl);
                    });

                    if (typeof settings.onModalLoaded === 'function') {
                        settings.onModalLoaded(modalEl, $root);
                    }
                }

                function openRemote(url) {
                    if (currentModalRequest && currentModalRequest.readyState !== 4) {
                        currentModalRequest.abort();
                    }
                    currentModalRequest = $.ajax({
                        url: url,
                        type: 'GET',
                        dataType: 'json',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success(response) {
                            if (response.tableHtml) {
                                applyResponse(response);
                            }

                            if (response.modalHtml) {
                                showModalMarkup(response.modalHtml);
                            }
                        },
                        error(xhr) {
                            if (window.DentiUI.debug) console.error('[DentiUI] Modal AJAX Error:', xhr);
                            window.DentiUI.handleApiError(xhr);
                        }
                    });
                }

                $filterForm.on('submit', function (event) {
                    event.preventDefault();
                    loadData(true);
                });

                $filterForm.on('change', settings.filterChangeSelector, function () {
                    loadData(true);
                });

                $filterForm.on('keyup', settings.searchInput, function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () {
                        loadData(true);
                    }, 400);
                });

                $root.on('change', '[data-control="per-page-selector"]', function () {
                    loadData(true);
                });

                $root.on('click', settings.createSelector + ',' + settings.editSelector, function (event) {
                    event.preventDefault();
                    openRemote($(this).attr('href'));
                });

                $root.on('click', settings.tableContainer + ' .pagination a', function (event) {
                    event.preventDefault();
                    currentUrl = $(this).attr('href');
                    loadData(false);
                });

                $root.on('submit', settings.actionFormSelector, function (event) {
                    event.preventDefault();
                    const $form = $(this);
                    const method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();
                    const $submit = $form.find('button[type="submit"]').first();

                    $submit.attr('data-kt-indicator', 'on').prop('disabled', true);

                    $.ajax({
                        url: $form.attr('action'),
                        type: method,
                        data: $form.serialize(),
                        success(response) {
                            window.DentiUI.notify('success', response.message || 'Islem basarili.');
                            loadData(false);
                        },
                        error(xhr) {
                            if (window.DentiUI.debug) console.error('[DentiUI] Action AJAX Error:', xhr);
                            window.DentiUI.handleApiError(xhr);
                        },
                        complete() {
                            $submit.removeAttr('data-kt-indicator').prop('disabled', false);
                        }
                    });
                });

                $(document).on('submit', settings.modalHost + ' form', function (event) {
                    if (!$(this).closest(settings.modalHost).length) {
                        return;
                    }

                    event.preventDefault();

                    const $form = $(this);
                    const method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();
                    const $submit = $form.find('button[type="submit"]').first();

                    $submit.attr('data-kt-indicator', 'on').prop('disabled', true);

                    $.ajax({
                        url: $form.attr('action'),
                        type: method,
                        data: $form.serialize(),
                        success(response) {
                            if (modalInstance) {
                                modalInstance.hide();
                            } else {
                                clearModal();
                            }

                            window.DentiUI.notify('success', response.message || 'Kayit basarili.');
                            loadData(false);
                        },
                        error(xhr) {
                            if (window.DentiUI.debug) console.error('[DentiUI] Modal Submit AJAX Error:', xhr);
                            window.DentiUI.handleApiError(xhr);
                        },
                        complete() {
                            $submit.removeAttr('data-kt-indicator').prop('disabled', false);
                        }
                    });
                });

                if ($modalHost.find('.modal').length) {
                    showModalMarkup($modalHost.html());
                }

                if (settings.initialLoad !== false) {
                    loadData(true);
                }

                return api;
            },
        };

        document.addEventListener('DOMContentLoaded', function () {
            document.body.removeAttribute('data-kt-aside-minimize');
            window.DentiUI.init(document);
        });
    </script>
    @stack('scripts')
</body>
</html>
