<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use App\Models\RequestOrderNote;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestOrderController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = RequestOrder::with(['owner', 'requestedBy'])
            ->orderBy('created_at', 'desc');

        if ($user->role === 'staff-outlet') {
            $query->where('owner_id', $user->outlet_id);
        }

        $requests = $query->get();
        $outlets  = Outlet::orderBy('name')->get();

        return view('request-orders.index', compact('requests', 'outlets'));
    }

    public function create()
    {
        return view('request-orders.create', [
            'outlets'    => Outlet::get(),
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::with(['stocks' => function ($q) {
                $q->where('qty_available', '>', 0)
                    ->where('status', 'available');
            }])->whereHas('stocks', function ($q) {
                $q->where('qty_available', '>', 0)
                    ->where('status', 'available');
            })
                // ->where('is_serialized', false)
                ->get()
                ->map(function ($product) {
                    $product->total_available = (int) $product->stocks->sum('qty_available');

                    return $product;
                }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_id'                   => 'required|exists:outlets,id',
            'request_date'               => 'required|date',
            'items'                      => 'required|array',
            'items.*.product_id'         => 'required|exists:products,id|distinct',
            'items.*.qty_requested'      => 'required|numeric|min:1',            // changed to numeric
            'extra_notes'                => 'nullable|array',
            'extra_notes.*.kategori'     => 'required_with:extra_notes|string|max:255',
            'extra_notes.*.qty'          => 'required_with:extra_notes|numeric|min:0', // changed to numeric
            'extra_notes.*.nama_pj'      => 'nullable|string|max:255',
        ], [
            'owner_id.required'                     => 'Cabang harus dipilih.',
            'owner_id.exists'                       => 'Cabang yang dipilih tidak ditemukan.',
            'request_date.required'                 => 'Tanggal permintaan harus diisi.',
            'request_date.date'                     => 'Tanggal permintaan harus berupa tanggal yang valid.',
            'items.required'                        => 'Item harus diisi.',
            'items.array'                           => 'Item harus berupa array.',
            'items.*.product_id.required'           => 'Produk harus dipilih.',
            'items.*.product_id.exists'             => 'Produk yang dipilih tidak ditemukan.',
            'items.*.product_id.distinct'           => 'Produk tidak boleh sama di baris yang berbeda.',
            'items.*.qty_requested.required'        => 'Jumlah diminta harus diisi.',
            'items.*.qty_requested.numeric'         => 'Jumlah diminta harus berupa angka.',
            'items.*.qty_requested.min'             => 'Jumlah diminta minimal 1.',
            'extra_notes.array'                     => 'sample barang harus berupa array.',
            'extra_notes.*.kategori.required_with'  => 'Kategori harus diisi jika ada sample barang.',
            'extra_notes.*.kategori.string'         => 'Kategori harus berupa teks.',
            'extra_notes.*.kategori.max'            => 'Kategori maksimal 255 karakter.',
            'extra_notes.*.qty.required_with'       => 'Jumlah harus diisi jika ada sample barang.',
            'extra_notes.*.qty.numeric'             => 'Jumlah harus berupa angka.',
            'extra_notes.*.qty.min'                 => 'Jumlah minimal 0.',
            'extra_notes.*.nama_pj.string'          => 'Nama penanggung jawab harus berupa teks.',
            'extra_notes.*.nama_pj.max'             => 'Nama penanggung jawab maksimal 255 karakter.',
        ]);

        DB::beginTransaction();
        try {
            $lastRequest = RequestOrder::withTrashed()->latest('id')->first();
            $nextNumber = $lastRequest ? ((int) substr($lastRequest->code, 3) + 1) : 1;
            $code = 'REQ'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $requestOrder = RequestOrder::create([
                'code' => $code,
                'owner_id' => $request->owner_id,
                'requested_by' => auth()->id(),
                'request_date' => $request->request_date,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                RequestOrderItem::create([
                    'request_order_id' => $requestOrder->id,
                    'product_id'       => $item['product_id'],
                    'stock_id'         => null,
                    'qty_requested'    => $item['qty_requested'],
                    'notes'            => $item['notes'] ?? null,
                ]);
            }

            foreach ($request->input('extra_notes', []) as $note) {
                if (! empty($note['kategori'])) {
                    RequestOrderNote::create([
                        'request_order_id' => $requestOrder->id,
                        'kategori'         => $note['kategori'],
                        'qty'              => (int) ($note['qty'] ?? 0),
                        'nama_pj'          => $note['nama_pj'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // return redirect()->route('request-orders.verify', $requestOrder)
            //     ->with('toast_success', 'Request created successfully. Please assign stocks.');

            return redirect()->route('request-orders.index')
                ->with('toast_success', 'Request created successfully. Please assign stocks.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('toast_error', $e->getMessage());
        }
    }

    public function verify(RequestOrder $requestOrder)
    {
        return redirect()->route('request-orders.show', $requestOrder);
    }

    public function show($id)
    {
        $requestOrder = RequestOrder::with([
            'items.product.stocks',
            'items.stock',
            'requestedBy',
            'verifiedBy',
            'additionalNotes',
            'deliveryOrder.owner',
            'deliveryOrder.requestOrder.additionalNotes',
            'deliveryOrder.items.product',
            'deliveryOrder.items.stock',
        ])->findOrFail($id);

        if (auth()->user()->role === 'staff-outlet') {
            return view('request-orders.show', compact('requestOrder'));
        }

        return view('request-orders.verify', compact('requestOrder'));
    }

    public function processVerification(Request $request, RequestOrder $requestOrder)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:request_order_items,id',
            'items.*.qty_approved' => 'required|integer|min:1',
            'items.*.item_status' => 'required|in:approved,partial,rejected',
        ], [
            'items.required' => 'Item harus diisi.',
            'items.array' => 'Item harus berupa array.',
            'items.*.id.required' => 'ID item harus diisi.',
            'items.*.id.exists' => 'Item permintaan tidak ditemukan.',
            'items.*.qty_approved.required' => 'Jumlah disetujui harus diisi.',
            'items.*.qty_approved.integer' => 'Jumlah disetujui harus berupa angka.',
            'items.*.qty_approved.min' => 'Jumlah disetujui minimal 0.',
            'items.*.item_status.required' => 'Status item harus diisi.',
            'items.*.item_status.in' => 'Status item harus dipilih antara approved, partial, atau rejected.',
        ]);

        // Validate qty_approved against specific SKU stock
        foreach ($request->items as $itemData) {
            $item = RequestOrderItem::find($itemData['id']);
            $stock = $item->stock;

            if (! $stock) {
                return back()->withErrors([
                    'items.'.array_search($itemData, $request->items).'.qty_approved' => "Stock not found for product {$item->product->name}"
                ])->withInput();
            }

            // Validate against requested qty
            if ($itemData['qty_approved'] > $item->qty_requested) {
                return back()->withErrors([
                    'items.'.array_search($itemData, $request->items).'.qty_approved' => "Product {$item->product->name}: Approved qty cannot exceed requested qty ({$item->qty_requested})"
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // FIRST: Unreserve all previous reservations
            foreach ($request->items as $itemData) {
                $item = RequestOrderItem::find($itemData['id']);
                $stock = $item->stock;

                if ($item->qty_approved > 0 && $stock) {
                    $stock->unreserve($item->qty_approved);
                }
            }

            // SECOND: Refresh stocks and validate new quantities
            foreach ($request->items as $itemData) {
                $item = RequestOrderItem::find($itemData['id']);
                $stock = $item->stock->fresh(); // Refresh from DB after unreserve

                // Skip validation if rejected
                if ($itemData['item_status'] === 'rejected') {
                    continue;
                }

                // Validate available stock after unreserving
                if ($itemData['qty_approved'] > 0) {
                    if ($stock->qty_available < $itemData['qty_approved']) {
                        // Rollback and show error with current available
                        DB::rollBack();

                        return back()->withErrors([
                            'items.'.array_search($itemData, $request->items).'.qty_approved' => "Product {$item->product->name} (SKU: {$stock->sku}): Only {$stock->qty_available} available after releasing previous reservation. Cannot approve {$itemData['qty_approved']}."
                        ])->withInput();
                    }
                }
            }

            // THIRD: Update items and reserve new quantities
            $hasApproved = false;
            $hasPartial = false;
            $allRejected = true;

            foreach ($request->items as $itemData) {
                $item = RequestOrderItem::find($itemData['id']);
                $stock = $item->stock->fresh();

                // Handle rejected status
                if ($itemData['item_status'] === 'rejected') {
                    $item->update([
                        'qty_approved' => 0,
                        'item_status' => 'rejected',
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    continue;
                }

                $item->update([
                    'qty_approved' => $itemData['qty_approved'],
                    'item_status' => $itemData['item_status'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Reserve new quantity
                if ($itemData['qty_approved'] > 0) {
                    $stock->reserve($itemData['qty_approved']);
                }

                // Determine overall status
                if ($itemData['item_status'] === 'approved') {
                    $hasApproved = true;
                }
                if ($itemData['item_status'] === 'partial') {
                    $hasPartial = true;
                    $hasApproved = true;
                }
                if ($itemData['item_status'] !== 'rejected') {
                    $allRejected = false;
                }
            }

            // Update request order status
            if ($allRejected) {
                $status = 'rejected';
            } elseif ($hasPartial) {
                $status = 'partial';
            } else {
                $status = 'approved';
            }

            $requestOrder->update([
                'status' => $status,
                'verified_by' => auth()->id(),
                'verified_date' => now(),
                'verification_notes' => $request->verification_notes,
            ]);

            DB::commit();

            $message = $requestOrder->wasChanged('status')
                ? 'Request verified successfully'
                : 'Request verification updated successfully';

            // return redirect()->route('request-orders.verify', $requestOrder)
            //     ->with('toast_success', $message);
            return redirect()->route('request-orders.index')
                ->with('toast_success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('toast_error', $e->getMessage());
        }
    }

    public function updateStocks(Request $request, RequestOrder $requestOrder)
    {
        $request->validate([
            'stock_assignments' => 'required|array',
            'stock_assignments.*.item_id' => 'required|exists:request_order_items,id',
            'stock_assignments.*.stock_id' => 'required|exists:stocks,id|distinct',
            'stock_assignments.*.qty' => 'required|integer|min:1',
        ], [
            'stock_assignments.required' => 'Penugasan stok harus diisi.',
            'stock_assignments.array' => 'Penugasan stok harus berupa array.',
            'stock_assignments.*.item_id.required' => 'ID item harus diisi.',
            'stock_assignments.*.item_id.exists' => 'Item permintaan tidak ditemukan.',
            'stock_assignments.*.stock_id.required' => 'Stok harus dipilih.',
            'stock_assignments.*.stock_id.exists' => 'Stok yang dipilih tidak ditemukan.',
            'stock_assignments.*.stock_id.distinct' => 'Terdapat stok yang sama (ID :input) dimasukkan lebih dari satu kali.',
            'stock_assignments.*.qty.required' => 'Jumlah stok harus diisi.',
            'stock_assignments.*.qty.integer' => 'Jumlah stok harus berupa angka.',
            'stock_assignments.*.qty.min' => 'Jumlah stok minimal 1.',
        ]);

        DB::beginTransaction();
        try {
            // Group by item_id
            $grouped = collect($request->stock_assignments)->groupBy('item_id');
            $hasPartial = false;

            foreach ($grouped as $itemId => $assignments) {
                $originalItem = RequestOrderItem::find($itemId);
                $totalQty = $assignments->sum('qty');

                if ($totalQty > $originalItem->qty_requested) {
                    throw new \Exception("Product {$originalItem->product->name}: Total assigned qty ({$totalQty}) tidak boleh melebihi qty request ({$originalItem->qty_requested})");
                }

                // Delete original item (will be replaced by split items)
                $originalItem->delete();

                // Create new items for each stock assignment
                foreach ($assignments as $assignment) {
                    $stock = Stock::find($assignment['stock_id']);

                    if ($stock->qty_available < $assignment['qty']) {
                        throw new \Exception("Stock {$stock->sku}: Only {$stock->qty_available} available, cannot assign {$assignment['qty']}");
                    }

                    RequestOrderItem::create([
                        'request_order_id' => $requestOrder->id,
                        'product_id' => $originalItem->product_id,
                        'stock_id' => $assignment['stock_id'],
                        'qty_requested' => $assignment['qty'],
                        'qty_approved' => $assignment['qty'],
                        'item_status' => 'approved',
                        'notes' => $originalItem->notes,
                    ]);

                    $stock->reserve($assignment['qty']);
                }

                if ($totalQty < $originalItem->qty_requested) {
                    $hasPartial = true;

                    RequestOrderItem::create([
                        'request_order_id' => $requestOrder->id,
                        'product_id' => $originalItem->product_id,
                        'stock_id' => null,
                        'qty_requested' => $originalItem->qty_requested - $totalQty,
                        'qty_approved' => 0,
                        'item_status' => 'rejected',
                        'notes' => trim(($originalItem->notes ? $originalItem->notes.' | ' : '').'Sisa qty belum teralokasi saat verifikasi otomatis.'),
                    ]);
                }
            }

            $requestOrder->update([
                'status' => $hasPartial ? 'partial' : 'approved',
                'verified_by' => auth()->id(),
                'verified_date' => now(),
                'verification_notes' => 'Terverifikasi otomatis saat admin memilih SKU/stok.',
            ]);

            DB::commit();

            return redirect()->route('request-orders.show', $requestOrder)
                ->with('toast_success', 'Stock assignment berhasil disimpan dan request otomatis terverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('toast_error', $e->getMessage())->withInput();
        }
    }
}
