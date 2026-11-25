@extends('layouts.master')
@section('page_title', __('Study Materials & E-Learning'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-file-text2 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Study Materials & E-Learning') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Digital lecture notes, assignments, syllabus resources & class-scoped downloads') }}
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
                    @if(in_array(Auth::user()->user_type, ['super_admin', 'admin', 'teacher']))
                        <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm ml-md-2" onclick="$('#tab-upload-material-link').tab('show');" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-upload mr-1"></i>{{ __('Upload New Material') }}
                        </button>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Materials') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_materials ?? $materials->total()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Available study resources') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-file-text2 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Classes Covered') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($total_classes_covered ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Active class repositories') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-office icon-2x" style="color: #10b981;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Subjects Covered') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_subjects_covered ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Distinct subject notes') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-books icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Repository Size') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($total_size_mb ?? 0, 2) }} MB</h3>
                            <span class="font-size-xs text-muted">{{ __('Total stored media') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-database icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Study Materials Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-file-text2 mr-2" style="color: #FF5700;"></i>{{ __('Study Materials & Lecture Notes') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            @if(in_array(Auth::user()->user_type, ['super_admin', 'admin', 'teacher']))
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item">
                        <a href="#all-materials" class="nav-link active font-weight-semibold" data-toggle="tab">
                            <i class="icon-list mr-1"></i> {{ __('All Study Materials') }}
                            <span class="badge badge-secondary ml-1 font-size-xs">{{ $materials->total() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#upload-material" id="tab-upload-material-link" class="nav-link font-weight-semibold" data-toggle="tab">
                            <i class="icon-upload mr-1"></i> {{ __('Upload New Material') }}
                        </a>
                    </li>
                </ul>
            @endif

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-materials">
                    {{-- Filter bar --}}
                    <form method="get" action="{{ route('study_materials.index') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Search Title / Description') }}</label>
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Search materials...') }}">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Class') }}</label>
                                <select name="class_id" class="form-control form-control-sm select">
                                    <option value="">{{ __('Filter by Class (All)') }}</option>
                                    @foreach($classes as $c)
                                        <option {{ request('class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(count($subjects) > 0)
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Subject') }}</label>
                                    <select name="subject_id" class="form-control form-control-sm select">
                                        <option value="">{{ __('All Subjects') }}</option>
                                        @foreach($subjects as $sub)
                                            <option {{ request('subject_id') == $sub->id ? 'selected' : '' }} value="{{ $sub->id }}">{{ $sub->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-2 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm mr-1">
                                    <i class="icon-filter3 mr-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('study_materials.index') }}" class="btn btn-light btn-sm" title="{{ __('Reset') }}">
                                    <i class="icon-reset"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Class / Section') }}</th>
                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('Uploaded By') }}</th>
                                    <th>{{ __('Format / Size') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $material)
                                    <tr>
                                        <td>{{ $materials->firstItem() + $loop->index }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">{{ $material->title }}</div>
                                            @if($material->description)
                                                <div class="text-muted font-size-xs">{{ \Illuminate\Support\Str::limit($material->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary" style="border-radius: 6px; padding: 4px 8px;">{{ $material->my_class->name ?? '-' }}</span>
                                            @if($material->section)
                                                <span class="badge badge-info" style="border-radius: 6px; padding: 4px 8px;">{{ $material->section->name }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $material->subject->name ?? __('General') }}</td>
                                        <td>{{ $material->teacher->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-dark text-uppercase">{{ $material->file_type }}</span>
                                            <span class="font-size-xs text-muted ml-1">{{ round($material->file_size / 1024, 1) }} KB</span>
                                        </td>
                                        <td class="text-muted font-size-xs">{{ $material->created_at->format('Y-m-d') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('study_materials.download', $material->id) }}" class="btn btn-outline-success btn-sm font-weight-semibold" style="border-radius: 6px;" title="{{ __('Download') }}">
                                                <i class="icon-download mr-1"></i>{{ __('Download') }}
                                            </a>
                                            @if(Qs::userIsSuperAdmin() || $material->teacher_id === Auth::id())
                                                <a id="{{ $material->id }}" onclick="confirmDelete(this.id)" href="#" class="btn btn-outline-danger btn-sm ml-1" style="border-radius: 6px;" title="{{ __('Delete') }}">
                                                    <i class="icon-trash"></i>
                                                </a>
                                                <form method="post" id="item-delete-{{ $material->id }}" action="{{ route('study_materials.destroy', $material->id) }}" class="hidden">
                                                    @csrf
                                                    @method('delete')
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="icon-folder-open2 icon-2x mb-2 d-block text-muted"></i>
                                            <span class="font-weight-medium">{{ __('No study materials found.') }}</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted font-size-sm">
                            {{ __('Showing :from to :to of :total materials', [
                                'from' => $materials->firstItem() ?? 0,
                                'to' => $materials->lastItem() ?? 0,
                                'total' => $materials->total()
                            ]) }}
                        </div>
                        <div>
                            {{ $materials->links() }}
                        </div>
                    </div>
                </div>

                @if(in_array(Auth::user()->user_type, ['super_admin', 'admin', 'teacher']))
                    <div class="tab-pane fade" id="upload-material">
                        <form method="post" action="{{ route('study_materials.store') }}" enctype="multipart/form-data" class="p-2">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('Material Title') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="{{ __('e.g. Chapter 4 Thermodynamics Notes') }}">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Class') }} <span class="text-danger">*</span></label>
                                            <select name="my_class_id" class="form-control select" required>
                                                <option value="">{{ __('Select Class') }}</option>
                                                @foreach($classes as $c)
                                                    <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Section (Optional)') }}</label>
                                            <select name="section_id" class="form-control select">
                                                <option value="">{{ __('All Sections') }}</option>
                                                @if(isset($sections))
                                                    @foreach($sections as $sec)
                                                        <option {{ old('section_id') == $sec->id ? 'selected' : '' }} value="{{ $sec->id }}">
                                                            {{ $sec->name }} ({{ $sec->my_class->name ?? '' }})
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('Subject (Optional)') }}</label>
                                        <select name="subject_id" class="form-control select">
                                            <option value="">{{ __('All / General Subject') }}</option>
                                            @foreach($subjects as $sub)
                                                <option {{ old('subject_id') == $sub->id ? 'selected' : '' }} value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->my_class->name ?? '' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('File Upload') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="material_file" class="form-control-uniform" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.png,.jpg,.jpeg">
                                        <small class="form-text text-muted">{{ __('Accepted formats: PDF, DOC, PPT, XLS, ZIP, TXT, Images. Max 10MB.') }}</small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('Description / Instructions') }}</label>
                                        <textarea name="description" rows="4" class="form-control" placeholder="{{ __('Optional reading instructions or summary') }}">{{ old('description') }}</textarea>
                                    </div>

                                    <div class="text-right border-top pt-3">
                                        <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                            <i class="icon-upload mr-2"></i>{{ __('Upload Material') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
