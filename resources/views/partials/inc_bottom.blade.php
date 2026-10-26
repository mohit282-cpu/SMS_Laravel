<!-- Theme JS files -->
<script src="{{ asset('global_assets/js/plugins/extensions/jquery_ui/interactions.min.js') }} "></script>
<script src="{{ asset('global_assets/js/plugins/forms/selects/select2.min.js') }} "></script>

{{--Forms--}}
<script src="{{ asset('global_assets/js/plugins/forms/wizards/steps.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/forms/inputs/inputmask.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/forms/validation/validate.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/extensions/cookie.js') }}"></script>

{{--Notify--}}
<script type="text/javascript" src="{{ asset('global_assets/js/plugins/notifications/sweet_alert2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('global_assets/js/plugins/notifications/pnotify.min.js') }}"></script>

{{--DataTables--}}
<script src="{{ asset('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/tables/datatables/extensions/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/tables/datatables/extensions/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/tables/datatables/extensions/pdfmake/vfs_fonts.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/tables/datatables/extensions/buttons.min.js') }}"></script>

{{--Date Pickers--}}
<script src="{{ asset('global_assets/js/plugins/ui/moment/moment.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/pickers/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('global_assets/js/plugins/pickers/pickadate/legacy.js') }}"></script>

{{--Uploaders--}}
<script src="{{ asset('global_assets/js/plugins/uploaders/fileinput/fileinput.min.js') }}"></script>

{{--Calendar--}}
<script src="{{ asset('global_assets/js/plugins/ui/fullcalendar/fullcalendar.min.js') }}"></script>


<script src=" {{ asset('assets/js/app.js') }} "></script>
<script src="{{ asset('global_assets/js/demo_pages/form_wizard.js') }}"></script>
<script src="{{ asset('global_assets/js/demo_pages/form_select2.js') }}"></script>
<script src="{{ asset('global_assets/js/demo_pages/datatables_extension_buttons_html5.js') }}"></script>
<script src="{{ asset('global_assets/js/demo_pages/uploader_bootstrap.js') }}"></script>
<script src="{{ asset('global_assets/js/demo_pages/fullcalendar_basic.js') }}"></script>

<!-- /theme JS files -->

<script src=" {{ asset('assets/js/custom.js') }} "></script>
<script src=" {{ asset('assets/js/nepali_datepicker.js') }} "></script>
<script src=" {{ asset('assets/js/nepali_bs_calendar.js') }} "></script>

{{-- PWA Scripts and Configuration --}}
<script>
    window.PWA_SW_PATH = "{{ route('pwa.sw') }}";
    window.PWA_USER_ID = {{ Auth::check() ? Auth::id() : 'null' }};
    window.PWA_ATTENDANCE_SYNC_URL = "{{ route('attendance.sync') }}";
    window.PWA_ICON_PATH = "{{ asset('assets/images/icons/icon-192x192.png') }}";
    window.PWA_APP_NAME = "{{ Qs::getSystemName() }}";
    window.PWA_I18N_UPDATE = "{{ __('A new version of the school portal is ready.') }}";
    window.PWA_I18N_UPDATE_BTN = "{{ __('Update Now') }}";
    window.PWA_I18N_INSTALL_DESC = "{{ __('Install school portal for quick and reliable access') }}";
    window.PWA_I18N_INSTALL_BTN = "{{ __('Install App') }}";
    window.PWA_I18N_ONLINE = "{{ __('Connection Restored') }}";
    window.PWA_I18N_OFFLINE = "{{ __('Offline Mode - Live actions require connection') }}";
    window.PWA_VAPID_PUBLIC_KEY = "{{ config('services.pwa.vapid_public_key', '') }}";
    window.PWA_VAPID_PUBLIC_KEY_URL = "{{ route('pwa.push.vapid') }}";
    window.PWA_PUSH_SUBSCRIBE_URL = "{{ route('pwa.push.subscribe') }}";
    window.PWA_PUSH_UNSUBSCRIBE_URL = "{{ route('pwa.push.unsubscribe') }}";
    window.PWA_PUSH_TEST_URL = "{{ route('pwa.push.test') }}";
</script>
<script src="{{ asset('assets/js/pwa.js') }}"></script>

@include('partials.js.custom_js')