<?php

namespace App\Helpers;

use Carbon\CarbonInterface;
use IntlDateFormatter;

class TanggalHelper
{
    /**
     * Tanggal Masehi berbahasa Indonesia, mis. "Kamis, 5 November 2026".
     */
    public static function masehi(?CarbonInterface $tanggal = null): string
    {
        return ($tanggal ?? now())->locale('id')->translatedFormat('l, j F Y');
    }

    /**
     * Tanggal Hijriah (kalender Umm al-Qura), mis. "25 Jumadilawal 1448 H".
     * Bisa selisih satu hari dari penetapan Kemenag. Null bila ekstensi intl tidak tersedia.
     */
    public static function hijriah(?CarbonInterface $tanggal = null): ?string
    {
        if (! class_exists(IntlDateFormatter::class)) {
            return null;
        }

        $tanggal ??= now();

        $formatter = new IntlDateFormatter(
            'id_ID@calendar=islamic-umalqura',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $tanggal->getTimezone(),
            IntlDateFormatter::TRADITIONAL,
        );

        $hasil = $formatter->format($tanggal->toDateTimeImmutable());

        return $hasil === false ? null : $hasil;
    }
}
