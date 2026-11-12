@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'card sms-card']) }}>
    @if($title || isset($actions))
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                @if($title)
                    <h6 class="card-title d-flex align-items-center mb-0">
                        @if($icon)
                            <i class="{{ $icon }} mr-2 text-muted" aria-hidden="true"></i>
                        @endif
                        <span>{{ $title }}</span>
                    </h6>
                @endif
                @if($subtitle)
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">{{ $subtitle }}</div>
                @endif
            </div>
            @if(isset($actions))
                <div class="d-flex align-items-center flex-wrap">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
