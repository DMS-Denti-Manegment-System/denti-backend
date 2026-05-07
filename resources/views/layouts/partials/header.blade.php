<style>
    body {
        background-color: #f2f4f7 !important;
        /* Distinct light gray background */
        background-image: none !important;
        /* Remove any gradients */
    }

    /* Extremely aggressive reset to kill ghost lines and double backgrounds */
    .page,
    .wrapper,
    .flex-root,
    .aside,
    .aside-light,
    .aside-enabled .aside,
    #kt_aside,
    #kt_wrapper,
    .toolbar,
    .post,
    .footer,
    #kt_toolbar,
    #kt_post,
    #kt_footer,
    .container-fluid,
    .container-xxl,
    [data-kt-drawer-name="aside"],
    .content {
        background-color: transparent !important;
        background: none !important;
        border: 0 !important;
        border-right: 0 !important;
        border-left: 0 !important;
        box-shadow: none !important;
        outline: none !important;
        backdrop-filter: none !important;
    }

    #kt_header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: #ffffff;
        /* Navbar is now white */
        height: 70px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 15px 0 rgba(0, 0, 0, 0.05);
        /* Softer shadow for white navbar */
        border-bottom: 1px solid #eff2f5;
    }

    #kt_header .text-white {
        color: #181c32 !important;
        /* Force dark text in header */
    }

    #kt_header .text-muted {
        color: #7e8299 !important;
    }

    #kt_header .btn-color-white {
        color: #3f4254 !important;
    }

    #kt_header .bg-hover-white {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }
</style>
<div id="kt_header" class="header">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <!--begin::Brand-->
        <div class="d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="me-10 d-flex align-items-center text-decoration-none">
                <i class="fas fa-tooth fs-2 text-primary me-3"></i>
                <span class="fs-2 fw-bold text-dark" style="letter-spacing: -0.5px;">Denti</span>
            </a>

            <!--begin::Aside toggle-->
            <div class="d-lg-none btn btn-icon btn-active-color-white w-30px h-30px ms-n2 me-3"
                id="kt_aside_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2"><span class="path1"></span><span class="path2"></span></i>
            </div>
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


        <!--begin::User-->
        <div class="d-flex align-items-center ms-1" id="kt_header_user_menu_toggle">
            <!--begin::User info-->
            <div class="btn btn-flex align-items-center bg-transparent border-0 py-2 px-2 px-md-3"
                data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">

                <!--begin::Name-->
                <div class="d-none d-md-flex flex-column align-items-end justify-content-center me-2 me-md-4">
                    <span class="text-muted fs-8 fw-semibold lh-1 mb-1">{{ auth()->user()->name }}</span>
                    <span
                        class="text-dark fs-8 fw-bold lh-1">{{ auth()->user()->roles->first()->name ?? 'Personel' }}</span>
                </div>
                <!--end::Name-->

                <!--begin::Symbol-->
                <div class="symbol symbol-30px symbol-md-40px">
                    <div class="symbol-label fs-3 bg-primary text-white fw-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}</div>
                </div>
                <!--end::Symbol-->
            </div>
            <!--end::User info-->

            <!--begin::User account menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                data-kt-menu="true">
                <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label fs-2 bg-light-primary text-primary fw-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}</div>
                        </div>
                        <div class="d-flex flex-column">
                            <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name }}</div>
                            <a href="#"
                                class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                        </div>
                    </div>
                </div>
                <div class="separator my-2"></div>
                <div class="menu-item px-5"><a href="{{ route('profile.index') }}" class="menu-link px-5">Profilim</a>
                </div>
                <div class="menu-item px-5">
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"
                            class="btn btn-link menu-link px-5 w-100 text-start border-0">Çıkış Yap</button></form>
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
