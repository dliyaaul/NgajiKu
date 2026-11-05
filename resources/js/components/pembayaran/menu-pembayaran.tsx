import { Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Landmark, ReceiptText, Tags, type LucideIcon } from 'lucide-react';

import { Icon } from '@/components/ui/icon';
import { isActive } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

interface Tab {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Prefix URL lain yang juga menandai tab ini aktif. */
    juga?: string[];
}

/** Sub-menu halaman pembayaran admin. */
export function MenuPembayaran({ menungguVerifikasi }: { menungguVerifikasi?: number }) {
    const { url } = usePage();
    const pembayaran = admin.pembayaran;

    const tabs: Tab[] = [
        {
            label: 'Tagihan',
            href: pembayaran.index.url(),
            icon: ReceiptText,
            juga: [pembayaran.generate.create.url(), `${pembayaran.index.url()}/tagihan`],
        },
        { label: 'Verifikasi', href: pembayaran.verifikasi.index.url(), icon: BadgeCheck },
        { label: 'Tarif', href: pembayaran.tarif.index.url(), icon: Tags },
        { label: 'Rekening', href: pembayaran.rekening.index.url(), icon: Landmark },
    ];

    const path = url.split(/[?#]/)[0];

    return (
        <nav aria-label="Menu pembayaran" className="no-scrollbar -mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul className="flex w-max gap-1 rounded-2xl border border-line bg-surface p-1 shadow-xs">
                {tabs.map((tab) => {
                    const aktif = tab.href === pembayaran.index.url()
                        ? path === tab.href || (tab.juga ?? []).some((prefix) => isActive(path, prefix))
                        : isActive(path, tab.href);

                    return (
                        <li key={tab.label}>
                            <Link
                                href={tab.href}
                                aria-current={aktif ? 'page' : undefined}
                                className={cn(
                                    'inline-flex h-10 items-center gap-2 rounded-xl px-4 text-sm font-semibold transition-colors',
                                    aktif ? 'bg-brand-500 text-white' : 'text-fg-muted hover:bg-surface-muted hover:text-fg',
                                )}
                            >
                                <Icon icon={tab.icon} className="size-4" />
                                {tab.label}
                                {tab.label === 'Verifikasi' && !!menungguVerifikasi && (
                                    <span
                                        className={cn(
                                            'rounded-full px-1.5 text-xs tabular-nums',
                                            aktif ? 'bg-white/20 text-white' : 'bg-error-600 text-white',
                                        )}
                                    >
                                        {menungguVerifikasi}
                                        <span className="sr-only"> menunggu</span>
                                    </span>
                                )}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
