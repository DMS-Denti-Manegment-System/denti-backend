<div class="aside-menu flex-column-fluid px-4">
    <!--begin::Aside Menu-->
    <div class="hover-scroll-overlay-y mh-100 my-5" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="{default: '#kt_aside_footer', lg: '#kt_header, #kt_aside_footer'}" data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu" data-kt-scroll-offset="{default: '5px', lg: '75px'}">
        <!--begin::Menu-->
        <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="kt_aside_menu" data-kt-menu="true">
            
            <!-- Dashboard -->
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-element-11 fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                        </i>
                    </span>
                    <span class="menu-title">Gösterge Paneli</span>
                </a>
            </div>

            <!-- Envanter -->
            <div class="menu-item pt-5">
                <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Envanter</span></div>
            </div>

            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->is('stocks*') || request()->is('stock-categories*') || request()->is('suppliers*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-package fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Stok Yönetimi</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('stocks*') ? 'active' : '' }}" href="{{ url('/stocks') }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Stok Listesi</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('stock-categories*') ? 'active' : '' }}" href="{{ url('/stock-categories') }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Kategoriler</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('suppliers*') ? 'active' : '' }}" href="{{ url('/suppliers') }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Tedarikçiler</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Operasyonlar -->
            <div class="menu-item pt-5">
                <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Operasyonlar</span></div>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->is('clinics*') ? 'active' : '' }}" href="{{ url('/clinics') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-hospital fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Klinikler</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->is('stock-requests*') ? 'active' : '' }}" href="{{ url('/stock-requests') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-swap fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Stok Talepleri</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->is('alerts*') ? 'active' : '' }}" href="{{ url('/alerts') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-notification-on fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Uyarılar</span>
                </a>
            </div>

            <!-- Raporlar -->
            <div class="menu-item pt-5">
                <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Raporlama</span></div>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->is('reports*') ? 'active' : '' }}" href="{{ url('/reports') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-chart-line-star fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Raporlar</span>
                </a>
            </div>

            <!-- Sistem -->
            <div class="menu-item pt-5">
                <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Sistem</span></div>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->is('employees*') ? 'active' : '' }}" href="{{ url('/employees') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-people fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Personel</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-shield-search fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Yetkiler</span>
                </a>
            </div>

        </div>
        <!--end::Menu-->
    </div>
</div>
