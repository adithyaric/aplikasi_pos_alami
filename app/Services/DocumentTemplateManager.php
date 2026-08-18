<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentTemplateManager
{
    public const PURCHASE_DOCX = 'pembelian-docx';
    public const PURCHASE_XLSX = 'pembelian-xlsx';
    public const SALES_INVOICE_XLSX = 'penjualan-invoice';
    public const SALES_DELIVERY_XLSX = 'penjualan-surat-jalan';

    public function definitions(): array
    {
        return [
            self::PURCHASE_DOCX => [
                'label' => 'Pembelian / PO (DOCX)',
                'short_label' => 'PO DOCX',
                'extension' => 'docx',
                'setting_key' => 'purchase_template_docx',
                'default_path' => base_path('template_alami_pembelian.docx'),
                'default_name' => 'template_alami_pembelian.docx',
            ],
            self::PURCHASE_XLSX => [
                'label' => 'Pembelian / PO (XLSX)',
                'short_label' => 'PO XLSX',
                'extension' => 'xlsx',
                'setting_key' => 'purchase_template_xlsx',
                'default_path' => base_path('template_alami_pembelian.xlsx'),
                'default_name' => 'template_alami_pembelian.xlsx',
            ],
            self::SALES_INVOICE_XLSX => [
                'label' => 'Penjualan / Invoice (XLSX)',
                'short_label' => 'Invoice XLSX',
                'extension' => 'xlsx',
                'setting_key' => 'sales_invoice_template_xlsx',
                'default_path' => base_path('template_alami_penjualan_invoice.xlsx'),
                'default_name' => 'template_alami_penjualan_invoice.xlsx',
            ],
            self::SALES_DELIVERY_XLSX => [
                'label' => 'Penjualan / Surat Jalan (XLSX)',
                'short_label' => 'Surat Jalan XLSX',
                'extension' => 'xlsx',
                'setting_key' => 'sales_delivery_template_xlsx',
                'default_path' => base_path('template_alami_penjualan_surat-jalan.xlsx'),
                'default_name' => 'template_alami_penjualan_surat-jalan.xlsx',
            ],
        ];
    }

    public function settings(): array
    {
        if (! Storage::disk('public')->exists('settings.json')) {
            return [];
        }

        return json_decode(Storage::disk('public')->get('settings.json'), true) ?? [];
    }

    public function metadata(): array
    {
        $settings = $this->settings();

        return collect($this->definitions())
            ->mapWithKeys(fn (array $definition, string $type) => [$type => $this->resolve($type, $settings)])
            ->all();
    }

    public function resolve(string $type, ?array $settings = null): array
    {
        $definition = $this->definition($type);
        $settings ??= $this->settings();
        $customPath = $settings[$definition['setting_key']] ?? null;

        if ($customPath && Storage::disk('public')->exists($customPath)) {
            return [
                ...$definition,
                'source' => 'custom',
                'label' => basename($customPath),
                'path' => Storage::disk('public')->path($customPath),
                'download_name' => basename($customPath),
            ];
        }

        return [
            ...$definition,
            'source' => 'default',
            'label' => $definition['default_name'],
            'path' => $definition['default_path'],
            'download_name' => $definition['default_name'],
        ];
    }

    /**
     * Resolve a purchase template for a supplier, falling back to the legacy
     * global purchase template and then the bundled default template.
     */
    public function resolvePurchase(string $type, Supplier $supplier, ?array $settings = null): array
    {
        $supplierPath = $supplier->po_template;

        if ($supplierPath
            && $this->purchaseTypeFromPath($supplierPath) === $type
            && Storage::disk('public')->exists($supplierPath)) {
            return $this->supplierPurchaseMetadata($supplierPath, $supplier);
        }

        return $this->resolve($type, $settings);
    }

    public function resolveSupplierPurchaseTemplate(Supplier $supplier, ?array $settings = null): array
    {
        $supplierPath = $supplier->po_template;
        if ($supplierPath
            && Storage::disk('public')->exists($supplierPath)
            && $this->purchaseTypeFromPath($supplierPath)) {
            return $this->supplierPurchaseMetadata($supplierPath, $supplier);
        }

        $settings ??= $this->settings();
        foreach ([self::PURCHASE_XLSX, self::PURCHASE_DOCX] as $type) {
            $template = $this->resolve($type, $settings);
            if ($template['source'] === 'custom') {
                return [...$template, 'type' => $type];
            }
        }

        return [
            ...$this->resolve(self::PURCHASE_XLSX, $settings),
            'type' => self::PURCHASE_XLSX,
        ];
    }

    public function purchaseTemplateType(Supplier $supplier, ?array $settings = null): string
    {
        return $this->resolveSupplierPurchaseTemplate($supplier, $settings)['type'];
    }

    public function storeSupplierPurchaseTemplate(Supplier $supplier, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        abort_unless(in_array($extension, ['docx', 'xlsx'], true), 422, 'Template PO harus DOCX atau XLSX.');

        $oldPath = $supplier->po_template;
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $baseName = $baseName ?: 'template-po-'.$supplier->id;
        $path = 'templates/documents/suppliers/'.$supplier->id.'/'.$baseName.'.'.$extension;

        $storedPath = $file->storeAs(dirname($path), basename($path), 'public');
        if ($oldPath && $oldPath !== $storedPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $storedPath;
    }

    public function resetSupplierPurchaseTemplate(Supplier $supplier): void
    {
        $path = $supplier->po_template;

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function supplierPurchaseMetadata(string $path, Supplier $supplier): array
    {
        $type = $this->purchaseTypeFromPath($path);

        return [
            ...$this->definition($type),
            'source' => 'supplier',
            'label' => basename($path),
            'path' => Storage::disk('public')->path($path),
            'download_name' => basename($path),
            'supplier_id' => $supplier->id,
            'type' => $type,
        ];
    }

    private function purchaseTypeFromPath(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'docx' => self::PURCHASE_DOCX,
            'xlsx' => self::PURCHASE_XLSX,
            default => null,
        };
    }

    public function store(string $type, UploadedFile $file, array $settings): string
    {
        $definition = $this->definition($type);
        $oldPath = $settings[$definition['setting_key']] ?? null;

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $file->storeAs(
            'templates/documents',
            $type.'.'.$definition['extension'],
            'public'
        );
    }

    public function reset(string $type, array $settings): void
    {
        $definition = $this->definition($type);
        $path = $settings[$definition['setting_key']] ?? null;

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function definition(string $type): array
    {
        abort_unless(array_key_exists($type, $this->definitions()), 404);

        return $this->definitions()[$type];
    }

    public function variableGroups(): array
    {
        return [
            'Bisa dipakai di semua template' => [
                'Perusahaan — dari Dashboard Setting' => [
                    '{{company.name}}', '{{company.address}}', '{{company.phone}}',
                    '{{company.email}}', '{{company.website}}', '{{company.logo}}', '{{company.nib}}',
                    '{{company.ttd}}', '{{company.nppbkc}}', '{{company.gol_pab}}',
                ],
                'Baris item umum — alias kompatibilitas' => [
                    '{{item.no}}', '{{item.code}}', '{{item.name}}', '{{item.qty}}',
                    '{{item.unit}}', '{{item.price}}', '{{item.discount}}', '{{item.subtotal}}',
                ],
            ],
            'Pembelian / PO' => [
                'Data pembelian' => [
                    '{{purchase.number}}', '{{purchase.date}}', '{{purchase.location}}', '{{purchase.total}}',
                ],
                'Supplier' => [
                    '{{supplier.code}}', '{{supplier.name}}', '{{supplier.address}}',
                    '{{supplier.phone}}',
                ],
                'Baris item Pembelian — otomatis mengikuti jumlah item' => [
                    '{{purchase.items.no}}', '{{purchase.items.code}}', '{{purchase.items.name}}',
                    '{{purchase.items.qty}}', '{{purchase.items.qty_besar}}',
                    '{{purchase.items.qty_terbesar}}', '{{purchase.items.qty_total}}',
                    '{{purchase.items.unit}}',
                    '{{purchase.items.price}}', '{{purchase.items.discount}}', '{{purchase.items.subtotal}}',
                ],
                'Alias kompatibilitas template supplier lama' => [
                    '{{sale.number}}', '{{sale.date}}', '{{sale.subtotal}}',
                    '{{sale.old_debt}}', '{{sale.shipping_cost}}', '{{sale.payment}}',
                    '{{sale.new_debt}}', '{{buyer.name}}', '{{buyer.address}}',
                    '{{buyer.company_name}}', '{{buyer.phone}}', '{{buyer.email}}',
                    '{{sale.items.no}}', '{{sale.items.code}}',
                    '{{sale.items.name}}', '{{sale.items.qty}}', '{{sale.items.unit}}',
                    '{{sale.items.discount}}', '{{sale.items.price}}', '{{sale.items.subtotal}}',
                ],
            ],
            'Penjualan / Invoice / Surat Jalan' => [
                'Nomor, tanggal, dan nilai' => [
                    '{{sale.number}}', '{{sale.date}}', '{{sale.subtotal}}',
                    '{{sale.discount}}', '{{sale.total}}',
                ],
                'Pembayaran dan saldo' => [
                    '{{sale.old_debt}}', '{{sale.shipping_cost}}', '{{sale.payment}}',
                    '{{sale.new_debt}}', '{{sale.payment_type}}', '{{sale.payment_status}}',
                ],
                'Alias pembayaran lama' => [
                    '{{sale.paid}}',
                ],
                'Pembeli' => [
                    '{{buyer.type}}', '{{buyer.name}}', '{{buyer.company_name}}',
                    '{{buyer.address}}', '{{buyer.phone}}', '{{buyer.email}}',
                ],
                'Petugas penjualan' => [
                    '{{operator.name}}', '{{salesman.name}}',
                ],
                'Baris item Penjualan — otomatis mengikuti jumlah item' => [
                    '{{sale.items.no}}', '{{sale.items.code}}', '{{sale.items.name}}',
                    '{{sale.items.qty}}', '{{sale.items.unit}}',
                    '{{sale.items.price}}', '{{sale.items.discount}}', '{{sale.items.subtotal}}',
                ],
            ],
        ];
    }
}
