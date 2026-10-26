<div class="navbar navbar-expand-lg navbar-dark" style="background: rgba(17, 24, 39, 0.94); border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="text-center d-lg-none w-100">
        <button type="button" class="navbar-toggler dropdown-toggle" data-toggle="collapse" data-target="#navbar-footer">
            <i class="icon-unfold mr-2"></i>
            {{ __('Legal & Policies') }}
        </button>
    </div>

    <div class="navbar-collapse collapse" id="navbar-footer">
        <span class="navbar-text text-muted font-size-xs">
            &copy; {{ date('Y') }} <strong class="text-white">{{ Qs::getSystemName() }}</strong> &bull; {{ __('Engineered by') }} <span style="color: #FF5700; font-weight: 600;">SMS Academy</span>
        </span>

        <ul class="navbar-nav ml-lg-auto">
            <li class="nav-item">
                <a href="{{ route('privacy_policy') }}" class="navbar-nav-link text-muted font-size-xs" target="_blank">
                    <i class="icon-lifebuoy mr-1"></i> {{ __('Privacy Policy') }}
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('terms_of_use') }}" class="navbar-nav-link text-muted font-size-xs" target="_blank">
                    <i class="icon-file-text2 mr-1"></i> {{ __('Terms of Use') }}
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- PWA Scripts and Configuration --}}
<script>
    window.PWA_SW_PATH = "{{ route('pwa.sw') }}";
    window.PWA_USER_ID = null;
    window.PWA_ICON_PATH = "{{ asset('assets/images/icons/icon-192x192.png') }}";
    window.PWA_APP_NAME = "{{ Qs::getSystemName() }}";
    window.PWA_I18N_UPDATE = "{{ __('A new version of the school portal is ready.') }}";
    window.PWA_I18N_UPDATE_BTN = "{{ __('Update Now') }}";
    window.PWA_I18N_INSTALL_DESC = "{{ __('Install school portal for quick and reliable access') }}";
    window.PWA_I18N_INSTALL_BTN = "{{ __('Install App') }}";
    window.PWA_I18N_ONLINE = "{{ __('Connection Restored') }}";
    window.PWA_I18N_OFFLINE = "{{ __('Offline Mode - Live actions require connection') }}";
</script>
<script src="{{ asset('assets/js/pwa.js') }}"></script>
