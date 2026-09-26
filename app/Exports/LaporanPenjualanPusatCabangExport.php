<?php

namespace App\Exports;

use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Outlet;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Multi-sheet daily sales report based on laporan_penjualan.xlsx.
 *
 * The product tabs are the source of truth for the three summary tabs. This
 * keeps the workbook useful when products, buyers, branches, or the selected
 * date range change.
 */
class LaporanPenjualanPusatCabangExport
{
    public const SCOPE_PUSAT = 'pusat';

    public const SCOPE_CABANG = 'cabang';

    public const SCOPE_SEMUA = 'semua';

    private Carbon $dateFrom;

    private Carbon $dateTo;

    private string $scope;

    private ?int $outletId;

    private ?int $userId;

    public function __construct(
        string $dateFrom,
        string $dateTo,
        string $scope = self::SCOPE_SEMUA,
        ?int $outletId = null,
        ?int $userId = null,
    ) {
        $this->dateFrom = Carbon::parse($dateFrom)->startOfDay();
        $this->dateTo = Carbon::parse($dateTo)->startOfDay();
        $this->scope = in_array($scope, [self::SCOPE_PUSAT, self::SCOPE_CABANG, self::SCOPE_SEMUA], true)
            ? $scope
            : self::SCOPE_SEMUA;
        $this->outletId = $outletId;
        $this->userId = $userId;
    }

    public function store(string $outputPath): string
    {
        $data = $this->buildData();
        $workbook = new Spreadsheet();
        $workbook->removeSheetByIndex(0);
        $workbook->getProperties()
            ->setCreator(config('app.name', 'ALAMI'))
            ->setTitle('Laporan Penjualan Pusat dan Cabang')
            ->setSubject('Laporan penjualan multi-sheet')
            ->setDescription('Laporan penjualan harian dengan tab produk, agen, jumlah penjualan, dan piutang.');

        foreach ($data['products'] as &$productData) {
            $sheet = $workbook->createSheet();
            $sheet->setTitle($productData['sheet']);
            $this->writeProductSheet($sheet, $productData, $data);
        }
        unset($productData);

        $agentSheet = $workbook->createSheet();
        $agentSheet->setTitle('AGEN');
        $this->writeAgentSheet($agentSheet, $data);

        $salesSheet = $workbook->createSheet();
        $salesSheet->setTitle('JUMLAH PENJUALAN');
        $this->writeSalesSummarySheet($salesSheet, $data);

        $receivableSheet = $workbook->createSheet();
        $receivableSheet->setTitle('PIUTANG');
        $this->writeReceivableSheet($receivableSheet, $data);

        $workbook->setActiveSheetIndex(0);
        $workbook->getCalculationEngine()->clearCalculationCache();

        $writer = new Xlsx($workbook);
        $writer->setPreCalculateFormulas(true);
        $writer->save($outputPath);

        return $outputPath;
    }

    private function buildData(): array
    {
        $dates = collect();
        foreach (CarbonPeriod::create($this->dateFrom->copy(), $this->dateTo->copy()) as $date) {
            $dates->push($date->copy()->startOfDay());
        }

        $products = Product::withTrashed()->orderBy('name')->orderBy('id')->get();
        $sales = $this->salesQuery(false)->get();
        $debtSales = $this->salesQuery(true)->get();

        // Historical sales can still point to a soft-deleted product. Keep
        // those products in the export so the totals remain reconcilable.
        $productsById = $products->keyBy('id');
        $debtSales->each(function (Penjualan $sale) use (&$productsById): void {
            foreach ($sale->items as $item) {
                if ($item->product && ! $productsById->has($item->product_id)) {
                    $productsById->put($item->product_id, $item->product);
                }
            }
        });
        $products = $productsById->sortBy(fn (Product $product) => [mb_strtolower((string) $product->name), $product->id])->values();

        $contributors = [];
        $qtyByDate = [];
        $nominalByDate = [];
        $qtyByContributor = [];
        $debtOpening = [];
        $paymentByDate = [];
        $paymentByDateProduct = [];

        foreach ($products as $product) {
            $debtOpening[$product->id] = 0.0;
        }

        foreach ($debtSales as $sale) {
            $saleDate = $this->saleDate($sale);
            if (! $saleDate || $saleDate->gt($this->dateTo)) {
                continue;
            }

            $lines = $this->saleLines($sale);
            if ($lines === []) {
                continue;
            }

            $lineTotal = array_sum(array_column($lines, 'amount'));
            if ($lineTotal <= 0) {
                $lineTotal = (float) ($sale->total ?? 0);
            }

            $events = $this->paymentEvents($sale, $saleDate);
            $paidBeforePeriod = array_sum(array_map(
                fn (array $event): float => $event['date']->lt($this->dateFrom) ? $event['amount'] : 0.0,
                $events
            ));

            if ($saleDate->lt($this->dateFrom)) {
                foreach ($lines as $line) {
                    $ratio = $lineTotal > 0 ? $line['amount'] / $lineTotal : 0;
                    $debtOpening[$line['product_id']] += max(0, $line['amount'] - ($paidBeforePeriod * $ratio));
                }
            }

            if ($saleDate->betweenIncluded($this->dateFrom, $this->dateTo)) {
                $dateKey = $saleDate->toDateString();
                $contributor = $this->contributorName($sale);
                if ($contributor !== null) {
                    $contributors[$contributor] = true;
                }

                foreach ($lines as $line) {
                    $productId = $line['product_id'];
                    $qtyByDate[$dateKey][$productId] = ($qtyByDate[$dateKey][$productId] ?? 0) + $line['qty'];
                    $nominalByDate[$dateKey][$productId] = ($nominalByDate[$dateKey][$productId] ?? 0) + $line['amount'];

                    if ($contributor !== null) {
                        $qtyByContributor[$dateKey][$productId][$contributor] =
                            ($qtyByContributor[$dateKey][$productId][$contributor] ?? 0) + $line['qty'];
                    }
                }
            }

            foreach ($events as $event) {
                if (! $event['date']->betweenIncluded($this->dateFrom, $this->dateTo)) {
                    continue;
                }

                $dateKey = $event['date']->toDateString();
                $paymentByDate[$dateKey] = ($paymentByDate[$dateKey] ?? 0) + $event['amount'];
                foreach ($lines as $line) {
                    $ratio = $lineTotal > 0 ? $line['amount'] / $lineTotal : 0;
                    $paymentByDateProduct[$dateKey][$line['product_id']] =
                        ($paymentByDateProduct[$dateKey][$line['product_id']] ?? 0) + ($event['amount'] * $ratio);
                }
            }
        }

        $contributors = array_keys($contributors);
        sort($contributors, SORT_NATURAL | SORT_FLAG_CASE);

        $debtByDate = [];
        $runningDebt = $debtOpening;
        foreach ($dates as $date) {
            $dateKey = $date->toDateString();
            foreach ($products as $product) {
                $productId = $product->id;
                $runningDebt[$productId] = max(
                    0,
                    ($runningDebt[$productId] ?? 0)
                        + ($nominalByDate[$dateKey][$productId] ?? 0)
                        - ($paymentByDateProduct[$dateKey][$productId] ?? 0)
                );
                $debtByDate[$dateKey][$productId] = $runningDebt[$productId];
            }
        }

        $sheetNames = $this->makeSheetNames($products);
        $stockData = $this->buildStockData($products, $dates, $qtyByDate);

        $productData = [];
        foreach ($products as $product) {
            $productData[] = [
                'model' => $product,
                'id' => $product->id,
                'sheet' => $sheetNames[$product->id],
                'rowByDate' => [],
            ];
        }

        return [
            'dates' => $dates,
            'products' => $productData,
            'contributors' => $contributors,
            'qtyByDate' => $qtyByDate,
            'nominalByDate' => $nominalByDate,
            'qtyByContributor' => $qtyByContributor,
            'debtOpening' => $debtOpening,
            'debtByDate' => $debtByDate,
            'paymentByDate' => $paymentByDate,
            'stockOpening' => $stockData['opening'],
            'loadingByDate' => $stockData['loading'],
            'scopeLabel' => $this->scopeLabel(),
        ];
    }

    private function salesQuery(bool $includeBeforePeriod)
    {
        $query = Penjualan::with([
            'items.product',
            'paymentTransaction',
            'outlet',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'tokoBuyer',
        ]);

        $channels = match ($this->scope) {
            self::SCOPE_PUSAT => ['warehouse'],
            self::SCOPE_CABANG => ['branch'],
            default => ['warehouse', 'branch'],
        };
        $query->whereIn('sale_channel', $channels);

        if ($includeBeforePeriod) {
            $query->where(function ($builder): void {
                $builder->whereDate('sale_date', '<=', $this->dateTo->toDateString())
                    ->orWhere(function ($fallback): void {
                        $fallback->whereNull('sale_date')
                            ->whereDate('created_at', '<=', $this->dateTo->toDateString());
                    });
            });
        } else {
            $query->where(function ($builder): void {
                $builder->whereBetween('sale_date', [
                    $this->dateFrom->toDateString(),
                    $this->dateTo->toDateString(),
                ])->orWhere(function ($fallback): void {
                    $fallback->whereNull('sale_date')
                        ->whereBetween('created_at', [$this->dateFrom, $this->dateTo->copy()->endOfDay()]);
                });
            });
        }

        if ($this->outletId !== null) {
            $query->where('outlet_id', $this->outletId);
        }

        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }

        return $query->orderBy('sale_date')->orderBy('id');
    }

    private function saleDate(Penjualan $sale): ?Carbon
    {
        return $sale->sale_date?->copy()->startOfDay()
            ?? $sale->created_at?->copy()->startOfDay();
    }

    private function saleLines(Penjualan $sale): array
    {
        $lines = [];
        foreach ($sale->items as $item) {
            if (! $item->product) {
                continue;
            }

            $qty = (float) ($item->qty ?? $item->qty_input ?? 0);
            $amount = (float) ($item->subtotal ?? (($qty * (float) ($item->price ?? 0)) - (float) ($item->discount ?? 0)));
            if ($qty <= 0 && $amount <= 0) {
                continue;
            }

            $lines[] = [
                'product_id' => (int) $item->product_id,
                'qty' => $qty,
                'amount' => max(0, $amount),
            ];
        }

        return $lines;
    }

    private function paymentEvents(Penjualan $sale, Carbon $saleDate): array
    {
        $payment = $sale->paymentTransaction;
        $history = $payment?->payment_history;
        $events = [];

        if (is_array($history) && $history !== []) {
            foreach ($history as $entry) {
                $amount = (float) ($entry['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $date = $entry['payment_date'] ?? $payment?->payment_date ?? $saleDate;
                $events[] = [
                    'date' => Carbon::parse($date)->startOfDay(),
                    'amount' => $amount,
                ];
            }

            return $events;
        }

        $amount = (float) ($payment?->amount ?? 0);
        if ($amount <= 0 && ($sale->payment_status ?? null) === 'paid') {
            $amount = (float) ($sale->total ?? 0);
        }

        if ($amount > 0) {
            $events[] = [
                'date' => ($payment?->payment_date?->copy() ?? $saleDate->copy())->startOfDay(),
                'amount' => $amount,
            ];
        }

        return $events;
    }

    private function contributorName(Penjualan $sale): ?string
    {
        $name = $sale->isWarehouseSale()
            ? trim((string) $sale->buyerDisplayName)
            : trim((string) ($sale->outlet?->name ?? ''));
        $name = $name !== '' && $name !== '-' ? $name : ($sale->isWarehouseSale() ? 'Tanpa Pembeli' : 'Tanpa Cabang');

        if ($this->scope === self::SCOPE_SEMUA) {
            $name = ($sale->isWarehouseSale() ? 'Pusat: ' : 'Cabang: ').$name;
        }

        return $name;
    }

    private function buildStockData(Collection $products, Collection $dates, array $qtyByDate): array
    {
        $productIds = $products->pluck('id')->all();
        $opening = [];
        $loading = [];
        foreach ($productIds as $productId) {
            $opening[$productId] = 0.0;
        }

        $includeWarehouse = in_array($this->scope, [self::SCOPE_PUSAT, self::SCOPE_SEMUA], true);
        $includeBranches = in_array($this->scope, [self::SCOPE_CABANG, self::SCOPE_SEMUA], true);
        $branchIds = $this->outletId !== null
            ? [$this->outletId]
            : Outlet::branches()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $movementNetAfterStart = array_fill_keys($productIds, 0.0);
        $movementRows = collect();
        if ($includeWarehouse) {
            $movementRows = $movementRows->merge(
                StockMovement::whereIn('product_id', $productIds)
                    ->where('created_at', '>=', $this->dateFrom)
                    ->get(['product_id', 'qty_in', 'qty_out', 'created_at'])
            );
            $currentWarehouse = Stock::whereIn('product_id', $productIds)
                ->selectRaw('product_id, SUM(qty) as quantity')
                ->groupBy('product_id')
                ->pluck('quantity', 'product_id');
            foreach ($currentWarehouse as $productId => $quantity) {
                $opening[$productId] += (float) $quantity;
            }
        }

        if ($includeBranches && $branchIds !== []) {
            $movementRows = $movementRows->merge(
                OwnerStockMovement::whereIn('owner_id', $branchIds)
                    ->whereIn('product_id', $productIds)
                    ->where('created_at', '>=', $this->dateFrom)
                    ->get(['product_id', 'qty_in', 'qty_out', 'created_at'])
            );
            $currentBranches = OwnerStock::whereIn('owner_id', $branchIds)
                ->whereIn('product_id', $productIds)
                ->selectRaw('product_id, SUM(qty) as quantity')
                ->groupBy('product_id')
                ->pluck('quantity', 'product_id');
            foreach ($currentBranches as $productId => $quantity) {
                $opening[$productId] += (float) $quantity;
            }
        }

        foreach ($movementRows as $movement) {
            $productId = (int) $movement->product_id;
            $date = Carbon::parse($movement->created_at)->startOfDay();
            $net = (float) $movement->qty_in - (float) $movement->qty_out;
            $movementNetAfterStart[$productId] = ($movementNetAfterStart[$productId] ?? 0) + $net;

            if ($date->betweenIncluded($this->dateFrom, $this->dateTo)) {
                $dateKey = $date->toDateString();
                $loading[$dateKey][$productId] = ($loading[$dateKey][$productId] ?? 0) + (float) $movement->qty_in;
            }
        }

        foreach ($opening as $productId => $quantity) {
            $opening[$productId] = max(0, $quantity - ($movementNetAfterStart[$productId] ?? 0));
        }

        // A sale is also a stock-out movement. The report's closing stock is
        // deliberately calculated from opening + loading - sales so it stays
        // understandable even when older records have incomplete movements.
        return ['opening' => $opening, 'loading' => $loading];
    }

    private function makeSheetNames(Collection $products): array
    {
        $used = ['AGEN' => true, 'JUMLAH PENJUALAN' => true, 'PIUTANG' => true];
        $names = [];
        foreach ($products as $product) {
            $base = trim((string) ($product->name ?: $product->code ?: 'Produk '.$product->id));
            $base = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $base) ?: 'Produk '.$product->id;
            $base = mb_substr($base, 0, 31);
            $candidate = $base;
            $suffix = 2;
            while (isset($used[mb_strtoupper($candidate)])) {
                $tail = ' '.($suffix++);
                $candidate = mb_substr($base, 0, 31 - mb_strlen($tail)).$tail;
            }
            $used[mb_strtoupper($candidate)] = true;
            $names[$product->id] = $candidate;
        }

        return $names;
    }

    private function writeProductSheet($sheet, array &$productData, array $data): void
    {
        /** @var Product $product */
        $product = $productData['model'];
        $contributors = $data['contributors'];
        $firstContributorColumn = 21; // U, matching the reference workbook.
        $totalColumn = $firstContributorColumn + count($contributors);
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumn);
        $dataStartRow = 4;

        $sheet->setCellValue('A1', mb_strtoupper($data['scopeLabel'].' - '.$product->name));
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A2', 'NO');
        $sheet->setCellValue('B2', 'TANGGAL');
        $sheet->setCellValue('C2', 'STOCK AWAL');
        $sheet->setCellValue('F2', 'LOADING');
        $sheet->setCellValue('I2', 'PENJUALAN');
        $sheet->setCellValue('L2', 'SISA');
        $sheet->setCellValue('O2', 'HARGA');
        $sheet->setCellValue('P2', 'NOMINAL HUTANG AWAL');
        $sheet->setCellValue('Q2', 'NOMINAL PENJUALAN');
        $sheet->setCellValue('R2', 'NOMINAL SISA HUTANG');
        $sheet->setCellValue('T2', 'TANGGAL DATA');
        $sheet->setCellValue($lastColumn.'2', 'TOTAL');

        foreach ([['C2', 'E2'], ['F2', 'H2'], ['I2', 'K2']] as [$from, $to]) {
            $sheet->mergeCells($from.':'.$to);
        }
        foreach (['A', 'B', 'O', 'P', 'Q', 'R', 'T'] as $column) {
            $sheet->mergeCells($column.'2:'.$column.'3');
        }
        foreach ([
            'C' => $product->satuan ?: 'PCS',
            'D' => $product->satuan_besar ?: '-',
            'E' => $product->satuan_terbesar ?: '-',
            'F' => $product->satuan ?: 'PCS',
            'G' => $product->satuan_besar ?: '-',
            'H' => $product->satuan_terbesar ?: '-',
            'I' => $product->satuan ?: 'PCS',
            'J' => $product->satuan_besar ?: '-',
            'K' => $product->satuan_terbesar ?: '-',
        ] as $column => $label) {
            $sheet->setCellValue($column.'3', $label);
        }
        foreach ($contributors as $index => $contributor) {
            $sheet->setCellValueByColumnAndRow($firstContributorColumn + $index, 2, $contributor);
        }
        $sheet->setCellValue($lastColumn.'3', $product->satuan ?: 'QTY');

        $factors = $this->unitFactors($product);
        foreach ($data['dates'] as $index => $date) {
            $row = $dataStartRow + $index;
            $dateKey = $date->toDateString();
            $productData['rowByDate'][$dateKey] = $row;
            $qty = (float) ($data['qtyByDate'][$dateKey][$product->id] ?? 0);
            $opening = (float) ($data['stockOpening'][$product->id] ?? 0);
            foreach ($data['dates']->slice(0, $index) as $priorDate) {
                $priorKey = $priorDate->toDateString();
                $opening += (float) ($data['loadingByDate'][$priorKey][$product->id] ?? 0)
                    - (float) ($data['qtyByDate'][$priorKey][$product->id] ?? 0);
            }
            $loading = (float) ($data['loadingByDate'][$dateKey][$product->id] ?? 0);
            $nominal = (float) ($data['nominalByDate'][$dateKey][$product->id] ?? 0);
            $debtOpening = $index === 0
                ? (float) ($data['debtOpening'][$product->id] ?? 0)
                : (float) ($data['debtByDate'][$data['dates'][$index - 1]->toDateString()][$product->id] ?? 0);
            $debtClosing = (float) ($data['debtByDate'][$dateKey][$product->id] ?? 0);

            $sheet->fromArray([
                $index + 1,
                $date->format('d/m/Y'),
                $opening,
                '='.$this->conversionFormula('C'.$row, $factors['large']),
                '='.$this->conversionFormula('C'.$row, $factors['largest']),
                $loading,
                '='.$this->conversionFormula('F'.$row, $factors['large']),
                '='.$this->conversionFormula('F'.$row, $factors['largest']),
                '='.$lastColumn.$row,
                '='.$this->conversionFormula('I'.$row, $factors['large']),
                '='.$this->conversionFormula('I'.$row, $factors['largest']),
                '=C'.$row.'+F'.$row.'-I'.$row,
                '='.$this->conversionFormula('L'.$row, $factors['large']),
                '='.$this->conversionFormula('L'.$row, $factors['largest']),
                (float) ($product->harga_jual ?? 0),
                $debtOpening,
                $nominal,
                $debtClosing,
                null,
                $date->format('d/m/Y'),
            ], null, 'A'.$row);

            foreach ($contributors as $contributorIndex => $contributor) {
                $value = (float) ($data['qtyByContributor'][$dateKey][$product->id][$contributor] ?? 0);
                $sheet->setCellValueByColumnAndRow($firstContributorColumn + $contributorIndex, $row, $value);
            }
            $sheet->setCellValue($lastColumn.$row, count($contributors) > 0
                ? '=SUM(U'.$row.':'.Coordinate::stringFromColumnIndex($totalColumn - 1).$row.')'
                : '=0');
        }

        $totalRow = $dataStartRow + $data['dates']->count();
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        $sheet->mergeCells('A'.$totalRow.':B'.$totalRow);
        foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R'] as $column) {
            $sheet->setCellValue($column.$totalRow, '=SUM('.$column.$dataStartRow.':'.$column.($totalRow - 1).')');
        }
        foreach (range($firstContributorColumn, $totalColumn) as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($column.$totalRow, '=SUM('.$column.$dataStartRow.':'.$column.($totalRow - 1).')');
        }

        $this->styleSheet($sheet, 'A1:'.$lastColumn.$totalRow, $lastColumn, $dataStartRow, $totalRow, true);
        $sheet->setAutoFilter('A3:'.$lastColumn.($totalRow - 1));
        $sheet->freezePane('C4');
        $sheet->getPageSetup()->setOrientation('landscape');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->setShowGridlines(false);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (range(3, 14) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(10);
        }
        $sheet->getColumnDimension('O')->setWidth(13);
        foreach (['P', 'Q', 'R'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(18);
        }
        $sheet->getColumnDimension('S')->setWidth(3);
        $sheet->getColumnDimension('T')->setWidth(13);
        foreach (range($firstContributorColumn, $totalColumn) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(14);
        }
    }

    private function writeAgentSheet($sheet, array $data): void
    {
        $products = $data['products'];
        $receivableColumns = $this->receivableColumns($data);
        $productStart = 3;
        $productEnd = $productStart + count($products) - 1;
        $qtyTotalColumn = $productEnd + 1;
        $nominalColumn = $qtyTotalColumn + 1;
        $paymentColumn = $nominalColumn + 1;
        $balanceColumn = $paymentColumn + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($balanceColumn);

        $sheet->setCellValue('A1', 'AGEN - RINGKASAN PENJUALAN '.$data['scopeLabel']);
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A2', 'NO');
        $sheet->setCellValue('B2', 'TANGGAL');
        $sheet->mergeCells('A2:A3');
        $sheet->mergeCells('B2:B3');
        foreach ($products as $index => $productData) {
            $column = Coordinate::stringFromColumnIndex($productStart + $index);
            $sheet->setCellValue($column.'2', $productData['model']->name ?: $productData['model']->code);
            $sheet->setCellValue($column.'3', $productData['model']->satuan ?: 'PCS');
        }
        $sheet->setCellValueByColumnAndRow($qtyTotalColumn, 2, 'TOTAL QTY');
        $sheet->setCellValueByColumnAndRow($nominalColumn, 2, 'NOMINAL PENJUALAN');
        $sheet->setCellValueByColumnAndRow($paymentColumn, 2, 'PEMBAYARAN');
        $sheet->setCellValueByColumnAndRow($balanceColumn, 2, 'SISA PIUTANG');
        foreach (range($qtyTotalColumn, $balanceColumn) as $columnIndex) {
            $sheet->mergeCells(Coordinate::stringFromColumnIndex($columnIndex).'2:'.Coordinate::stringFromColumnIndex($columnIndex).'3');
        }

        foreach ($data['dates'] as $index => $date) {
            $row = 4 + $index;
            $piutangRow = 2 + $index;
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, $date->format('d/m/Y'));
            foreach ($products as $productIndex => $productData) {
                $sourceRow = $productData['rowByDate'][$date->toDateString()];
                $column = Coordinate::stringFromColumnIndex($productStart + $productIndex);
                $sheet->setCellValue($column.$row, $this->sheetReference($productData['sheet'], 'I'.$sourceRow));
            }
            $qtyRange = 'C'.$row.':'.Coordinate::stringFromColumnIndex($productEnd).$row;
            $sheet->setCellValueByColumnAndRow($qtyTotalColumn, $row, '=SUM('.$qtyRange.')');
            $sheet->setCellValueByColumnAndRow($nominalColumn, $row, $this->piutangReference($this->cellAddress($receivableColumns['total'], $piutangRow)));
            $sheet->setCellValueByColumnAndRow($paymentColumn, $row, $this->piutangReference($this->cellAddress($receivableColumns['payment'], $piutangRow)));
            $sheet->setCellValueByColumnAndRow($balanceColumn, $row, $this->piutangReference($this->cellAddress($receivableColumns['balance'], $piutangRow)));
        }
        $totalRow = 4 + $data['dates']->count();
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        $sheet->mergeCells('A'.$totalRow.':B'.$totalRow);
        foreach (range($productStart, $paymentColumn) as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($column.$totalRow, '=SUM('.$column.'4:'.$column.($totalRow - 1).')');
        }
        $sheet->setCellValueByColumnAndRow($balanceColumn, $totalRow, '='.$this->cellAddress($balanceColumn, $totalRow - 1));

        $this->styleSheet($sheet, 'A1:'.$lastColumn.$totalRow, $lastColumn, 4, $totalRow, false);
        $sheet->setAutoFilter('A3:'.$lastColumn.($totalRow - 1));
        $sheet->freezePane('C4');
        $sheet->getPageSetup()->setOrientation('landscape');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->setShowGridlines(false);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (range(3, $balanceColumn) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(16);
        }
    }

    private function writeSalesSummarySheet($sheet, array $data): void
    {
        $products = $data['products'];
        $receivableColumns = $this->receivableColumns($data);
        $qtyStart = 3;
        $qtyEnd = $qtyStart + count($products) - 1;
        $nominalStart = $qtyEnd + 1;
        $nominalEnd = $nominalStart + count($products) - 1;
        $totalNominal = $nominalEnd + 1;
        $dateTransfer = $totalNominal + 2;
        $payment = $dateTransfer + 1;
        $balance = $payment + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($balance);

        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'TANGGAL');
        $sheet->setCellValue('C1', 'QTY');
        $sheet->setCellValueByColumnAndRow($nominalStart, 1, 'NOMINAL UANG');
        $sheet->setCellValueByColumnAndRow($totalNominal, 1, 'JUMLAH UANG');
        $sheet->setCellValueByColumnAndRow($dateTransfer, 1, 'TANGGAL TRANSFER');
        $sheet->setCellValueByColumnAndRow($payment, 1, 'PEMBAYARAN');
        $sheet->setCellValueByColumnAndRow($balance, 1, 'SISA HUTANG');
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:'.$this->cellAddress($qtyEnd, 1));
        $sheet->mergeCells($this->cellAddress($nominalStart, 1).':'.$this->cellAddress($nominalEnd, 1));
        foreach ([$totalNominal, $dateTransfer, $payment, $balance] as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->mergeCells($column.'1:'.$column.'2');
        }
        foreach ($products as $index => $productData) {
            $qtyColumn = Coordinate::stringFromColumnIndex($qtyStart + $index);
            $nominalColumn = Coordinate::stringFromColumnIndex($nominalStart + $index);
            $sheet->setCellValue($qtyColumn.'2', $productData['model']->name ?: $productData['model']->code);
            $sheet->setCellValue($nominalColumn.'2', $productData['model']->name ?: $productData['model']->code);
        }

        foreach ($data['dates'] as $index => $date) {
            $row = 3 + $index;
            $piutangRow = 2 + $index;
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, $date->format('d/m/Y'));
            foreach ($products as $productIndex => $productData) {
                $sourceRow = $productData['rowByDate'][$date->toDateString()];
                $sheet->setCellValueByColumnAndRow($qtyStart + $productIndex, $row, $this->sheetReference($productData['sheet'], 'I'.$sourceRow));
                $sheet->setCellValueByColumnAndRow($nominalStart + $productIndex, $row, $this->sheetReference($productData['sheet'], 'Q'.$sourceRow));
            }
            $sheet->setCellValueByColumnAndRow($totalNominal, $row, '=SUM('.$this->cellAddress($nominalStart, $row).':'.$this->cellAddress($nominalEnd, $row).')');
            $sheet->setCellValueByColumnAndRow($dateTransfer, $row, $this->piutangReference('B'.$piutangRow));
            $sheet->setCellValueByColumnAndRow($payment, $row, $this->piutangReference($this->cellAddress($receivableColumns['payment'], $piutangRow)));
            $sheet->setCellValueByColumnAndRow($balance, $row, $this->piutangReference($this->cellAddress($receivableColumns['balance'], $piutangRow)));
        }
        $totalRow = 3 + $data['dates']->count();
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        $sheet->mergeCells('A'.$totalRow.':B'.$totalRow);
        foreach (range($qtyStart, $payment) as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($column.$totalRow, '=SUM('.$column.'3:'.$column.($totalRow - 1).')');
        }
        $sheet->setCellValueByColumnAndRow($balance, $totalRow, '='.$this->cellAddress($balance, $totalRow - 1));

        $this->styleSheet($sheet, 'A1:'.$lastColumn.$totalRow, $lastColumn, 3, $totalRow, false);
        $sheet->setAutoFilter('A2:'.$lastColumn.($totalRow - 1));
        $sheet->freezePane('C3');
        $sheet->getPageSetup()->setOrientation('landscape');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->setShowGridlines(false);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (range(3, $lastColumn) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(16);
        }
    }

    private function writeReceivableSheet($sheet, array $data): void
    {
        $products = $data['products'];
        $productStart = 3;
        $productEnd = $productStart + count($products) - 1;
        $total = $productEnd + 1;
        $payment = $total + 1;
        $balance = $payment + 1;
        $notes = $balance + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($notes);

        $headers = ['NO', 'TANGGAL'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
            $sheet->mergeCells(Coordinate::stringFromColumnIndex($index + 1).'1:'.Coordinate::stringFromColumnIndex($index + 1).'1');
        }
        foreach ($products as $index => $productData) {
            $sheet->setCellValueByColumnAndRow($productStart + $index, 1, $productData['model']->name ?: $productData['model']->code);
        }
        $sheet->setCellValueByColumnAndRow($total, 1, 'TOTAL');
        $sheet->setCellValueByColumnAndRow($payment, 1, 'PEMBAYARAN');
        $sheet->setCellValueByColumnAndRow($balance, 1, 'SISA HUTANG');
        $sheet->setCellValueByColumnAndRow($notes, 1, 'KETERANGAN');

        foreach ($data['dates'] as $index => $date) {
            $row = 2 + $index;
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, $date->format('d/m/Y'));
            foreach ($products as $productIndex => $productData) {
                $sourceRow = $productData['rowByDate'][$date->toDateString()];
                $sheet->setCellValueByColumnAndRow($productStart + $productIndex, $row, $this->sheetReference($productData['sheet'], 'Q'.$sourceRow));
            }
            $sheet->setCellValueByColumnAndRow($total, $row, '=SUM(C'.$row.':'.$this->cellAddress($productEnd, $row).')');
            $sheet->setCellValueByColumnAndRow($payment, $row, (float) ($data['paymentByDate'][$date->toDateString()] ?? 0));
            $totalCell = $this->cellAddress($total, $row);
            $paymentCell = $this->cellAddress($payment, $row);
            $sheet->setCellValueByColumnAndRow($balance, $row, $index === 0
                ? '='.$totalCell.'-'.$paymentCell
                : '='.$this->cellAddress($balance, $row - 1).'+'.$totalCell.'-'.$paymentCell);
            $sheet->setCellValueByColumnAndRow($notes, $row, 'Penjualan '.$data['scopeLabel']);
        }
        $totalRow = 2 + $data['dates']->count();
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        foreach (range($productStart, $payment) as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($column.$totalRow, '=SUM('.$column.'2:'.$column.($totalRow - 1).')');
        }
        $sheet->setCellValueByColumnAndRow($balance, $totalRow, '='.$this->cellAddress($balance, $totalRow - 1));

        $this->styleSheet($sheet, 'A1:'.$lastColumn.$totalRow, $lastColumn, 2, $totalRow, false);
        $sheet->setAutoFilter('A1:'.$lastColumn.($totalRow - 1));
        $sheet->freezePane('C2');
        $sheet->getPageSetup()->setOrientation('landscape');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->setShowGridlines(false);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (range(3, $lastColumn) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth($columnIndex === $notes ? 34 : 16);
        }
    }

    private function unitFactors(Product $product): array
    {
        $large = $product->satuan_besar && $product->konversi_qty
            ? max(1, (int) round((float) $product->konversi_qty))
            : 1;
        $largest = $product->satuan_terbesar && $product->konversi_qty_terbesar
            ? max(1, (int) round($large * (float) $product->konversi_qty_terbesar))
            : $large;

        return ['large' => $large, 'largest' => $largest];
    }

    private function conversionFormula(string $cell, int $factor): string
    {
        return $cell.'/'.max(1, $factor);
    }

    private function sheetReference(string $sheet, string $cell): string
    {
        return "='".str_replace("'", "''", $sheet)."'!".$cell;
    }

    private function piutangReference(string $cell): string
    {
        return "='PIUTANG'!".$cell;
    }

    private function receivableColumns(array $data): array
    {
        $productStart = 3;
        $productEnd = $productStart + count($data['products']) - 1;

        return [
            'total' => $productEnd + 1,
            'payment' => $productEnd + 2,
            'balance' => $productEnd + 3,
        ];
    }

    private function cellAddress(int $columnIndex, int $row): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex).$row;
    }

    private function scopeLabel(): string
    {
        return match ($this->scope) {
            self::SCOPE_PUSAT => 'PENJUALAN PUSAT',
            self::SCOPE_CABANG => 'PENJUALAN CABANG',
            default => 'PENJUALAN PUSAT & CABANG',
        };
    }

    private function styleSheet($sheet, string $range, string $lastColumn, int $dataStartRow, int $totalRow, bool $productSheet): void
    {
        $sheet->getStyle($range)->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $hasTitleRow = $sheet->getTitle() !== 'PIUTANG';
        if ($hasTitleRow) {
            $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $headerStartRow = $hasTitleRow ? 2 : 1;
        $headerEndRow = $productSheet ? 3 : ($hasTitleRow ? 2 : 1);
        $sheet->getStyle('A'.$headerStartRow.':'.$lastColumn.$headerEndRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A'.$totalRow.':'.$lastColumn.$totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F0D9']],
        ]);
        if ($dataStartRow <= $totalRow - 1) {
            $sheet->getStyle('A'.$dataStartRow.':'.$lastColumn.($totalRow - 1))
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('O'.$dataStartRow.':'.$lastColumn.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('Q'.$dataStartRow.':R'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getRowDimension(1)->setRowHeight($hasTitleRow ? 24 : 28);
        $sheet->getRowDimension(2)->setRowHeight(28);
        if ($productSheet) {
            $sheet->getRowDimension(3)->setRowHeight(22);
        }
    }
}
