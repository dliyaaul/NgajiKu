import { BookMarked, Building2, GraduationCap, Layers, Repeat2, ShieldCheck, UserCog, Users, type LucideIcon } from 'lucide-react';

import type { Tone } from '@/lib/program';
import lpq from '@/routes/admin/lpq';

export type Peran = 'admin' | 'staff' | 'pengajar' | 'santri';

export type Jenis = 'reguler' | 'privat' | 'lembaga';

export const PBM = ['Offline', 'Online'] as const;

interface PeranMeta {
    label: string;
    jamak: string;
    deskripsi: string;
    icon: LucideIcon;
}

/** Tampilan tiap peran akun pada manajemen LPQ. */
export const PERAN: Record<Peran, PeranMeta> = {
    admin: { label: 'Admin', jamak: 'Admin', deskripsi: 'Akses penuh ke seluruh data dan pengaturan LPQ.', icon: ShieldCheck },
    staff: { label: 'Staff', jamak: 'Staff', deskripsi: 'Tenaga administrasi yang membantu operasional LPQ.', icon: UserCog },
    pengajar: { label: 'Pengajar', jamak: 'Pengajar', deskripsi: 'Ustadz/ustadzah pemegang kelas dan pengisi absensi.', icon: GraduationCap },
    santri: { label: 'Santri', jamak: 'Santri', deskripsi: 'Peserta didik beserta program dan kelasnya.', icon: Users },
};

export const JENIS: Record<Jenis, { tone: Tone; deskripsi: string }> = {
    reguler: { tone: 'brand', deskripsi: 'Kelas bersama di LPQ, tarif per bulan.' },
    privat: { tone: 'violet', deskripsi: 'Belajar perorangan, tarif per pertemuan.' },
    lembaga: { tone: 'amber', deskripsi: 'Kelas di lembaga mitra, satu lembaga satu kelas.' },
};

export interface MenuLpqItem {
    label: string;
    href: string;
    icon: LucideIcon;
}

/** Sub-menu manajemen LPQ, dikelompokkan. */
export function menuLpq(): Array<{ judul: string; items: MenuLpqItem[] }> {
    return [
        {
            judul: 'Pengguna',
            items: [
                ...(Object.keys(PERAN) as Peran[]).map((peran) => ({
                    label: PERAN[peran].jamak,
                    href: lpq.pengguna.index.url(peran),
                    icon: PERAN[peran].icon,
                })),
                { label: 'Pengajar Pengganti', href: lpq.pengajarPengganti.index.url(), icon: Repeat2 },
            ],
        },
        {
            judul: 'Akademik',
            items: [
                { label: 'Jenis Kelas', href: lpq.jenisKelas.index.url(), icon: Layers },
                { label: 'Program Santri', href: lpq.program.index.url(), icon: BookMarked },
                { label: 'Lembaga', href: lpq.lembaga.index.url(), icon: Building2 },
            ],
        },
    ];
}

/** Hapus parameter kosong agar URL filter tetap bersih. */
export function queryBersih(query: Record<string, string | null | undefined>): Record<string, string> {
    return Object.fromEntries(Object.entries(query).filter((entry): entry is [string, string] => Boolean(entry[1]?.trim())));
}
