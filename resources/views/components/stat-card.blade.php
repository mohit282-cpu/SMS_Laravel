@props([
    'label',
    'value',
    'icon' => 'icon-stats-bars2',
    'variant' => 'orange',
    'href' => null,
    'subtitle' => null,
])

@if($href)
<a href="{{ $href }}" class="text-decoration-none d-block">
@endif
    <div {{ $attributes->merge(['class' => 'sms-stat-card']) }}>
        <div>
            <div class="stat-number">{{ $value }}</div>
            <div class="stat-label">{{ $label }}</div>
            @if($subtitle)
                <div class="text-muted mt-1" style="font-size: 0.74rem;">{{ $subtitle }}</div>
            @endif
        </div>
        <div class="stat-icon-wrapper stat-icon-{{ $variant }}" aria-hidden="true">
            <i class="{{ $icon }}"></i>
        </div>
    </div>
@if($href)
</a>
@endif

