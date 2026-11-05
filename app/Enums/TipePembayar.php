<?php

namespace App\Enums;

/**
 * Pihak yang membayar tagihan: santri (layanan Reguler/Privat) atau
 * koordinator lembaga (layanan Lembaga).
 */
enum TipePembayar: string
{
    case Santri = 'santri';
    case KoordinatorLembaga = 'koordinator_lembaga';
}
