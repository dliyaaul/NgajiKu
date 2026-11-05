import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import type { ReactNode } from 'react';

import { PageHeader } from '@/components/page-header';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { isActive } from '@/lib/navigation';
import { menuLpq } from '@/lib/lpq';
import { cn } from '@/lib/utils';
import lpq from '@/routes/admin/lpq';
import type { BreadcrumbItem } from '@/types';

/**
 * Kerangka halaman manajemen LPQ: judul, sub-menu (kolom kiri di desktop,
 * baris gulir di ponsel), lalu konten.
 */
export function LpqLayout({
    title,
    subtitle,
    breadcrumbs,
    actions,
    children,
}: {
    title: string;
    subtitle?: string;
    /** Breadcrumb setelah "Manajemen LPQ"; bawaan = judul halaman. Array kosong = halaman ringkasan. */
    breadcrumbs?: BreadcrumbItem[];
    actions?: ReactNode;
    children: ReactNode;
}) {
    const jejak: BreadcrumbItem[] =
        breadcrumbs && breadcrumbs.length === 0
            ? [{ label: 'Manajemen LPQ' }]
            : [{ label: 'Manajemen LPQ', href: lpq.index.url() }, ...(breadcrumbs ?? [{ label: title }])];

    return (
        <AppLayout title={title} breadcrumbs={jejak}>
            <div className="grid grid-cols-1 gap-6 *:min-w-0 lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-8">
                <MenuLpq />

                <div>
                    <PageHeader title={title} subtitle={subtitle} breadcrumbs={jejak} actions={actions} />
                    {children}
                </div>
            </div>
        </AppLayout>
    );
}

function MenuLpq() {
    const { url } = usePage();
    const path = url.split(/[?#]/)[0];
    const grup = menuLpq();
    const semua = grup.flatMap((item) => item.items);
    const ringkasanAktif = path === lpq.index.url();

    const linkClass = (aktif: boolean) =>
        cn(
            'flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors',
            aktif ? 'bg-brand-50 text-brand-600 dark:bg-brand-400/10 dark:text-brand-300' : 'text-fg-muted hover:bg-surface-muted hover:text-fg',
        );

    return (
        <>
            {/* Ponsel & tablet: baris gulir */}
            <nav aria-label="Menu manajemen LPQ" className="no-scrollbar -mx-4 overflow-x-auto px-4 sm:-mx-6 sm:px-6 lg:hidden">
                <ul className="flex w-max gap-2">
                    {[{ label: 'Ringkasan', href: lpq.index.url(), icon: LayoutGrid }, ...semua].map((item) => {
                        const aktif = item.href === lpq.index.url() ? ringkasanAktif : isActive(path, item.href);

                        return (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    aria-current={aktif ? 'page' : undefined}
                                    className={cn(
                                        'inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-semibold whitespace-nowrap transition-colors',
                                        aktif ? 'border-brand-500 bg-brand-500 text-white' : 'border-line bg-surface text-fg-muted hover:text-fg',
                                    )}
                                >
                                    <Icon icon={item.icon} className="size-4" />
                                    {item.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* Desktop: kolom kiri */}
            <nav aria-label="Menu manajemen LPQ" className="hidden lg:block">
                <div className="sticky top-24 flex flex-col gap-5">
                    <Link href={lpq.index.url()} aria-current={ringkasanAktif ? 'page' : undefined} className={linkClass(ringkasanAktif)}>
                        <Icon icon={LayoutGrid} className="size-[1.125rem]" />
                        Ringkasan
                    </Link>

                    {grup.map((bagian) => (
                        <div key={bagian.judul}>
                            <p className="mb-1.5 px-3 text-[0.6875rem] font-semibold tracking-[0.12em] text-fg-subtle uppercase">{bagian.judul}</p>
                            <ul className="flex flex-col gap-0.5">
                                {bagian.items.map((item) => {
                                    const aktif = isActive(path, item.href);

                                    return (
                                        <li key={item.href}>
                                            <Link href={item.href} aria-current={aktif ? 'page' : undefined} className={linkClass(aktif)}>
                                                <Icon icon={item.icon} className="size-[1.125rem]" />
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </div>
            </nav>
        </>
    );
}
