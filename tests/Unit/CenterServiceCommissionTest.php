<?php

namespace Tests\Unit;

use App\Support\CenterServiceCommission;
use PHPUnit\Framework\TestCase;

class CenterServiceCommissionTest extends TestCase
{
    public function test_fixed_commission(): void
    {
        $this->assertSame(15.0, CenterServiceCommission::commissionAmount(100, 15, 'fixed'));
        $this->assertSame(85.0, CenterServiceCommission::netForCenter(100, 15, 'fixed'));
    }

    public function test_percent_commission(): void
    {
        $this->assertSame(20.0, CenterServiceCommission::commissionAmount(100, 20, 'percent'));
        $this->assertSame(80.0, CenterServiceCommission::netForCenter(100, 20, 'percent'));
        $this->assertSame(100.0, CenterServiceCommission::commissionAmount(100, 150, 'percent'));
    }

    public function test_format_pivot(): void
    {
        $this->assertSame('15.00 ر.س', CenterServiceCommission::formatPivot(15, 'fixed'));
        $this->assertSame('20%', CenterServiceCommission::formatPivot(20, 'percent'));
    }
}
