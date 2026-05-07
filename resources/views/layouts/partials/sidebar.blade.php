<div class="aside-menu flex-column-fluid px-4">
    <!--begin::Aside Menu-->
    <div class="aside-menu-scroll" id="kt_aside_menu_wrapper">
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

            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('stocks.*') || request()->routeIs('categories.*') || request()->routeIs('suppliers.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-package fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Ürün Yönetimi</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}" href="{{ route('stocks.index') }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Ürün Listesi</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Kategoriler</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
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
                <a class="menu-link {{ request()->routeIs('clinics.*') ? 'active' : '' }}" href="{{ route('clinics.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-hospital fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Klinikler</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}" href="{{ route('stock-requests.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-swap fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                    <span class="menu-title">Stok Talepleri</span>
                </a>
            </div>


            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-notification-on fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Uyarılar</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('todos.*') ? 'active' : '' }}" href="{{ route('todos.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-check-square fs-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                    <span class="menu-title">Yapılacaklar</span>
                </a>
            </div>

            <!-- Raporlar -->
            <div class="menu-item pt-5">
                <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Raporlama</span></div>
            </div>

            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
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
                <a class="menu-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-people fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                    </span>
                    <span class="menu-title">Personel</span>
                </a>
            </div>



        </div>
        <!--end::Menu-->
    </div>
</div>
