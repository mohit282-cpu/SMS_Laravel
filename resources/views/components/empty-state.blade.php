@props([
    'title' => __('No records found'),
    'message' => __('There is currently no data to display here.'),
    'icon' => 'icon-folder-open',
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'sms-empty-state']) }}>
    <div class="sms-empty-state-icon" aria-hidden="true">
        <i class="{{ $icon }}"></i>
    </div>
    <h5 class="mb-1 font-weight-semibold">{{ $title }}</h5>
    <p class="text-muted mb-3 mx-auto" style="max-width: 420px;">{{ $message }}</p>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">{{ $actionLabel }}</a>
    @endif
    {{ $slot }}
</div>
