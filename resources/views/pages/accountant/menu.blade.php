{{-- Accountant ERP Navigation Menu --}}
<li class="nav-item">
    <a href="{{ route('accounting.index') }}" class="nav-link {{ Route::is('accounting.*') ? 'active' : '' }}">
        <i class="icon-coins"></i> <span>{{ __('Accounting & Day Book') }}</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('payroll.index') }}" class="nav-link {{ Route::is('payroll.*') ? 'active' : '' }}">
        <i class="icon-cash3"></i> <span>{{ __('HR & Payroll') }}</span>
    </a>
</li>
