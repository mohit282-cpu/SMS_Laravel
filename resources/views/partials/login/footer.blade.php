<div class="navbar navbar-expand-lg navbar-light">
    <div class="text-center d-lg-none w-100">
        <button type="button" class="navbar-toggler dropdown-toggle" data-toggle="collapse" data-target="#navbar-footer">
            <i class="icon-unfold mr-2"></i>
            More Links
        </button>
    </div>

    <div class="navbar-collapse collapse" id="navbar-footer">
					<span class="navbar-text">
						&copy; {{ date('Y') }}. <a href="#">{{ Qs::getSystemName() }}</a> by <a href="#" >SMS Academy</a>
					</span>

        <ul class="navbar-nav ml-lg-auto">
            <li class="nav-item"><a href="{{ route('privacy_policy') }}" class="navbar-nav-link" target="_blank"><i class="icon-lifebuoy mr-2"></i> Privacy Policy</a></li>
            <li class="nav-item"><a href="{{ route('terms_of_use') }}" class="navbar-nav-link" target="_blank"><i class="icon-file-text2 mr-2"></i> Terms of Use </a></li>
{{--            <li class="nav-item"><a href="#" class="navbar-nav-link font-weight-semibold"><span class="text-pink-400"><i class="icon-phone mr-2"></i> Contact Us</span></a></li>--}}
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
