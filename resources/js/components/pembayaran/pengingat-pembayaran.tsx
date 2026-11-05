import { ArrowRight, BadgeCheck, ReceiptText, type LucideIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Icon } from '@/components/ui/icon';

/** Kartu pengingat di dashboard (tagihan belum dibayar / pembayaran menunggu verifikasi). */
export function PengingatPembayaran({
    jenis,
    judul,
    keterangan,
    href,
    aksi,
}: {
    jenis: 'tagihan' | 'verifikasi';
    judul: string;
    keterangan: string;
    href: string;
    aksi: string;
}) {
    const icon: LucideIcon = jenis === 'tagihan' ? ReceiptText : BadgeCheck;

    return (
        <section className="mb-6 flex flex-col gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-4 sm:flex-row sm:items-center sm:p-5 dark:border-warning-400/20 dark:bg-warning-400/10">
            <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-white text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                <Icon icon={icon} className="size-5" />
            </span>
            <div className="min-w-0 flex-1">
                <h2 className="font-semibold text-warning-900 dark:text-warning-100">{judul}</h2>
                <p className="text-sm text-warning-800 dark:text-warning-200">{keterangan}</p>
            </div>
            <Button variant="secondary" href={href} iconRight={ArrowRight}>
                {aksi}
            </Button>
        </section>
    );
}
