<?php

namespace Tests\Unit;

use App\Helpers\TanggalHelper;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class TanggalHelperTest extends TestCase
{
    #[RequiresPhpExtension('intl')]
    public function test_hijri_date_uses_umm_al_qura_calendar(): void
    {
        $tanggal = CarbonImmutable::parse('2026-11-05', 'Asia/Jakarta');

        $this->assertSame('25 Jumadilawal 1448 H', TanggalHelper::hijriah($tanggal));
    }

    #[RequiresPhpExtension('intl')]
    public function test_hijri_date_follows_ramadan(): void
    {
        $tanggal = CarbonImmutable::parse('2026-02-20', 'Asia/Jakarta');

        $this->assertStringContainsString('Ramadan 1447 H', TanggalHelper::hijriah($tanggal));
    }
}
