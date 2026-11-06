
import {
    ArrowLeftRight,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    GraduationCap,
    UserPlus,
    type LucideIcon,
} from 'lucide-react';

import { Card } from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import { formatWaktu } from '@/lib/utils';

interface Aktivitas {
    id: string;
    jenis: string;
    judul: string;
    deskripsi: string;
    waktu: string;
}

interface Props {
    aktivitas: Aktivitas[];
}

const IKON_AKTIVITAS: Record<string, LucideIcon> = {
    santri_baru: UserPlus,
    pendaftaran_program: BookOpen,
    perpindahan_kelas: ArrowLeftRight,
    pertemuan: CalendarDays,
    verifikasi_pembayaran: CheckCircle2,
};

export function AktivitasTerbaru({ aktivitas }: Props) {
    return (
        <Card
            title="Aktivitas Terbaru"
            description="Aktivitas akademik dan keuangan yang baru terjadi."
            padded={false}
        >
            {aktivitas.length === 0 ? (
                <div className="flex flex-col items-center px-6 py-10 text-center">
                    <span className="mb-3 grid size-11 place-items-center rounded-2xl bg-surface-muted text-fg-muted">
                        <Icon icon={GraduationCap} className="size-5" />
                    </span>
                    <p className="font-semibold text-fg">Belum ada aktivitas</p>
                    <p className="mt-1 text-sm text-fg-muted">
                        Aktivitas terbaru akan muncul di sini ketika ada data baru.
                    </p>
                </div>
            ) : (
                <ul className="divide-y divide-line">
                    {aktivitas.map((item) => {
                        const ikon = IKON_AKTIVITAS[item.jenis] ?? CalendarDays;

                        return (
                            <li
                                key={`${item.jenis}-${item.id}`}
                                className="flex items-start gap-3 px-5 py-4 sm:px-6"
                            >
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-400/10 dark:text-brand-300">
                                    <Icon icon={ikon} className="size-5" />
                                </span>

                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-fg">{item.judul}</p>
                                    <p className="mt-0.5 text-sm text-fg-muted">
                                        {item.deskripsi}
                                    </p>
                                    <time
                                        dateTime={item.waktu}
                                        className="mt-1.5 block text-xs text-fg-subtle"
                                    >
                                        {formatWaktu(item.waktu)}
                                    </time>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </Card>
    );
}
