@extends('layouts.master')
@section('page_title', __('Manage Book Categories'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-folder-open icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Book Categories & Library Classification') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Genre taxonomy, departmental shelf classification & catalog segmentation') }}
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
                    <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-books mr-1"></i>{{ __('Book Inventory') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    @php
        $totalCats = count($categories);
        $totalCatBooks = $categories->sum(fn($c) => $c->books_count ?? count($c->books ?? []));
        $activeCats = $categories->filter(fn($c) => ($c->books_count ?? count($c->books ?? [])) > 0)->count();
        $emptyCats = $categories->filter(fn($c) => ($c->books_count ?? count($c->books ?? [])) == 0)->count();
    @endphp
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Categories') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalCats) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Classifications registered') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-folder-open icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Categorized Books') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($totalCatBooks) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Titles mapped to genres') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-books icon-2x" style="color: #10b981;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('In-Use Genres') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($activeCats) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('With active stock') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-checkmark4 icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Unused Categories') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($emptyCats) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Available for assignment') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-drawer-empty icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Layout --}}
    <div class="row">
        {{-- Categories Table Column --}}
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                    <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                        <i class="icon-folder-open mr-2" style="color: #FF5700;"></i>{{ __('Category Directory') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ count($categories) }}</span>
                    </h5>
                    {!! Qs::getPanelOptions() !!}
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Category Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Books') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($categories as $cat)
                                @php $bookCount = $cat->books_count ?? count($cat->books ?? []); @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-semibold text-dark">{{ $cat->name }}</span>
                                    </td>
                                    <td><code>{{ $cat->slug }}</code></td>
                                    <td>
                                        <span class="badge {{ $bookCount > 0 ? 'badge-primary' : 'badge-light border' }}" style="border-radius: 6px; padding: 4px 8px;">
                                            {{ $bookCount }} {{ __('Books') }}
                                        </span>
                                    </td>
                                    <td class="text-muted font-size-xs">{{ $cat->description ?: '-' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-edit-category" 
                                                    title="{{ __('Edit Category') }}"
                                                    data-id="{{ $cat->id }}"
                                                    data-name="{{ $cat->name }}"
                                                    data-description="{{ $cat->description }}">
                                                <i class="icon-pencil"></i>
                                            </button>
                                            @if($bookCount == 0)
                                                <a id="del-cat-{{ $cat->id }}" onclick="confirmDelete(this.id)" href="#" class="btn btn-outline-danger ml-1" title="{{ __('Delete Category') }}">
                                                    <i class="icon-trash"></i>
                                                </a>
                                                <form method="post" id="item-delete-del-cat-{{ $cat->id }}" action="{{ route('books.categories.destroy', $cat->id) }}" class="hidden">
                                                    @csrf @method('delete')
                                                </form>
                                            @else
                                                <span class="btn btn-sm btn-light disabled ml-1" title="{{ __('Protected: Has books assigned') }}" style="cursor: not-allowed; opacity: 0.6;">
                                                    <i class="icon-lock2 text-muted"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="icon-folder-open icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No library categories created yet.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Category Form Column --}}
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                        <i class="icon-plus-circle2 mr-2" style="color: #FF5700;"></i>{{ __('Add New Category') }}
                    </h5>
                </div>

                <div class="card-body p-3 p-md-4">
                    <form method="post" action="{{ route('books.categories.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Category Name') }} <span class="text-danger">*</span></label>
                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Science & Technology, Literature') }}">
                            @error('name')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Description (Optional)') }}</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Optional classification notes, Dewey range, rack section...') }}">{{ old('description') }}</textarea>
                            @error('description')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>

                        <div class="text-right border-top pt-3">
                            <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-checkmark mr-1"></i>{{ __('Save Category') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                <form id="editCategoryForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF;">
                        <h6 class="modal-title font-weight-bold mb-0 text-white">
                            <i class="icon-pencil mr-2 text-warning"></i>{{ __('Edit Book Category') }}
                        </h6>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Category Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="modal_cat_name" required class="form-control">
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-semibold text-dark">{{ __('Description (Optional)') }}</label>
                            <textarea name="description" id="modal_cat_desc" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm font-weight-semibold" style="border-radius: 6px;">
                            <i class="icon-checkmark mr-1"></i>{{ __('Update Category') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.btn-edit-category').on('click', function() {
            var catId = $(this).data('id');
            var name = $(this).data('name');
            var desc = $(this).data('description');

            var updateUrl = "{{ url('books/categories') }}/" + catId;
            $('#editCategoryForm').attr('action', updateUrl);
            $('#modal_cat_name').val(name);
            $('#modal_cat_desc').val(desc || '');

            $('#editCategoryModal').modal('show');
        });
    });
</script>
@endsection
