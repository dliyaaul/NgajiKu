export type Role = 'admin' | 'staff' | 'pengajar' | 'santri';

export type ProgramKey = 'bbaq' | 'tahfidz' | 'tarjamah';

export type StatusKehadiran = 'Hadir' | 'Izin' | 'Sakit' | 'Alpha';

/** Jumlah per status kehadiran; kosong (`[]` dari PHP) bila belum ada absensi. */
export type Rekap = Partial<Record<StatusKehadiran, number>>;

export interface AuthUser {
    id: string;
    username: string;
    email: string | null;
    avatar: string | null;
    role: Role;
}

export interface SharedProps {
    auth: AuthUser | null;
    flash: {
        success: string | null;
        error: string | null;
        status: string | null;
    };
    tanggal: {
        iso: string;
        masehi: string;
        hijriah: string | null;
    };
    errors: Record<string, string>;
    [key: string]: unknown;
}

/** Item breadcrumb; `href` kosong = halaman saat ini. */
export interface BreadcrumbItem {
    label: string;
    href?: string;
}

export interface RiwayatItem {
    program: ProgramKey;
    tanggal: string | null;
    status: string;
    materi: string | null;
    metode: string | null;
    pengajar: string | null;
    jenis_tahfidz: string | null;
    surat: string | null;
    ayat_mulai: number | null;
    ayat_selesai: number | null;
    keterangan: string | null;
}

export interface PertemuanRingkas {
    program: ProgramKey;
    id: string;
    tanggal: string;
    kelas: string;
    kelas_id: string;
    materi: string | null;
    rekap: Rekap;
    editable: boolean;
    batas_edit: string | null;
}

export interface KelasRingkas {
    id: string;
    nama_kelas: string;
}

export interface Opsi {
    id: string;
    nama: string;
}

// ---------------------------------------------------------------- Pembayaran

export type StatusTagihan = 'belum_bayar' | 'menunggu_verifikasi' | 'lunas' | 'ditolak' | 'dibatalkan';

export type StatusPembayaran = 'menunggu_verifikasi' | 'diterima' | 'ditolak';

export interface Rekening {
    id: string;
    nama_bank: string;
    nomor_rekening: string;
    nama_pemilik: string;
    keterangan: string | null;
    aktif: boolean;
    urutan: number;
}

export interface TagihanItem {
    id: string;
    deskripsi: string;
    jenis: string;
    tanggal: string | null;
    jumlah: number;
    satuan: string;
    harga_satuan: number;
    subtotal: number;
}

export interface LogPembayaran {
    status_lama: string | null;
    status_baru: string;
    catatan: string | null;
    created_at: string | null;
}

export interface Pembayaran {
    id: string;
    nomor_pembayaran: string;
    tagihan_id: string;
    tanggal_transfer: string;
    nominal_transfer: number;
    nama_pengirim: string | null;
    rekening_pengirim: string | null;
    catatan: string | null;
    status: StatusPembayaran;
    status_label: string;
    alasan_penolakan: string | null;
    diverifikasi_at: string | null;
    bukti_url: string;
    bukti_pdf: boolean;
    created_at: string | null;
    rekening?: Rekening;
    tagihan?: {
        id: string;
        nomor_tagihan: string;
        total: number;
        periode_mulai: string;
        periode_selesai: string;
        nama_santri: string | null;
        nama_lembaga: string | null;
    };
    log?: LogPembayaran[];
}

export interface Tagihan {
    id: string;
    nomor_tagihan: string;
    tipe_pembayar: 'santri' | 'koordinator_lembaga';
    periode_mulai: string;
    periode_selesai: string;
    tanggal_tagihan: string;
    tanggal_jatuh_tempo: string | null;
    subtotal: number;
    diskon: number;
    total: number;
    status: StatusTagihan;
    status_label: string;
    bisa_dibayar: boolean;
    nama_santri: string | null;
    nama_program: string | null;
    nama_lembaga: string | null;
    created_at: string | null;
    items?: TagihanItem[];
    pembayaran?: Pembayaran[];
}

export interface Pagination {
    halaman: number;
    total_halaman: number;
    total: number;
    sebelumnya: string | null;
    berikutnya: string | null;
}
