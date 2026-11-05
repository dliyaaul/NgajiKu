import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';

import { Flash } from '@/components/flash';
import { Icon } from '@/components/ui/icon';
import type { BreadcrumbItem } from '@/types';

/**
 * Judul halaman + aksi utama. Di desktop breadcrumb tampil di header atas (AppLayout);
 * di ponsel/tablet diringkas menjadi tautan "kembali" ke induk terdekat.
 */
export function PageHeader({
    title,
    subtitle,
    breadcrumbs = [],
    actions,
}: {
    title: string;
    subtitle?: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: ReactNode;
}) {
    const parent = breadcrumbs.filter((item) => item.href).at(-1);

    return (
        <>
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between lg:mb-8">
                <div className="min-w-0">
                    {parent?.href && (
                        <Link
                            href={parent.href}
                            className="mb-3 inline-flex items-center gap-1 rounded-md text-sm font-medium text-fg-muted hover:text-fg lg:hidden"
                        >
                            <Icon icon={ChevronLeft} className="size-4" />
                            {parent.label}
                        </Link>
                    )}

                    <h1 className="text-2xl font-bold tracking-tight text-balance text-fg sm:text-3xl">{title}</h1>

                    {subtitle && <p className="mt-1.5 max-w-3xl text-sm text-fg-muted sm:text-base">{subtitle}</p>}
                </div>

                {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
            </div>

            <Flash />
        </>
    );
}
