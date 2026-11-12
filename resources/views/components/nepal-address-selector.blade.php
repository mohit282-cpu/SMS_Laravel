@props([
    'prefix' => '',
    'provinceId' => null,
    'districtId' => null,
    'localLevelId' => null,
    'wardNo' => null,
    'tole' => null,
    'street' => null,
    'provinces' => null,
    'required' => false,
])

@php
    $pField = $prefix ? "{$prefix}_province_id" : 'province_id';
    $dField = $prefix ? "{$prefix}_district_id" : 'district_id';
    $lField = $prefix ? "{$prefix}_local_level_id" : 'local_level_id';
    $wField = $prefix ? "{$prefix}_ward_no" : 'ward_no';
    $tField = $prefix ? "{$prefix}_tole" : 'tole';
    $sField = $prefix ? "{$prefix}_street" : 'street';
    $uid = 'nepal_addr_' . ($prefix ?: 'main');
    $provinceList = $provinces ?? \App\Models\NepalProvince::where('is_active', true)->orderBy('id')->get();
@endphp

<div class="row sms-nepal-address-group" id="{{ $uid }}">
    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_country">{{ __('Country') }}</label>
        <input type="text" id="{{ $uid }}_country" class="form-control" value="Nepal (नेपाल)" readonly>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_province">{{ __('Province (प्रदेश)') }}@if($required) <span class="text-danger">*</span>@endif</label>
        <select name="{{ $pField }}" id="{{ $uid }}_province" class="select-search form-control sms-nepal-province" data-placeholder="{{ __('Select Province') }}" data-target-district="#{{ $uid }}_district" @if($required) required @endif>
            <option value="">{{ __('Select Province') }}</option>
            @foreach($provinceList as $prov)
                <option value="{{ $prov->id }}" {{ (string) old($pField, $provinceId) === (string) $prov->id ? 'selected' : '' }}>
                    {{ $prov->name }} / {{ $prov->name_ne }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_district">{{ __('District (जिल्ला)') }}@if($required) <span class="text-danger">*</span>@endif</label>
        <select name="{{ $dField }}" id="{{ $uid }}_district" class="select-search form-control sms-nepal-district" data-placeholder="{{ __('Select District') }}" data-target-local="#{{ $uid }}_local" data-selected="{{ old($dField, $districtId) }}" @if($required) required @endif>
            <option value="">{{ __('Select District') }}</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_local">{{ __('Local Government (स्थानीय तह)') }}@if($required) <span class="text-danger">*</span>@endif</label>
        <select name="{{ $lField }}" id="{{ $uid }}_local" class="select-search form-control sms-nepal-local" data-placeholder="{{ __('Select Local Government') }}" data-target-ward="#{{ $uid }}_ward" data-selected="{{ old($lField, $localLevelId) }}" @if($required) required @endif>
            <option value="">{{ __('Select Local Government') }}</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_ward">{{ __('Ward No. (वडा नं.)') }}</label>
        <select name="{{ $wField }}" id="{{ $uid }}_ward" class="select-search form-control sms-nepal-ward" data-placeholder="{{ __('Select Ward') }}" data-selected="{{ old($wField, $wardNo) }}">
            <option value="">{{ __('Select Ward') }}</option>
            @for($w = 1; $w <= 25; $w++)
                <option value="{{ $w }}" {{ (string) old($wField, $wardNo) === (string) $w ? 'selected' : '' }}>
                    {{ __('Ward') }} {{ $w }} / वडा नं. {{ \App\Helpers\NepaliHelper::toDevanagari((string) $w) }}
                </option>
            @endfor
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_tole">{{ __('Tole / Village / Settlement') }}</label>
        <input type="text" name="{{ $tField }}" id="{{ $uid }}_tole" class="form-control" value="{{ old($tField, $tole) }}" placeholder="{{ __('e.g. Patan Dhoka / टोल') }}">
    </div>

    <div class="col-12 mb-3">
        <label for="{{ $uid }}_street">{{ __('Street / Landmark Address') }}</label>
        <input type="text" name="{{ $sField }}" id="{{ $uid }}_street" class="form-control" value="{{ old($sField, $street) }}" placeholder="{{ __('Street name, house no., or landmark') }}">
    </div>
</div>

