@extends('layouts.master')
@section('page_title', __('Study Materials & E-Learning'))
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-file-text2 mr-2" style="color: #FF5700;"></i>{{ __('Study Materials & Lecture Notes') }}</h2>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        @if(in_array(Auth::user()->user_type, ['super_admin', 'admin', 'teacher']))
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-materials" class="nav-link active" data-toggle="tab"><i class="icon-list mr-1"></i> {{ __('All Study Materials') }}</a></li>
                <li class="nav-item"><a href="#upload-material" class="nav-link" data-toggle="tab"><i class="icon-upload mr-1"></i> {{ __('Upload New Material') }}</a></li>
            </ul>
        @endif

        <div class="tab-content">
            <div class="tab-pane fade show active" id="all-materials">
                {{-- Filter bar --}}
                <form method="get" action="{{ route('study_materials.index') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="class_id" class="form-control select">
                                <option value="">{{ __('Filter by Class (All)') }}</option>
                                @foreach($classes as $c)
                                    <option {{ request('class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary"><i class="icon-filter3 mr-1"></i>{{ __('Filter') }}</button>
                            <a href="{{ route('study_materials.index') }}" class="btn btn-light"><i class="icon-reset mr-1"></i>{{ __('Reset') }}</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Uploaded By') }}</th>
                                <th>{{ __('Format / Size') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $material)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold">{{ $material->title }}</div>
                                        @if($material->description)
                                            <div class="text-muted font-size-xs">{{ \Illuminate\Support\Str::limit($material->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $material->my_class->name ?? '-' }}</span>
                                        @if($material->section)
                                            <span class="badge badge-info">{{ $material->section->name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $material->subject->name ?? __('General') }}</td>
                                    <td>{{ $material->teacher->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-dark text-uppercase">{{ $material->file_type }}</span>
                                        <span class="font-size-xs text-muted ml-1">{{ round($material->file_size / 1024, 1) }} KB</span>
                                    </td>
                                    <td>{{ $material->created_at->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('study_materials.download', $material->id) }}" class="btn btn-outline-success btn-sm" title="{{ __('Download') }}">
                                            <i class="icon-download mr-1"></i>{{ __('Download') }}
                                        </a>
                                        @if(Qs::userIsSuperAdmin() || $material->teacher_id === Auth::id())
                                            <a id="{{ $material->id }}" onclick="confirmDelete(this.id)" href="#" class="btn btn-outline-danger btn-sm ml-1" title="{{ __('Delete') }}">
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
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="icon-folder-open2 icon-2x mb-2 d-block"></i>
                                        {{ __('No study materials found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $materials->appends(request()->query())->links() }}
                </div>
            </div>

            @if(in_array(Auth::user()->user_type, ['super_admin', 'admin', 'teacher']))
                <div class="tab-pane fade" id="upload-material">
                    <form method="post" action="{{ route('study_materials.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Material Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="{{ __('e.g. Chapter 4 Thermodynamics Notes') }}">
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Class') }} <span class="text-danger">*</span></label>
                                    <select name="my_class_id" class="form-control select" required>
                                        <option value="">{{ __('Select Class') }}</option>
                                        @foreach($classes as $c)
                                            <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Subject (Optional)') }}</label>
                                    <select name="subject_id" class="form-control select">
                                        <option value="">{{ __('All / General Subject') }}</option>
                                        @foreach($subjects as $sub)
                                            <option {{ old('subject_id') == $sub->id ? 'selected' : '' }} value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->my_class->name ?? '' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('File Upload') }} <span class="text-danger">*</span></label>
                                    <input type="file" name="material_file" class="form-control-uniform" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.png,.jpg,.jpeg">
                                    <small class="form-text text-muted">{{ __('Accepted formats: PDF, DOC, PPT, XLS, ZIP, TXT, Images. Max 10MB.') }}</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Description / Instructions') }}</label>
                                    <textarea name="description" rows="4" class="form-control" placeholder="{{ __('Optional reading instructions or summary') }}">{{ old('description') }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-success btn-block mt-4"><i class="icon-upload mr-2"></i>{{ __('Upload Material') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
