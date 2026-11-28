@extends('layouts.master')
@section('page_title', __('Inventory & Asset Management'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('School Inventory, Assets & Stock Control') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Track supplies, lab & stationery stock, vendor purchases, departmental issues & low-stock alerts') }}</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Stock Items') }}</span><h3 class="font-weight-bold mb-0">{{ $totalItems }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Low Stock Alerts') }}</span><h3 class="font-weight-bold text-danger mb-0">{{ $lowStockItems->count() }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Inventory Valuation') }}</span><h3 class="font-weight-bold text-success mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($totalValuation) }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#inv-items" class="nav-link active" data-toggle="tab">{{ __('Items & Stock') }}</a></li>
                <li class="nav-item"><a href="#inv-txn" class="nav-link" data-toggle="tab">{{ __('Purchase / Issue Stock') }}</a></li>
                <li class="nav-item"><a href="#inv-new" class="nav-link" data-toggle="tab">{{ __('Add Item & Category') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="inv-items">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Item Name') }}</th><th>{{ __('Category') }}</th><th>{{ __('Current Stock') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @forelse($items as $it)
                                    <tr>
                                        <td><code>{{ $it->item_code }}</code></td>
                                        <td><strong>{{ $it->name }}</strong> <div class="text-muted font-size-xs">{{ $it->location ?? '' }}</div></td>
                                        <td>{{ $it->category->name ?? __('Uncategorized') }}</td>
                                        <td><strong>{{ $it->current_stock }}</strong> {{ $it->unit }}</td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr($it->unit_price) }}</td>
                                        <td>
                                            @if($it->isLowStock())
                                                <span class="badge badge-danger">{{ __('Low Stock') }} (&le; {{ $it->reorder_level }})</span>
                                            @else
                                                <span class="badge badge-success">{{ __('In Stock') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No inventory items registered.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="inv-txn">
                    <form method="POST" action="{{ route('inventory.transactions.store') }}" class="mb-4 p-3 bg-light rounded border">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('Item') }} *</label><select name="inventory_item_id" class="form-control" required>@foreach($items as $it)<option value="{{ $it->id }}">{{ $it->name }} ({{ $it->current_stock }} {{ $it->unit }})</option>@endforeach</select></div>
                            <div class="col-md-2 form-group"><label>{{ __('Type') }}</label><select name="transaction_type" class="form-control"><option value="purchase">Purchase (+)</option><option value="issue">Issue (-)</option><option value="return">Return (+)</option><option value="adjustment">Adjustment (+)</option></select></div>
                            <div class="col-md-2 form-group"><label>{{ __('Quantity') }} *</label><input type="number" min="1" name="quantity" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('Vendor / Issued To') }}</label><input type="text" name="vendor_or_recipient" class="form-control"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Record Stock Transaction') }}</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Date (BS)') }}</th><th>{{ __('Item') }}</th><th>{{ __('Type') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Vendor / Recipient') }}</th></tr></thead>
                            <tbody>
                                @forelse($transactions as $tx)
                                    <tr>
                                        <td>{{ $tx->transaction_date_bs ?: $tx->transaction_date_ad->format('Y-m-d') }}</td>
                                        <td>{{ $tx->item->name ?? '-' }}</td>
                                        <td><span class="badge badge-info text-capitalize">{{ $tx->transaction_type }}</span></td>
                                        <td>{{ $tx->quantity }}</td>
                                        <td>{{ $tx->vendor_or_recipient ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No transactions yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="inv-new">
                    <form method="POST" action="{{ route('inventory.categories.store') }}" class="mb-4 p-3 bg-light rounded border">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-4 form-group mb-md-0"><label>{{ __('New Category Name') }} *</label><input type="text" name="name" class="form-control" required placeholder="Science Lab / Stationery"></div>
                            <div class="col-md-6 form-group mb-md-0"><label>{{ __('Description') }}</label><input type="text" name="description" class="form-control"></div>
                            <div class="col-md-2"><button type="submit" class="btn btn-secondary btn-block">{{ __('Add Category') }}</button></div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('inventory.items.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 form-group"><label>{{ __('Item Code / SKU') }} *</label><input type="text" name="item_code" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('Item Name') }} *</label><input type="text" name="name" class="form-control" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Category') }}</label><select name="inventory_category_id" class="form-control"><option value="">{{ __('Uncategorized') }}</option>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                            <div class="col-md-2 form-group"><label>{{ __('Unit') }} *</label><input type="text" name="unit" class="form-control" value="pcs" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Opening Stock') }}</label><input type="number" name="current_stock" value="0" class="form-control" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Reorder Alert Level') }}</label><input type="number" name="reorder_level" value="5" class="form-control" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Unit Price (NPR)') }}</label><input type="number" step="0.01" name="unit_price" value="0" class="form-control" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Storage Location') }}</label><input type="text" name="location" class="form-control"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save Inventory Item') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
