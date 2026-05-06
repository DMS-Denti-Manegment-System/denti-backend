<div id="kt_header" class="header">
    <!--begin::Container-->
        <div class="container-fluid d-flex flex-stack">		
        <!--begin::Brand-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Aside toggle-->
            <div class="d-lg-none btn btn-icon btn-active-color-white w-30px h-30px ms-n2 me-3" id="kt_aside_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2"><span class="path1"></span><span class="path2"></span></i>
            </div>
            <!--end::Aside  toggle-->

            <div class="d-flex align-items-center d-lg-none">
                <a href="{{ route('dashboard') }}">
                    <img alt="Logo" src="{{ asset('ui-kit/media/logos/default-small.svg') }}" class="h-25px h-lg-30px">
                </a>
            </div>
        </div>
        <!--end::Brand-->		 

        <!--begin::Topbar-->
        <div class="d-flex align-items-center flex-shrink-0">
            <div class="d-flex align-items-center ms-1">
                <a href="{{ route('stocks.index') }}" class="btn btn-icon btn-color-white bg-hover-white bg-hover-opacity-10 w-30px h-30px h-40px w-40px" aria-label="Stok listesine git" title="Stok listesi">
                    <i class="ki-duotone ki-magnifier fs-1"><span class="path1"></span><span class="path2"></span></i>
                </a>
            </div>

            <!--begin::Theme mode-->
            <div class="d-flex align-items-center ms-1">
                <a href="#" class="btn btn-icon btn-color-white bg-hover-white bg-hover-opacity-10 w-30px h-30px h-40px w-40px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-night-day theme-light-show fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i>
                    <i class="ki-duotone ki-moon theme-dark-show fs-1"><span class="path1"></span><span class="path2"></span></i>
                </a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                            <span class="menu-icon"><i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i></span>
                            <span class="menu-title">Aydınlık</span>
                        </a>
                    </div>
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                            <span class="menu-icon"><i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i></span>
                            <span class="menu-title">Karanlık</span>
                        </a>
                    </div>
                </div>
            </div>
            <!--end::Theme mode-->

            <!--begin::User-->
            <div class="d-flex align-items-center ms-1" id="kt_header_user_menu_toggle">
                <!--begin::User info-->
                <div class="btn btn-flex align-items-center bg-hover-white bg-hover-opacity-10 py-2 px-2 px-md-3" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    
                    <!--begin::Name-->
                    <div class="d-none d-md-flex flex-column align-items-end justify-content-center me-2 me-md-4">
                        <span class="text-muted fs-8 fw-semibold lh-1 mb-1">{{ auth()->user()->name }}</span>
                        <span class="text-white fs-8 fw-bold lh-1">{{ auth()->user()->roles->first()->name ?? 'Personel' }}</span>
                    </div>
                    <!--end::Name-->

                    <!--begin::Symbol-->
                    <div class="symbol symbol-30px symbol-md-40px">
                        <div class="symbol-label fs-3 bg-primary text-white fw-bold">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    </div>
                    <!--end::Symbol-->
                </div>
                <!--end::User info-->

                <!--begin::User account menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <div class="symbol symbol-50px me-5">
                                <div class="symbol-label fs-2 bg-light-primary text-primary fw-bold">{{ substr(auth()->user()->name, 0, 1) }}</div>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name }}</div>
                                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="separator my-2"></div>
                    <div class="menu-item px-5"><a href="{{ route('profile.index') }}" class="menu-link px-5">Profilim</a></div>
                    <div class="menu-item px-5">
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-link menu-link px-5 w-100 text-start border-0">Çıkış Yap</button></form>
                    </div>
                </div>
                <!--end::User account menu-->
            </div>
            <!--end::User -->
        </div>
        <!--end::Topbar-->	
    </div>
    <!--end::Container-->
</div>
