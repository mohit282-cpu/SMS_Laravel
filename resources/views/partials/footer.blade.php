<footer class="sms-footer d-flex flex-column flex-sm-row align-items-center justify-content-between" role="contentinfo">
    <div class="mb-1 mb-sm-0">
        <span class="font-weight-semibold text-dark">{{ Qs::getSystemName() ?: 'SMS Academy' }}</span>
        <span class="mx-1 text-muted">&bull;</span>
        <span>{{ __('Smart School Management. Simplified.') }}</span>
    </div>
    <div>
        <span>&copy; {{ date('Y') }} {{ __('Powered by') }} <strong>Sovryx Tech Pvt. Ltd.</strong></span>
    </div>
</footer>

