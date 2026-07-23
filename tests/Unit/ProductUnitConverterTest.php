<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Support\ProductUnitConverter;
use PHPUnit\Framework\TestCase;

class ProductUnitConverterTest extends TestCase
{
    public function test_it_normalizes_slop_and_ball_into_base_pack_units(): void
    {
        $product = new Product([
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 25,
        ]);

        $converter = new ProductUnitConverter();

        $this->assertSame(20, $converter->normalize($product, 2, 'Slop'));
        $this->assertSame(250, $converter->normalize($product, 1, 'Ball'));
        $this->assertSame('1 Ball 1 Slop 5 Pack', $converter->display($product, 265));
        $this->assertSame('260 Pack | 1 Ball 1 Slop', $converter->detailedDisplay($product, 260));
    }

    public function test_it_uses_channel_specific_input_units(): void
    {
        $product = new Product([
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 25,
        ]);

        $converter = new ProductUnitConverter();

        $this->assertSame('Slop', $converter->defaultInputUnit($product, 'distribution'));
        $this->assertSame('Pack', $converter->defaultInputUnit($product, 'sales'));
        $this->assertSame(['Slop', 'Ball', 'Pack'], array_column($converter->inputUnits($product, 'distribution'), 'value'));
        $this->assertSame(['Pack'], array_column($converter->inputUnits($product, 'sales'), 'value'));
    }
}
