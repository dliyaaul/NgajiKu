import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Kartu permukaan. Gunakan `title` (+ `actions`) untuk kepala kartu,
 * atau `padded={false}` untuk konten rapat seperti tabel/daftar.
 */
export function Card({
    title,
    description,
    actions,
    padded = true,
    className,
    children,
}: {
    title?: string;
    description?: string;
    actions?: ReactNode;
    padded?: boolean;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section className={cn('rounded-2xl border border-line bg-surface shadow-xs', className)}>
            {title && (
                <header className="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-fg">{title}</h2>
                        {description && <p className="mt-0.5 text-sm text-fg-muted">{description}</p>}
                    </div>

                    {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
                </header>
            )}

            <div className={cn(padded && 'p-5 sm:p-6')}>{children}</div>
        </section>
    );
}
