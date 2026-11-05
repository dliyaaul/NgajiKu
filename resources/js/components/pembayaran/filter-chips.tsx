import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';

export interface Chip {
    label: string;
    href: string;
    aktif: boolean;
    jumlah?: number;
}

/** Baris tombol filter yang bisa digulir di ponsel. */
export function FilterChips({ label, chips }: { label: string; chips: Chip[] }) {
    return (
        <nav aria-label={label} className="no-scrollbar -mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul className="flex w-max gap-2">
                {chips.map((chip) => (
                    <li key={chip.label}>
                        <Link
                            href={chip.href}
                            preserveScroll
                            aria-current={chip.aktif ? 'page' : undefined}
                            className={cn(
                                'inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition-colors',
                                chip.aktif
                                    ? 'border-brand-500 bg-brand-500 text-white'
                                    : 'border-line bg-surface text-fg-muted hover:bg-surface-muted hover:text-fg',
                            )}
                        >
                            {chip.label}
                            {chip.jumlah !== undefined && (
                                <span className={cn('text-xs tabular-nums', chip.aktif ? 'text-white/80' : 'text-fg-subtle')}>{chip.jumlah}</span>
                            )}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
