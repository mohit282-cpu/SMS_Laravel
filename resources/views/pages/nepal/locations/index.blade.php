@extends('layouts.master')
@section('page_title', __('Nepal Administrative Locations'))
@section('content')

<div class="row mb-3">
    <div class="col-md-3 col-6 mb-2">
        <div class="card sms-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Provinces (प्रदेश)') }}</div>
                <div class="h4 mb-0">{{ $report['provinces']['actual'] }} / {{ $report['provinces']['expected'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card sms-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Districts (जिल्ला)') }}</div>
                <div class="h4 mb-0">{{ $report['districts']['actual'] }} / {{ $report['districts']['expected'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card sms-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Local Governments (स्थानीय तह)') }}</div>
                <div class="h4 mb-0">{{ $report['local_levels']['actual'] }} / {{ $report['local_levels']['expected'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card sms-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Dataset Status & Wards') }}</div>
                <div class="h5 mb-1">{{ number_format($report['total_wards']['actual']) }} / {{ number_format($report['total_wards']['expected']) }} {{ __('Wards') }}</div>
                <div class="h6 mb-0">
                    @if($report['is_complete_official_dataset'])
                        <span class="badge badge-success">{{ __('Verified Complete (7/77/753)') }}</span>
                    @else
                        <span class="badge badge-warning">{{ __('Partial Dataset') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card sms-card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title">{{ __('Nepal Administrative Location Directory') }}</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('nepal_locations.manage') }}" class="row mb-4">
            <div class="col-md-3 mb-2">
                <label for="filter_province_id">{{ __('Province (प्रदेश)') }}</label>
                <select name="province_id" id="filter_province_id" class="form-control select-search">
                    <option value="">{{ __('All Provinces') }}</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ (string) $provinceId === (string) $prov->id ? 'selected' : '' }}>
                            {{ $prov->name }} / {{ $prov->name_ne }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label for="filter_district_id">{{ __('District (जिल्ला)') }}</label>
                <select name="district_id" id="filter_district_id" class="form-control select-search">
                    <option value="">{{ __('All Districts') }}</option>
                    @foreach($districts as $dist)
                        <option value="{{ $dist->id }}" {{ (string) $districtId === (string) $dist->id ? 'selected' : '' }}>
                            {{ $dist->name }} / {{ $dist->name_ne }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label for="filter_level_type">{{ __('Type') }}</label>
                <select name="level_type" id="filter_level_type" class="form-control">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="metropolitan" {{ $levelType === 'metropolitan' ? 'selected' : '' }}>{{ __('Metropolitan City') }}</option>
                    <option value="sub_metropolitan" {{ $levelType === 'sub_metropolitan' ? 'selected' : '' }}>{{ __('Sub-Metropolitan City') }}</option>
                    <option value="municipality" {{ $levelType === 'municipality' ? 'selected' : '' }}>{{ __('Municipality') }}</option>
                    <option value="rural_municipality" {{ $levelType === 'rural_municipality' ? 'selected' : '' }}>{{ __('Rural Municipality') }}</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label for="filter_q">{{ __('Search (EN / नेपाली)') }}</label>
                <input type="text" name="q" id="filter_q" value="{{ $q }}" class="form-control" placeholder="{{ __('Name or code...') }}">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary mr-2">{{ __('Filter') }}</button>
                <a href="{{ route('nepal_locations.manage') }}" class="btn btn-light">{{ __('Reset') }}</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Local Government (EN)') }}</th>
                        <th>{{ __('नेपाली नाम') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Wards') }}</th>
                        <th>{{ __('District') }}</th>
                        <th>{{ __('Province') }}</th>
                        <th>{{ __('Source / Verified') }}</th>
                        <th>{{ __('Status') }}</th>
                        @if(Qs::userIsSuperAdmin())
                            <th>{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($localLevels as $index => $local)
                        <tr>
                            <td>{{ $localLevels->firstItem() + $index }}</td>
                            <td><code>{{ $local->code ?: '-' }}</code></td>
                            <td>{{ $local->name }}</td>
                            <td>{{ $local->name_ne }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $local->level_type)) }}</td>
                            <td><span class="badge badge-light">{{ $local->total_wards }}</span></td>
                            <td>{{ $local->district ? $local->district->name : '-' }}</td>
                            <td>{{ ($local->district && $local->district->province) ? $local->district->province->name : '-' }}</td>
                            <td class="small text-muted">
                                {{ \Illuminate\Support\Str::limit($local->data_source ?: '-', 36) }}
                                @if($local->effective_date)
                                    <br><span>{{ $local->effective_date }}</span>
                                @endif
                            </td>
                            <td>
                                @if($local->is_active)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            @if(Qs::userIsSuperAdmin())
                                <td>
                                    <form method="POST" action="{{ route('nepal_locations.toggle', ['type' => 'local_level', 'id' => $local->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $local->is_active ? 'warning' : 'success' }}">
                                            {{ $local->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Qs::userIsSuperAdmin() ? 11 : 10 }}" class="text-center text-muted">{{ __('No matching locations found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $localLevels->links() }}
        </div>
    </div>
</div>

@endsection

