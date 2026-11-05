import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { AppHeader } from '@/components/app-header';
import { AppSidebar } from '@/components/app-sidebar';
import { BottomNav } from '@/components/bottom-nav';
import { Ornament } from '@/components/ui/ornament';
import type { BreadcrumbItem } from '@/types';

/**
 * Kerangka halaman setelah login: sidebar desktop (bisa diciutkan), header dengan
 * breadcrumb & tanggal, dan navigasi bawah mengambang di ponsel.
 */
export default function AppLayout({
    title,
    breadcrumbs = [],
    children,
}: {
    title: string;
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    return (
        <>
            <Head title={`${title} - NgajiKu`} />

            <a
                href="#konten-utama"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-60 focus:rounded-lg focus:bg-brand-500 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white"
            >
                Lewati ke konten utama
            </a>

            <AppSidebar />

            <div className="flex min-h-dvh flex-col transition-[padding] duration-200 lg:pl-68 sidebar-collapsed:lg:pl-20">
                <AppHeader title={title} breadcrumbs={breadcrumbs} />

                <main
                    id="konten-utama"
                    tabIndex={-1}
                    className="mx-auto w-full max-w-7xl flex-1 px-4 pt-6 pb-(--bottom-nav-space) outline-none sm:px-6 lg:px-10 lg:pt-8 lg:pb-10"
                >
                    {children}
                </main>

                <footer className="mx-auto hidden w-full max-w-7xl items-center justify-between gap-4 border-t border-line px-10 py-5 text-xs text-fg-subtle lg:flex">
                    <p>© {new Date().getFullYear()} NgajiKu · Sistem Informasi Lembaga Pendidikan Al-Qur’an</p>
                    <Ornament type="divider" className="text-gold-500/70" />
                </footer>
            </div>

            <BottomNav />
        </>
    );
}
