<!-- Sidebar Ana Konteynırı -->
<style>
    .aside-menu-container {
        height: auto !important;
        max-height: calc(100vh - 140px);
        margin: 30px 10px 40px 20px;
        border-radius: 1.5rem;
        background: #fdfdfe;
        box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.03);
        border: none !important;
    }

    .menu-link .menu-title {
        color: #4b5675 !important;
    }

    .menu-link.active .menu-title {
        color: #009ef7 !important;
    }

    .menu-link.active {
        background-color: rgba(0, 158, 247, 0.05) !important;
    }

    .menu-heading {
        color: #a1a5b7 !important;
        font-weight: 600 !important;
    }

    #kt_aside {
        background: transparent !important;
        width: 250px !important;
        border: none !important;
        box-shadow: none !important;
    }
</style>
<div class="aside-menu-container flex-column-fluid px-4 py-5" id="kt_aside_menu_wrapper">
    @php
        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('Super Admin');
    @endphp

    <!-- Kaydırılabilir Alan -->
    <div class="hover-scroll-y" style="height: 100%;" data-kt-scroll="true"
        data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu"
        data-kt-scroll-offset="0">

        <!-- Menü Başlangıcı -->
        <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="kt_aside_menu"
            data-kt-menu="true">

            @if ($isSuperAdmin)
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}"
                        href="{{ route('admin.companies') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-abstract-26 fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Şirketler</span>
                    </a>
                </div>
            @else
            <!-- Dashboard -->
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-element-11 fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                class="path4"></span>
                        </i>
                    </span>
                    <span class="menu-title">Ana Sayfa</span>
                </a>
            </div>

            @can('view-stocks')
                <!-- Stoklar -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}"
                        href="{{ route('stocks.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-package fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title">Ürün Listesi</span>
                    </a>
                </div>

                <!-- Kategoriler -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                        href="{{ route('categories.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-category fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                    class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">Kategoriler</span>
                    </a>
                </div>

                <!-- Tedarikçiler -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
                        href="{{ route('suppliers.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-truck fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                    class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Tedarikçiler</span>
                    </a>
                </div>
            @endcan

            @can('view-clinics')
                <!-- Klinikler -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('clinics.*') ? 'active' : '' }}"
                        href="{{ route('clinics.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-bank fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Klinikler</span>
                    </a>
                </div>
            @endcan

            @canany(['view-stocks', 'transfer-stocks', 'approve-transfers', 'cancel-transfers'])
                <!-- Stok Talepleri -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}"
                        href="{{ route('stock-requests.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-document fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Stok Talepleri</span>
                    </a>
                </div>
            @endcanany

            @can('view-stocks')
                <!-- Uyarılar -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}"
                        href="{{ route('alerts.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-notification-on fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                    class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Uyarılar</span>
                    </a>
                </div>
            @endcan

            @can('view-todos')
                <!-- Yapılacaklar -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('todos.*') ? 'active' : '' }}"
                        href="{{ route('todos.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-check-square fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Yapılacaklar</span>
                    </a>
                </div>
            @endcan

            @can('view-reports')
                <!-- Raporlar -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-chart-line-star fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title">Raporlar</span>
                    </a>
                </div>
            @endcan

            @can('manage-users')
                <!-- Personel -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
                        href="{{ route('employees.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-people fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                    class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Personel</span>
                    </a>
                </div>
            @endcan
            @endif


        </div>
    </div>
</div>
