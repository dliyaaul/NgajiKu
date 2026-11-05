import { BookHeart, BookOpen, Languages, type LucideIcon } from 'lucide-react';

import type { ProgramKey, StatusKehadiran } from '@/types';

export type Tone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info' | 'sky' | 'amber' | 'violet';

interface ProgramMeta {
    label: string;
    description: string;
    tone: Extract<Tone, 'sky' | 'amber' | 'violet'>;
    icon: LucideIcon;
    hasHafalan: boolean;
}

/**
 * Tampilan tiap program. Warna sengaja berbeda dari warna status kehadiran
 * dan dari warna brand (emerald).
 */
export const PROGRAM: Record<ProgramKey, ProgramMeta> = {
    bbaq: {
        label: 'BBAQ',
        description: 'Bimbingan Belajar Al-Qur’an',
        tone: 'sky',
        icon: BookOpen,
        hasHafalan: false,
    },
    tahfidz: {
        label: 'Tahfidz',
        description: 'Program penghafal Al-Qur’an beserta setoran hafalan',
        tone: 'amber',
        icon: BookHeart,
        hasHafalan: true,
    },
    tarjamah: {
        label: 'Tarjamah',
        description: 'Tarjamah Lafdziyah Al-Qur’an',
        tone: 'violet',
        icon: Languages,
        hasHafalan: false,
    },
};

export const STATUSES: StatusKehadiran[] = ['Hadir', 'Izin', 'Sakit', 'Alpha'];

export const BELUM_ABSEN = 'Belum Absen';

export function statusTone(status: string): Tone {
    switch (status) {
        case 'Hadir':
            return 'success';
        case 'Izin':
            return 'info';
        case 'Sakit':
            return 'warning';
        case 'Alpha':
            return 'danger';
        default:
            return 'neutral';
    }
}

/** Titik warna status (kelas lengkap agar terbaca Tailwind). */
export const TONE_DOT: Partial<Record<Tone, string>> = {
    success: 'bg-success-600',
    info: 'bg-indigo-600',
    warning: 'bg-warning-500',
    danger: 'bg-error-600',
    neutral: 'bg-gray-400',
};
