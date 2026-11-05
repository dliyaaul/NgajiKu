import type { Tone } from '@/lib/program';
import type { StatusPembayaran, StatusTagihan } from '@/types';

/** "150000" → "Rp150.000" */
export function formatRupiah(nilai: number | string | null | undefined): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(nilai ?? 0));
}

export const STATUS_TAGIHAN: Record<StatusTagihan, { label: string; tone: Tone }> = {
    belum_bayar: { label: 'Belum Bayar', tone: 'warning' },
    menunggu_verifikasi: { label: 'Menunggu Verifikasi', tone: 'info' },
    lunas: { label: 'Lunas', tone: 'success' },
    ditolak: { label: 'Ditolak', tone: 'danger' },
    dibatalkan: { label: 'Dibatalkan', tone: 'neutral' },
};

export const STATUS_TAGIHAN_URUT: StatusTagihan[] = ['belum_bayar', 'menunggu_verifikasi', 'ditolak', 'lunas', 'dibatalkan'];

export const STATUS_PEMBAYARAN: Record<StatusPembayaran, { label: string; tone: Tone }> = {
    menunggu_verifikasi: { label: 'Menunggu Verifikasi', tone: 'info' },
    diterima: { label: 'Diterima', tone: 'success' },
    ditolak: { label: 'Ditolak', tone: 'danger' },
};

/** Periode tagihan ringkas, mis. "1–30 Nov 2026" atau "28 Okt – 3 Nov 2026". */
export function formatPeriode(mulai: string, selesai: string): string {
    const awal = new Date(`${mulai}T00:00:00`);
    const akhir = new Date(`${selesai}T00:00:00`);
    const bulanSama = awal.getMonth() === akhir.getMonth() && awal.getFullYear() === akhir.getFullYear();

    if (bulanSama) {
        return `${awal.getDate()}–${new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(akhir)}`;
    }

    const format = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });

    return `${format.format(awal)} – ${new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(akhir)}`;
}
