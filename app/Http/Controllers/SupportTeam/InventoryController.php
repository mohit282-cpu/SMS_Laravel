<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->middleware('teamSA');
        $this->erp = $erp;
    }

    public function index()
    {
        $categories = InventoryCategory::withCount('items')->orderBy('name')->get();
        $items = InventoryItem::with('category')->orderBy('name')->get();
        $transactions = InventoryTransaction::with(['item', 'recorder'])->orderBy('id', 'desc')->take(40)->get();

        $totalItems = $items->count();
        $lowStockItems = $items->filter->isLowStock();
        $totalValuation = $items->sum(function ($item) {
            return (float) $item->current_stock * (float) $item->unit_price;
        });

        return view('pages.support_team.inventory.index', compact(
            'categories', 'items', 'transactions', 'totalItems', 'lowStockItems', 'totalValuation'
        ));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:inventory_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);
        InventoryCategory::create($validated);

        return back()->with('flash_success', __('Inventory category added.'));
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'inventory_category_id' => 'nullable|exists:inventory_categories,id',
            'item_code' => 'required|string|max:50|unique:inventory_items,item_code',
            'name' => 'required|string|max:150',
            'unit' => 'required|string|max:40',
            'current_stock' => 'required|integer|min:0|max:1000000',
            'reorder_level' => 'required|integer|min:0|max:100000',
            'unit_price' => 'required|numeric|min:0|max:9999999',
            'location' => 'nullable|string|max:120',
        ]);

        $item = InventoryItem::create($validated);
        $this->erp->audit('inventory.item_create', 'administration', "Added inventory item {$item->name}", $item);

        return back()->with('flash_success', __('Inventory item registered cleanly.'));
    }

    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'transaction_type' => 'required|in:purchase,issue,return,adjustment',
            'quantity' => 'required|integer|min:1|max:100000',
            'unit_cost' => 'nullable|numeric|min:0|max:9999999',
            'vendor_or_recipient' => 'nullable|string|max:150',
            'transaction_date_ad' => 'nullable|date',
            'transaction_date_bs' => 'nullable|string|max:20',
            'reference_no' => 'nullable|string|max:80',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['transaction_date_ad'] ?? null, $validated['transaction_date_bs'] ?? null, true);

        DB::transaction(function () use ($validated, $dates) {
            $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);
            $qty = (int) $validated['quantity'];

            if ($validated['transaction_type'] === 'issue') {
                if ($item->current_stock < $qty) {
                    abort(422, __('Insufficient stock to issue :qty unit(s). Available: :avail', [
                        'qty' => $qty,
                        'avail' => $item->current_stock,
                    ]));
                }
                $item->decrement('current_stock', $qty);
            } else {
                $item->increment('current_stock', $qty);
            }

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $qty,
                'unit_cost' => $validated['unit_cost'] ?? $item->unit_price,
                'vendor_or_recipient' => $validated['vendor_or_recipient'] ?? null,
                'transaction_date_ad' => $dates['ad'],
                'transaction_date_bs' => $dates['bs'],
                'reference_no' => $validated['reference_no'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'recorded_by' => Auth::id(),
            ]);
        });

        return back()->with('flash_success', __('Stock transaction posted and inventory updated.'));
    }
}
