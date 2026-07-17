<?php

namespace App\Support;

use App\Models\Product;

class ProductUnitConverter
{
    public function normalize(Product $product, int|float|string $qty, ?string $unit = null): int
    {
        $factor = $this->factorForUnit($product, $unit);
        $amount = $this->parseNumeric($qty);

        return (int) round($amount * $factor);
    }

    public function factorForUnit(Product $product, ?string $unit = null): int
    {
        $unitKey = $this->normalizeUnitName($unit ?: $product->satuan);
        $factors = $this->unitMultipliers($product);

        return $factors[$unitKey] ?? 1;
    }

    public function unitMultipliers(Product $product): array
    {
        $baseUnit = $product->satuan ?: 'PCS';
        $factors = [
            $this->normalizeUnitName($baseUnit) => 1,
        ];

        if ($product->satuan_besar && $product->konversi_qty) {
            $factors[$this->normalizeUnitName($product->satuan_besar)] = (int) round((float) $product->konversi_qty);
        }

        if ($product->satuan_terbesar && $product->konversi_qty && $product->konversi_qty_terbesar) {
            $factors[$this->normalizeUnitName($product->satuan_terbesar)] = (int) round((float) $product->konversi_qty * (float) $product->konversi_qty_terbesar);
        }

        return $factors;
    }

    public function defaultInputUnit(Product $product, string $channel = 'warehouse'): string
    {
        return match ($channel) {
            'warehouse', 'supplier', 'distribution' => $product->satuan_besar ?: ($product->satuan ?: 'PCS'),
            'branch', 'sales', 'customer' => $product->satuan ?: 'PCS',
            default => $product->satuan ?: 'PCS',
        };
    }

    public function display(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $baseUnit = $product->satuan ?: 'PCS';
        $bigUnit = $product->satuan_besar;
        $largestUnit = $product->satuan_terbesar;
        $bigFactor = $product->konversi_qty ? (int) round((float) $product->konversi_qty) : null;
        $largestFactor = ($bigFactor && $product->konversi_qty_terbesar)
            ? $bigFactor * (int) round((float) $product->konversi_qty_terbesar)
            : null;

        if (! $bigFactor || ! $bigUnit) {
            return $this->formatPart($qty, $baseUnit);
        }

        $remaining = $qty;
        $parts = [];

        if ($largestFactor && $largestUnit) {
            $largestQty = intdiv($remaining, $largestFactor);
            if ($largestQty > 0) {
                $parts[] = $this->formatPart($largestQty, $largestUnit);
                $remaining %= $largestFactor;
            }
        }

        $bigQty = intdiv($remaining, $bigFactor);
        if ($bigQty > 0) {
            $parts[] = $this->formatPart($bigQty, $bigUnit);
            $remaining %= $bigFactor;
        }

        if ($remaining > 0 || $parts === []) {
            $parts[] = $this->formatPart($remaining, $baseUnit);
        }

        return implode(' ', $parts);
    }

    public function detailedDisplay(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $base = $this->formatPart($qty, $product->satuan ?: 'PCS');
        $converted = $this->display($product, $qty);

        return $converted === $base ? $base : "{$base} | {$converted}";
    }

    public function stockSummaryDisplay(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $baseUnit = $product->satuan ?: 'PCS';
        $bigUnit = $product->satuan_besar;
        $largestUnit = $product->satuan_terbesar;
        $bigFactor = $product->konversi_qty ? (int) round((float) $product->konversi_qty) : null;
        $largestFactor = ($bigFactor && $product->konversi_qty_terbesar)
            ? $bigFactor * (int) round((float) $product->konversi_qty_terbesar)
            : null;

        $parts = [
            $this->formatPart($qty, $baseUnit),
        ];

        if ($bigFactor && $bigUnit) {
            $parts[] = $this->formatFractionalPart($qty / $bigFactor, $bigUnit);
        }

        if ($largestFactor && $largestUnit) {
            $parts[] = $this->formatFractionalPart($qty / $largestFactor, $largestUnit);
        }

        return implode(' | ', array_filter($parts));
    }

    protected function parseNumeric(int|float|string $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = trim($value);
        $normalized = str_replace([' ', ','], ['', '.'], $normalized);

        return (float) $normalized;
    }

    protected function normalizeUnitName(?string $unit): string
    {
        return mb_strtolower(trim((string) $unit));
    }

    protected function formatPart(int $qty, string $unit): string
    {
        return number_format($qty, 0, ',', '.').' '.$unit;
    }

    protected function formatFractionalPart(float $qty, string $unit): string
    {
        if ((float) (int) $qty === $qty) {
            return number_format($qty, 0, ',', '.').' '.$unit;
        }

        return rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',').' '.$unit;
    }
}
