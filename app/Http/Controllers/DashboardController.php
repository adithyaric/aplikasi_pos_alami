<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\RequestOrder;
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

        // Stat cards
        $pendingOrdersCount = RequestOrder::where('status', 'pending')->count();
        $deliveredCount    = DeliveryOrder::where('status', 'delivered')->count();

        // Produk dengan penjualan terbanyak dari seluruh kanal penjualan.
        $salesTrendChart = PenjualanItem::selectRaw('product_id, SUM(qty) as total_qty')
            ->with('product:id,name,code')
            ->whereHas('penjualan')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->get();

        // 5 most recent request orders
        $recentOrders = RequestOrder::with(['owner:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $distributionDashboard = $this->distributionDashboardData();

        if ($request->wantsJson()) {
            return response()->json([
                'bestBuyProducts'  => [],
                'bestBuySuppliers' => [],
                'salesGraph'       => [],
                'productGraph'     => [],
                'monthlyRevenue'   => [],
            ]);
        }

        return view('dashboard.index', [
            'isStaffOutletDashboard' => false,
            'pembelianTerkirim'  => Pembelian::where('is_published', true)->count(),
            // Stat cards
            'pendingOrdersCount' => $pendingOrdersCount,
            'deliveredCount'     => $deliveredCount,
            // Charts
            'salesTrendChart'    => $salesTrendChart,
            // Tables
            'recentOrders'       => $recentOrders,
            // Existing widgets
            'distributionDashboard' => $distributionDashboard,
        ]);
    }

    /**
     * Build the four distribution summaries used by the dashboard. Sales are
     * grouped by the transaction's buyer type, while Sales is grouped by the
     * presence of a salesman. Receivables are calculated from the remaining
     * balance after the recorded payment transaction.
     */
    private function distributionDashboardData(): array
    {
        $monthStart = now()->startOfMonth();
        $chartStart = $monthStart->copy()->subMonths(5);
        $sales = Penjualan::with('paymentTransaction')->get();
        $chartSales = $sales->filter(fn ($sale) => $sale->sale_date && $sale->sale_date->gte($chartStart));

        $groups = [
            'canvas' => fn ($sale) => $sale->buyer_type === 'canvas',
            'agent' => fn ($sale) => $sale->buyer_type === 'agent',
            'branch' => fn ($sale) => $sale->buyer_type === 'outlet',
            'sales' => fn ($sale) => (bool) $sale->salesman_id,
        ];

        $cards = [];
        $charts = [];
        $labels = [];

        for ($index = 0; $index < 6; $index++) {
            $labels[] = $chartStart->copy()->addMonths($index)->format('M Y');
        }

        foreach ($groups as $key => $matches) {
            $groupSales = $sales->filter($matches)->values();
            $groupChartSales = $chartSales->filter($matches)->values();
            $cards[$key] = [
                'sales' => (int) $groupSales
                    ->filter(fn ($sale) => $sale->sale_date && $sale->sale_date->gte($monthStart))
                    ->sum(fn ($sale) => (int) ($sale->total ?? 0)),
                'receivable' => (int) $groupSales->sum(function ($sale) {
                    $paid = (int) ($sale->paymentTransaction?->amount ?? 0);

                    return max(0, (int) ($sale->total ?? 0) - $paid);
                }),
            ];

            $salesByMonth = [];
            $paymentsByMonth = [];
            foreach (range(0, 5) as $monthIndex) {
                $periodStart = $chartStart->copy()->addMonths($monthIndex)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $salesByMonth[] = (int) $groupChartSales
                    ->filter(fn ($sale) => $sale->sale_date && $sale->sale_date->betweenIncluded($periodStart, $periodEnd))
                    ->sum(fn ($sale) => (int) ($sale->total ?? 0));
                $paymentsByMonth[] = (int) $groupSales
                    ->filter(function ($sale) use ($periodStart, $periodEnd) {
                        $paymentDate = $sale->paymentTransaction?->payment_date;

                        return $paymentDate && $paymentDate->betweenIncluded($periodStart, $periodEnd);
                    })
                    ->sum(fn ($sale) => (int) ($sale->paymentTransaction?->amount ?? 0));
            }

            $charts[$key] = [
                'sales' => $salesByMonth,
                'payments' => $paymentsByMonth,
            ];
        }

        return compact('cards', 'charts', 'labels');
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
            'headOfficeSignature' => $settings['head_office_signature'] ?? '',
        ]);
    }

    public function settingMedia(string $type)
    {
        abort_unless(in_array($type, ['logo', 'signature'], true), 404);

        $settings = $this->getSettingsData();
        $path = $type === 'logo'
            ? ($settings['logo'] ?? null)
            : ($settings['head_office_signature'] ?? null);
        $storage = Storage::disk('public');

        abort_unless($path && $storage->exists($path), 404);

        return response()->file($storage->path($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
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
            'head_office_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'logo.image' => 'File yang diunggah harus berupa gambar.',
            'logo.mimes' => 'Logo harus bertipe: jpeg, png, jpg, atau gif.',
            'logo.max'   => 'Ukuran logo maksimal 2 MB.',
        ]);

        $data = array_merge($settings, [
            'name'    => $request->name,
            'email'   => $request->email,
            'telp'    => $request->telp,
            'address' => $request->address,
            'website' => $request->website,
        ]);
        unset($data['po_template_docx'], $data['po_template_xlsx']);

        if ($request->hasFile('logo')) {
            $this->deletePublicFile($settings['logo'] ?? null);
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        }

        if ($request->hasFile('head_office_signature')) {
            $this->deletePublicFile($settings['head_office_signature'] ?? null);
            $path = $request->file('head_office_signature')->store('signatures', 'public');
            $data['head_office_signature'] = $path;
        }

        Storage::disk('public')->put('settings.json', json_encode($data));

        return redirect(route('setting'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    private function getSettingsData(): array
    {
        if (! Storage::disk('public')->exists('settings.json')) {
            return [];
        }

        return json_decode(Storage::disk('public')->get('settings.json'), true) ?? [];
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
