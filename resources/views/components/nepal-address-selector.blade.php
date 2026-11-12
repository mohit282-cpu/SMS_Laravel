@props([
    'prefix' => '',
    'provinceId' => null,
    'districtId' => null,
    'localLevelId' => null,
    'wardNo' => null,
    'tole' => null,
    'street' => null,
    'provinces' => null,
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
        <label for="{{ $uid }}_province">{{ __('Province (प्रदेश)') }}</label>
        <select name="{{ $pField }}" id="{{ $uid }}_province" class="form-control sms-nepal-province" data-target-district="#{{ $uid }}_district">
            <option value="">{{ __('Select Province') }}</option>
            @foreach($provinceList as $prov)
                <option value="{{ $prov->id }}" {{ (string) old($pField, $provinceId) === (string) $prov->id ? 'selected' : '' }}>
                    {{ $prov->name }} / {{ $prov->name_ne }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_district">{{ __('District (जिल्ला)') }}</label>
        <select name="{{ $dField }}" id="{{ $uid }}_district" class="form-control sms-nepal-district" data-target-local="#{{ $uid }}_local" data-selected="{{ old($dField, $districtId) }}">
            <option value="">{{ __('Select District') }}</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_local">{{ __('Local Government (स्थानीय तह)') }}</label>
        <select name="{{ $lField }}" id="{{ $uid }}_local" class="form-control sms-nepal-local" data-target-ward="#{{ $uid }}_ward" data-selected="{{ old($lField, $localLevelId) }}">
            <option value="">{{ __('Select Local Government') }}</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $uid }}_ward">{{ __('Ward No. (वडा नं.)') }}</label>
        <select name="{{ $wField }}" id="{{ $uid }}_ward" class="form-control sms-nepal-ward" data-selected="{{ old($wField, $wardNo) }}">
            <option value="">{{ __('Select Ward') }}</option>
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

