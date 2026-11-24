@extends('layouts.master')
@section('page_title', __('Nepal Administrative Locations'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-location4 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Nepal Administrative Locations & Governance') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Federal hierarchy directory: 7 Provinces, 77 Districts, 753 Local Governments & 6,743 Wards') }}
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-calendar22 mr-1 text-primary"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-graduation mr-1"></i> {{ __('Session') }}: {{ Qs::getSetting('current_session') }}
                    </span>
                    @if($report['is_complete_official_dataset'])
                        <span class="badge badge-success px-3 py-2 shadow-sm font-size-sm" style="border-radius: 8px;">
                            <i class="icon-checkmark-circle mr-1"></i> {{ __('Verified Complete (7/77/753)') }}
                        </span>
                    @else
                        <span class="badge badge-warning px-3 py-2 shadow-sm font-size-sm text-white" style="border-radius: 8px;">
                            <i class="icon-warning2 mr-1"></i> {{ __('Partial Dataset') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Provinces (प्रदेश)') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $report['provinces']['actual'] }} / {{ $report['provinces']['expected'] }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Federal states of Nepal') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-map5 icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Districts (जिल्ला)') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $report['districts']['actual'] }} / {{ $report['districts']['expected'] }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Administrative districts') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-city icon-2x" style="color: #06b6d4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Local Governments (तह)') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $report['local_levels']['actual'] }} / {{ $report['local_levels']['expected'] }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Municipalities & Rural councils') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-office icon-2x" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Wards (वडा तह)') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($report['total_wards']['actual']) }} / {{ number_format($report['total_wards']['expected']) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Constitutional ward units') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-grid5 icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Module Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-location4 mr-2" style="color: #FF5700;"></i>{{ __('Nepal Administrative Directory & Management') }}
            </h5>
            <div class="d-flex align-items-center">
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item">
                    <a href="#tab-local-levels" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-office mr-1"></i> {{ __('Local Governments (स्थानीय तह)') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ $localLevels->total() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-districts" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-city mr-1"></i> {{ __('Districts (जिल्ला)') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ $districts->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-provinces" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-map5 mr-1"></i> {{ __('Provinces (प्रदेश)') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ $provinces->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-overview" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-info22 mr-1"></i> {{ __('Federal Structure Overview') }}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab 1: Local Governments (753) --}}
                <div class="tab-pane fade show active" id="tab-local-levels">
                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('nepal_locations.manage') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label for="filter_province_id" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Province (प्रदेश)') }}</label>
                                <select name="province_id" id="filter_province_id" class="form-control form-control-sm select-search">
                                    <option value="">{{ __('All Provinces') }}</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->id }}" {{ (string) $provinceId === (string) $prov->id ? 'selected' : '' }}>
                                            {{ $prov->name }} / {{ $prov->name_ne }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="filter_district_id" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('District (जिल्ला)') }}</label>
                                <select name="district_id" id="filter_district_id" class="form-control form-control-sm select-search">
                                    <option value="">{{ __('All Districts') }}</option>
                                    @foreach($districts as $dist)
                                        <option value="{{ $dist->id }}" {{ (string) $districtId === (string) $dist->id ? 'selected' : '' }}>
                                            {{ $dist->name }} / {{ $dist->name_ne }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filter_level_type" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Type (प्रकार)') }}</label>
                                <select name="level_type" id="filter_level_type" class="form-control form-control-sm">
                                    <option value="">{{ __('All Types') }}</option>
                                    <option value="metropolitan" {{ $levelType === 'metropolitan' ? 'selected' : '' }}>{{ __('Metropolitan City') }}</option>
                                    <option value="sub_metropolitan" {{ $levelType === 'sub_metropolitan' ? 'selected' : '' }}>{{ __('Sub-Metropolitan City') }}</option>
                                    <option value="municipality" {{ $levelType === 'municipality' ? 'selected' : '' }}>{{ __('Municipality') }}</option>
                                    <option value="rural_municipality" {{ $levelType === 'rural_municipality' ? 'selected' : '' }}>{{ __('Rural Municipality') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filter_q" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Search (EN / नेपाली)') }}</label>
                                <input type="text" name="q" id="filter_q" value="{{ $q }}" class="form-control form-control-sm" placeholder="{{ __('Name or code...') }}">
                            </div>
                            <div class="col-md-2 mb-2 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm mr-1">
                                    <i class="icon-search4 mr-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('nepal_locations.manage') }}" class="btn btn-light btn-sm" title="{{ __('Reset') }}">
                                    <i class="icon-reset"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Local Government (EN & NE)') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Wards') }}</th>
                                    <th>{{ __('District') }}</th>
                                    <th>{{ __('Province') }}</th>
                                    <th>{{ __('Source & Verified') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(Qs::userIsSuperAdmin())
                                        <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($localLevels as $index => $local)
                                    <tr>
                                        <td>{{ $localLevels->firstItem() + $index }}</td>
                                        <td><code>{{ $local->code ?: '-' }}</code></td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">{{ $local->name }}</div>
                                            <div class="text-muted font-size-xs">{{ $local->name_ne }}</div>
                                        </td>
                                        <td>
                                            @if($local->level_type === 'metropolitan')
                                                <span class="badge badge-primary" style="border-radius: 6px; padding: 4px 8px;">{{ __('Metropolitan City') }}</span>
                                            @elseif($local->level_type === 'sub_metropolitan')
                                                <span class="badge badge-info" style="border-radius: 6px; padding: 4px 8px;">{{ __('Sub-Metropolitan') }}</span>
                                            @elseif($local->level_type === 'municipality')
                                                <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ __('Municipality') }}</span>
                                            @else
                                                <span class="badge badge-secondary" style="border-radius: 6px; padding: 4px 8px;">{{ __('Rural Municipality') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light border font-weight-semibold">{{ $local->total_wards }}</span>
                                        </td>
                                        <td>{{ $local->district ? $local->district->name : '-' }}</td>
                                        <td>{{ ($local->district && $local->district->province) ? $local->district->province->name : '-' }}</td>
                                        <td class="font-size-xs text-muted">
                                            <span>{{ \Illuminate\Support\Str::limit($local->data_source ?: 'MoFAGA Official', 32) }}</span>
                                            @if($local->effective_date)
                                                <div class="text-muted"><i class="icon-calendar22 mr-1"></i>{{ $local->effective_date }}</div>
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
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-outline-info btn-edit-location" 
                                                            title="{{ __('Edit Details') }}"
                                                            data-type="local_level"
                                                            data-id="{{ $local->id }}"
                                                            data-name="{{ $local->name }}"
                                                            data-name-ne="{{ $local->name_ne }}"
                                                            data-code="{{ $local->code }}"
                                                            data-total-wards="{{ $local->total_wards }}">
                                                        <i class="icon-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('nepal_locations.toggle', ['type' => 'local_level', 'id' => $local->id]) }}" class="d-inline ml-1">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-outline-{{ $local->is_active ? 'warning' : 'success' }}"
                                                                title="{{ $local->is_active ? __('Deactivate') : __('Activate') }}">
                                                            <i class="icon-switch"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ Qs::userIsSuperAdmin() ? 10 : 9 }}" class="text-center py-5 text-muted">
                                            <i class="icon-location4 icon-2x mb-2 d-block text-muted"></i>
                                            <span class="font-weight-medium">{{ __('No matching local governments found.') }}</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted font-size-sm">
                            {{ __('Showing :from to :to of :total local governments', [
                                'from' => $localLevels->firstItem() ?? 0,
                                'to' => $localLevels->lastItem() ?? 0,
                                'total' => $localLevels->total()
                            ]) }}
                        </div>
                        <div>
                            {{ $localLevels->links() }}
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Districts Directory (77) --}}
                <div class="tab-pane fade" id="tab-districts">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('District (जिल्ला)') }}</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Province') }}</th>
                                    <th>{{ __('Local Levels') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(Qs::userIsSuperAdmin())
                                        <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($districts as $dist)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">{{ $dist->name }}</div>
                                            <div class="text-muted font-size-xs">{{ $dist->name_ne }}</div>
                                        </td>
                                        <td><code>{{ $dist->code ?: '-' }}</code></td>
                                        <td>
                                            <span class="badge badge-light border">{{ $dist->province ? $dist->province->name : '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $dist->local_levels_count ?? '-' }} {{ __('Local Levels') }}</span>
                                        </td>
                                        <td>
                                            @if($dist->is_active)
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        @if(Qs::userIsSuperAdmin())
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-outline-info btn-edit-location" 
                                                            title="{{ __('Edit Details') }}"
                                                            data-type="district"
                                                            data-id="{{ $dist->id }}"
                                                            data-name="{{ $dist->name }}"
                                                            data-name-ne="{{ $dist->name_ne }}"
                                                            data-code="{{ $dist->code }}">
                                                        <i class="icon-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('nepal_locations.toggle', ['type' => 'district', 'id' => $dist->id]) }}" class="d-inline ml-1">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-outline-{{ $dist->is_active ? 'warning' : 'success' }}"
                                                                title="{{ $dist->is_active ? __('Deactivate') : __('Activate') }}">
                                                            <i class="icon-switch"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 3: Provinces Directory (7) --}}
                <div class="tab-pane fade" id="tab-provinces">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Province (प्रदेश)') }}</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Districts Count') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(Qs::userIsSuperAdmin())
                                        <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($provinces as $prov)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">{{ $prov->name }}</div>
                                            <div class="text-muted font-size-xs">{{ $prov->name_ne }}</div>
                                        </td>
                                        <td><code>{{ $prov->code ?: '-' }}</code></td>
                                        <td>
                                            <span class="badge badge-primary">{{ $prov->districts_count ?? '-' }} {{ __('Districts') }}</span>
                                        </td>
                                        <td>
                                            @if($prov->is_active)
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        @if(Qs::userIsSuperAdmin())
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-outline-info btn-edit-location" 
                                                            title="{{ __('Edit Details') }}"
                                                            data-type="province"
                                                            data-id="{{ $prov->id }}"
                                                            data-name="{{ $prov->name }}"
                                                            data-name-ne="{{ $prov->name_ne }}"
                                                            data-code="{{ $prov->code }}">
                                                        <i class="icon-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('nepal_locations.toggle', ['type' => 'province', 'id' => $prov->id]) }}" class="d-inline ml-1">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-outline-{{ $prov->is_active ? 'warning' : 'success' }}"
                                                                title="{{ $prov->is_active ? __('Deactivate') : __('Activate') }}">
                                                            <i class="icon-switch"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 4: Federal Structure Overview --}}
                <div class="tab-pane fade" id="tab-overview">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="font-weight-bold text-dark mb-2">
                                    <i class="icon-law mr-2 text-primary"></i>{{ __('Constitution of Nepal (Federal Restructuring)') }}
                                </h6>
                                <p class="text-muted font-size-sm mb-3">
                                    {{ __('Under Part 5 (Structure of State and Distribution of State Power) of the Constitution of Nepal, the federal democratic republic of Nepal is divided into three tiers of government:') }}
                                </p>
                                <ul class="list-unstyled font-size-sm">
                                    <li class="mb-2">
                                        <i class="icon-primitive-dot text-primary mr-1"></i>
                                        <strong>{{ __('Federal Level (संघीय सरकार)') }}</strong> &bull; {{ __('Central sovereign executive government based in Kathmandu.') }}
                                    </li>
                                    <li class="mb-2">
                                        <i class="icon-primitive-dot text-info mr-1"></i>
                                        <strong>{{ __('Provincial Level (प्रदेश सरकार)') }}</strong> &bull; {{ __('7 autonomous provinces overseeing regional infrastructure, health, and higher education.') }}
                                    </li>
                                    <li class="mb-2">
                                        <i class="icon-primitive-dot text-success mr-1"></i>
                                        <strong>{{ __('Local Level (स्थानीय सरकार)') }}</strong> &bull; {{ __('753 local governance units managing basic and secondary school education (Grade 1-12).') }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="font-weight-bold text-dark mb-2">
                                    <i class="icon-office mr-2 text-success"></i>{{ __('Local Government Classification (753 Units)') }}
                                </h6>
                                <div class="row text-center mt-3">
                                    <div class="col-6 mb-3">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="h4 font-weight-bold text-primary mb-0">6</div>
                                            <div class="font-size-xs text-muted font-weight-semibold">{{ __('Metropolitan Cities') }}</div>
                                            <div class="text-muted font-size-xs">(महानगरपालिका)</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="h4 font-weight-bold text-info mb-0">11</div>
                                            <div class="font-size-xs text-muted font-weight-semibold">{{ __('Sub-Metropolitan Cities') }}</div>
                                            <div class="text-muted font-size-xs">(उपमहानगरपालिका)</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="h4 font-weight-bold text-success mb-0">276</div>
                                            <div class="font-size-xs text-muted font-weight-semibold">{{ __('Municipalities') }}</div>
                                            <div class="text-muted font-size-xs">(नगरपालिका)</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="h4 font-weight-bold text-secondary mb-0">460</div>
                                            <div class="font-size-xs text-muted font-weight-semibold">{{ __('Rural Municipalities') }}</div>
                                            <div class="text-muted font-size-xs">(गाउँपालिका)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Location Modal (Super Admin) --}}
    @if(Qs::userIsSuperAdmin())
        <div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                    <form id="editLocationForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF;">
                            <h6 class="modal-title font-weight-bold mb-0 text-white">
                                <i class="icon-pencil mr-2 text-warning"></i>{{ __('Edit Location Details') }}
                            </h6>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-group">
                                <label class="font-weight-semibold text-dark">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="modal_loc_name" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-semibold text-dark">{{ __('Name (नेपाली)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name_ne" id="modal_loc_name_ne" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-semibold text-dark">{{ __('Official Code') }}</label>
                                <input type="text" name="code" id="modal_loc_code" class="form-control">
                            </div>
                            <div class="form-group" id="modal_wards_container" style="display: none;">
                                <label class="font-weight-semibold text-dark">{{ __('Total Wards') }}</label>
                                <input type="number" name="total_wards" id="modal_loc_total_wards" min="1" max="50" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm font-weight-semibold">
                                <i class="icon-checkmark mr-1"></i>{{ __('Update Location') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Dynamic district cascade for filter form
        $('#filter_province_id').on('change', function() {
            var provId = $(this).val();
            var $distSelect = $('#filter_district_id');
            
            $distSelect.empty().append($('<option>', { value: '', text: '{{ __("All Districts") }}' }));
            
            if (provId) {
                $.getJSON('{{ route("nepal_locations.districts") }}', { province_id: provId }, function(res) {
                    $.each(res.data || [], function(_, item) {
                        var label = item.name_ne ? (item.name + ' / ' + item.name_ne) : item.name;
                        $distSelect.append($('<option>', { value: item.id, text: label }));
                    });
                    if ($distSelect.hasClass('select2-hidden-accessible')) {
                        $distSelect.trigger('change.select2');
                    }
                });
            }
        });

        // Edit location modal handler
        $('.btn-edit-location').on('click', function() {
            var type = $(this).data('type');
            var id = $(this).data('id');
            var name = $(this).data('name');
            var nameNe = $(this).data('name-ne');
            var code = $(this).data('code');
            var totalWards = $(this).data('total-wards');

            var updateUrl = '{{ url("nepal-locations/manage") }}/' + type + '/' + id;
            $('#editLocationForm').attr('action', updateUrl);

            $('#modal_loc_name').val(name);
            $('#modal_loc_name_ne').val(nameNe);
            $('#modal_loc_code').val(code || '');

            if (type === 'local_level') {
                $('#modal_wards_container').show();
                $('#modal_loc_total_wards').val(totalWards || '');
            } else {
                $('#modal_wards_container').hide();
                $('#modal_loc_total_wards').val('');
            }

            $('#editLocationModal').modal('show');
        });
    });
</script>
@endsection
