@props([
    'id' => 'sms-confirm-modal',
    'title' => __('Confirm Action'),
    'message' => __('Are you sure you want to continue?'),
    'confirmLabel' => __('Confirm'),
    'cancelLabel' => __('Cancel'),
    'variant' => 'danger',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-semibold" id="{{ $id }}-title">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-muted">{{ $message }}</p>
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ $cancelLabel }}</button>
                @if(isset($action))
                    {{ $action }}
                @else
                    <button type="button" class="btn btn-{{ $variant }} sms-confirm-submit">{{ $confirmLabel }}</button>
                @endif
            </div>
        </div>
    </div>
</div>
