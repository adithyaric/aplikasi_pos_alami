<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\RefundPembelian;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'staff-outlet') {
            $requestOrdersBase = RequestOrder::where('owner_id', $user->outlet_id);

            return view('dashboard.index', [
                'isStaffOutletDashboard' => true,
                'outletRequestTotal' => (clone $requestOrdersBase)->count(),
                'outletRequestPending' => (clone $requestOrdersBase)->where('status', 'pending')->count(),
            ]);
        }

        $urgentSuppliers = Supplier::whereNotNull('deadline_days')
            ->whereNotNull('deadline_interval_weeks')
            ->with(['pembelians' => fn ($q) => $q->where('created_at', '>=', now()->subWeeks(4))])
            ->get()
            ->filter(function ($s) {
                $next = $s->nextDeadlineDate();
                if (! $next) {
                    return false;
                }
                if (\Carbon\Carbon::today()->diffInDays($next, false) > 3) {
                    return false;
                }
                if ($s->hasPembelianInCurrentInterval($next)) {
                    return false;
                }
                $s->next_deadline = $next;

                return true;
            })
            ->sortBy('next_deadline')
            ->values();

        $nearExpiryStocks = Stock::with('product:id,name,code')
            ->where('qty_available', '>', 0)
            ->whereNotNull('expired_at')
            ->whereDate('expired_at', '>=', now()->toDateString())
            ->whereDate('expired_at', '<=', now()->addDays(60)->toDateString())
            ->orderBy('expired_at')
            ->get(['id', 'product_id', 'qty_available', 'expired_at', 'batch_number', 'sku']);

        $activeAdjustments = \App\Models\ProductMinimumAdjustment::query()
            ->activeOn()
            ->orderByDesc('active_from')
            ->orderByDesc('id')
            ->get()
            ->groupBy('product_id');

        $lowVelocityProducts = Product::select('id', 'code', 'name', 'min_stock')
            ->withSum('stocks', 'qty_available')
            ->where('min_stock', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($activeAdjustments) {
                $adj          = $activeAdjustments->get($product->id)?->first();
                $effectiveMin = $adj
                    ? (int) ceil($product->min_stock * (1 + $adj->adjustment_percentage / 100))
                    : (int) $product->min_stock;
                $currentStock = (int) ($product->stocks_sum_qty_available ?? 0);

                $product->effective_min         = $effectiveMin;
                $product->current_stock         = $currentStock;
                $product->adjustment_percentage = $adj?->adjustment_percentage ?? 0;
                $product->deficit               = max(0, $effectiveMin - $currentStock);

                return $product;
            })
            ->filter(fn ($p) => $p->current_stock <= $p->effective_min)
            ->sortByDesc('deficit')
            ->values();

        // Stat cards
        $totalStock        = (int) Stock::sum('qty_available');
        $pendingOrdersCount = RequestOrder::where('status', 'pending')->count();
        $deliveredCount    = DeliveryOrder::where('status', 'delivered')->count();
        $refundCount       = RefundPembelian::count();
        $pendingOwnerApprovals = Pembelian::with(['supplier'])
            ->where('owner_approval_status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        // Top 5 products by available stock (inventory chart)
        $inventoryChart = Stock::selectRaw('product_id, SUM(qty_available) as total_qty')
            ->with('product:id,name,code')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Top 5 most requested products (status order donut chart)
        $statusOrderChart = RequestOrderItem::selectRaw('product_id, SUM(qty_requested) as total_qty')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Top 5 products most delivered to outlets
        $topProducts = DeliveryOrderItem::selectRaw('product_id, SUM(qty_sent) as total_qty')
            ->with('product:id,name,code')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 5 most recent request orders
        $recentOrders = RequestOrder::with(['owner:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        // Slow moving: products with stock but not delivered in last 90 days
        $recentlyDeliveredIds = DeliveryOrderItem::where('created_at', '>=', now()->subDays(90))
            ->distinct()
            ->pluck('product_id');

        $slowMovingProducts = Product::select('id', 'code', 'name')
            ->withSum('stocks', 'qty_available')
            ->whereNotIn('id', $recentlyDeliveredIds)
            ->orderByDesc('stocks_sum_qty_available')
            ->limit(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'bestBuyProducts'  => [],
                'bestBuySuppliers' => [],
                'salesGraph'       => [],
                'productGraph'     => [],
                'monthlyRevenue'   => [],
            ]);
        }

        $adjustmentProducts = Product::select('id', 'code', 'name', 'min_stock')
            ->withSum('stocks', 'qty_available')
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($activeAdjustments) {
                $adj = $activeAdjustments->get($p->id)?->first();
                $p->active_from   = $adj?->active_from;
                $p->active_until  = $adj?->active_until;
                $p->current_stock = (int) ($p->stocks_sum_qty_available ?? 0);
                $p->effective_min = $adj
                    ? (int) ceil($p->min_stock * (1 + $adj->adjustment_percentage / 100))
                    : (int) $p->min_stock;

                return $p;
            });

        return view('dashboard.index', [
            'isStaffOutletDashboard' => false,
            'products'           => Product::count(),
            'stocks'             => Stock::sum('qty'),
            'penjualans'         => Penjualan::count(),
            'pembelianTerkirim'  => Pembelian::where('is_published', true)->count(),
            'totalRevenue'       => 0,
            // Stat cards
            'totalStock'         => $totalStock,
            'pendingOrdersCount' => $pendingOrdersCount,
            'deliveredCount'     => $deliveredCount,
            'refundCount'        => $refundCount,
            'lowStockCount'      => $lowVelocityProducts->count(),
            'pendingOwnerApprovalCount' => $pendingOwnerApprovals->count(),
            // Charts
            'inventoryChart'     => $inventoryChart,
            'statusOrderChart'   => $statusOrderChart,
            'topProducts'        => $topProducts,
            // Tables
            'recentOrders'       => $recentOrders,
            'slowMovingProducts' => $slowMovingProducts,
            // Existing widgets
            'urgentSuppliers'    => $urgentSuppliers,
            'nearExpiryStocks'   => $nearExpiryStocks,
            'lowVelocityProducts' => $lowVelocityProducts,
            'adjustmentProducts' => $adjustmentProducts,
            'pendingOwnerApprovals' => $pendingOwnerApprovals,
        ]);
    }

    public function setting()
    {
        $settings = $this->getSettingsData();

        return view('dashboard.setting', [
            'name'    => $settings['name'] ?? '',
            'email'   => $settings['email'] ?? '',
            'telp'    => $settings['telp'] ?? '',
            'address' => $settings['address'] ?? '',
            'website' => $settings['website'] ?? '',
            'logo'    => $settings['logo'] ?? '',
            'poTemplateDocx' => $this->resolvePoTemplateMeta($settings, 'docx'),
            'poTemplateXlsx' => $this->resolvePoTemplateMeta($settings, 'xlsx'),
        ]);
    }

    public function store(Request $request)
    {
        $settings = $this->getSettingsData();

        $this->validate($request, [
            'name'    => 'required',
            'email'   => 'required|email',
            'telp'    => 'required',
            'address' => 'required',
            'website' => 'nullable|url',
            'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'po_template_docx' => 'nullable|file|mimes:docx|max:10240',
            'po_template_xlsx' => 'nullable|file|mimes:xlsx,xls|max:10240',
        ], [
            'logo.image' => 'File yang diunggah harus berupa gambar.',
            'logo.mimes' => 'Logo harus bertipe: jpeg, png, jpg, atau gif.',
            'logo.max'   => 'Ukuran logo maksimal 2 MB.',
            'po_template_docx.mimes' => 'Template DOCX harus bertipe .docx.',
            'po_template_docx.max' => 'Ukuran template DOCX maksimal 10 MB.',
            'po_template_xlsx.mimes' => 'Template Excel harus bertipe .xlsx atau .xls.',
            'po_template_xlsx.max' => 'Ukuran template Excel maksimal 10 MB.',
        ]);

        $data = array_merge($settings, [
            'name'    => $request->name,
            'email'   => $request->email,
            'telp'    => $request->telp,
            'address' => $request->address,
            'website' => $request->website,
        ]);

        if ($request->hasFile('logo')) {
            $this->deletePublicFile($settings['logo'] ?? null);
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        }

        if ($request->boolean('reset_po_template_docx')) {
            $this->deletePublicFile($settings['po_template_docx'] ?? null);
            $data['po_template_docx'] = null;
        }

        if ($request->boolean('reset_po_template_xlsx')) {
            $this->deletePublicFile($settings['po_template_xlsx'] ?? null);
            $data['po_template_xlsx'] = null;
        }

        if ($request->hasFile('po_template_docx')) {
            $this->deletePublicFile($settings['po_template_docx'] ?? null);
            $data['po_template_docx'] = $this->storePoTemplate($request->file('po_template_docx'), 'docx');
        }

        if ($request->hasFile('po_template_xlsx')) {
            $this->deletePublicFile($settings['po_template_xlsx'] ?? null);
            $data['po_template_xlsx'] = $this->storePoTemplate($request->file('po_template_xlsx'), 'xlsx');
        }

        Storage::disk('public')->put('settings.json', json_encode($data));

        return redirect(route('setting'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function downloadPoTemplate(string $format)
    {
        abort_unless(in_array($format, ['docx', 'xlsx'], true), 404);

        $settings = $this->getSettingsData();
        $template = $this->resolvePoTemplateMeta($settings, $format);

        abort_unless($template['absolute_path'] && file_exists($template['absolute_path']), 404);

        return response()->download($template['absolute_path'], $template['download_name']);
    }

    private function getSettingsData(): array
    {
        if (! Storage::disk('public')->exists('settings.json')) {
            return [];
        }

        return json_decode(Storage::disk('public')->get('settings.json'), true) ?? [];
    }

    private function resolvePoTemplateMeta(array $settings, string $format): array
    {
        $settingKey = $format === 'docx' ? 'po_template_docx' : 'po_template_xlsx';
        $defaultPath = $format === 'docx'
            ? base_path('contoh-po-docs.docx')
            : base_path('contoh-po-excel.xlsx');
        $customPath = $settings[$settingKey] ?? null;

        if ($customPath && Storage::disk('public')->exists($customPath)) {
            return [
                'source' => 'custom',
                'label' => basename($customPath),
                'absolute_path' => Storage::disk('public')->path($customPath),
                'download_name' => basename($customPath),
            ];
        }

        return [
            'source' => 'default',
            'label' => basename($defaultPath),
            'absolute_path' => file_exists($defaultPath) ? $defaultPath : null,
            'download_name' => basename($defaultPath),
        ];
    }

    private function storePoTemplate($file, string $type): string
    {
        $extension = $file->getClientOriginalExtension();

        return $file->storeAs('templates/po', "po-template-{$type}.{$extension}", 'public');
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
