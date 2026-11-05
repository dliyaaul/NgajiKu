/** Gabungkan kelas CSS; nilai kosong/false diabaikan. */
export function cn(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}

/** "2026-10-03" → Date lokal (tanpa pergeseran zona waktu). */
function parseTanggal(tanggal: string): Date {
    const [tahun, bulan, hari] = tanggal.slice(0, 10).split('-').map(Number);

    return new Date(tahun, bulan - 1, hari);
}

const FORMAT_TANGGAL = {
    /** "05 Nov 2026" */
    ringkas: { day: '2-digit', month: 'short', year: 'numeric' },
    /** "Kam, 05 Nov 2026" */
    pendek: { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' },
    /** "Kamis, 05 Nov 2026" */
    sedang: { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' },
    /** "Kamis, 05 November 2026" */
    panjang: { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' },
} satisfies Record<string, Intl.DateTimeFormatOptions>;

export function formatTanggal(tanggal: string | null | undefined, gaya: keyof typeof FORMAT_TANGGAL = 'pendek'): string {
    if (!tanggal) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', FORMAT_TANGGAL[gaya]).format(parseTanggal(tanggal));
}

/** Tanggal & jam dari timestamp ISO, mis. "06 Nov 2026, 14.30". */
export function formatWaktu(iso: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

/** Sisa waktu hingga `iso` dalam satu satuan, mis. "3 jam" atau "45 menit". */
export function sisaWaktu(iso: string): string {
    const detik = Math.max(0, Math.floor((Date.parse(iso) - Date.now()) / 1000));

    const satuan: Array<[number, string]> = [
        [86400, 'hari'],
        [3600, 'jam'],
        [60, 'menit'],
    ];

    for (const [ukuran, nama] of satuan) {
        if (detik >= ukuran) {
            return `${Math.floor(detik / ukuran)} ${nama}`;
        }
    }

    return `${detik} detik`;
}

/** Inisial maksimal dua huruf dari nama, mis. "Aisyah Putri" → "AP". */
export function inisial(nama: string): string {
    return nama
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((kata) => kata.charAt(0).toUpperCase())
        .join('');
}

/** Baca/tulis localStorage dengan aman (bisa diblokir di mode privat). */
export function readPreference(key: string): string | null {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
}

export function writePreference(key: string, value: string): void {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Penyimpanan diblokir: preferensi tetap berlaku untuk sesi ini.
    }
}
