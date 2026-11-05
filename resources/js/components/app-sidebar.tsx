import { Link, usePage } from '@inertiajs/react';
import { PanelLeft } from 'lucide-react';

import { Icon } from '@/components/ui/icon';
import { Ornament } from '@/components/ui/ornament';
import { UserAvatar } from '@/components/user-avatar';
import { useSidebar } from '@/hooks/use-preferences';
import { dashboardUrl, isActive, navItems } from '@/lib/navigation';
import { cn } from '@/lib/utils';

/** Sidebar desktop: panel emerald seperti halaman login, tetap gelap di kedua tema. */
export function AppSidebar() {
    const { auth } = usePage().props;
    const { url } = usePage();
    const { collapsed, toggle } = useSidebar();

    return (
        <aside className="on-brand fixed inset-y-0 left-0 z-30 hidden w-68 flex-col overflow-hidden bg-brand-900 text-white transition-[width] duration-200 lg:flex sidebar-collapsed:w-20">
            {/* Latar: gradasi, pola bintang yang memudar, dan cahaya lembut */}
            <div className="pointer-events-none absolute inset-0 bg-linear-to-b from-brand-700/70 via-brand-900 to-brand-950" aria-hidden="true" />
            <Ornament
                type="pattern"
                className="absolute inset-0 size-full text-white opacity-[0.07] mask-[linear-gradient(to_bottom,black,transparent_55%)]"
            />
            <div className="pointer-events-none absolute -top-24 -left-16 size-64 rounded-full bg-brand-400/20 blur-3xl" aria-hidden="true" />

            {/* Identitas */}
            <div className="relative flex h-20 shrink-0 items-center px-5 sidebar-collapsed:justify-center sidebar-collapsed:px-0">
                <Link href={dashboardUrl(auth?.role)} className="flex min-w-0 items-center gap-3 rounded-xl">
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-white p-1.5 shadow-lg shadow-brand-950/30">
                        <img src="/assets/images/logo/Ngajiku_Logo.png" alt="" width={32} height={32} className="size-full object-contain" />
                    </span>

                    <span className="min-w-0 sidebar-collapsed:sr-only">
                        <span className="block text-lg leading-tight font-bold tracking-tight">NgajiKu</span>
                        <span className="block text-xs whitespace-nowrap text-brand-100/75">Lembaga Pendidikan Al-Qur’an</span>
                    </span>
                </Link>
            </div>

            <Ornament
                type="divider"
                className="relative mx-5 justify-center text-gold-300/80 sidebar-collapsed:mx-auto sidebar-collapsed:[&>span:first-child]:hidden sidebar-collapsed:[&>span:last-child]:hidden"
            />

            {/* Menu */}
            <nav className="no-scrollbar relative flex-1 overflow-y-auto px-3 py-6" aria-label="Navigasi utama">
                <p className="mb-2 px-3 text-[0.6875rem] font-semibold tracking-[0.12em] text-brand-100/60 uppercase sidebar-collapsed:sr-only">
                    Menu Utama
                </p>

                <ul className="flex flex-col gap-1">
                    {navItems(auth?.role).map((item) => {
                        const active = isActive(url, item.href);

                        return (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    title={item.name}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'group relative flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors duration-150 sidebar-collapsed:justify-center sidebar-collapsed:px-0',
                                        active
                                            ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10 ring-inset'
                                            : 'text-brand-50/80 hover:bg-white/[0.06] hover:text-white',
                                    )}
                                >
                                    {active && <span className="absolute inset-y-2.5 left-0 w-1 rounded-r-full bg-gold-300" aria-hidden="true" />}

                                    <Icon icon={item.icon} className={cn('size-5', active && 'text-gold-200')} />
                                    <span className="truncate sidebar-collapsed:sr-only">{item.name}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* Profil & ciutkan */}
            <div className="relative border-t border-white/10 p-3">
                <div className="flex items-center gap-3 rounded-xl px-2 py-2 sidebar-collapsed:flex-col sidebar-collapsed:px-0">
                    {auth && <UserAvatar user={auth} className="size-9 bg-gold-300 text-brand-900 ring-2 ring-white/20" />}

                    <span className="min-w-0 flex-1 sidebar-collapsed:sr-only">
                        <span className="block truncate text-sm font-semibold">{auth?.username}</span>
                        <span className="block text-xs text-brand-100/75 capitalize">{auth?.role}</span>
                    </span>

                    <button
                        type="button"
                        onClick={toggle}
                        aria-label={collapsed ? 'Lebarkan menu' : 'Ciutkan menu'}
                        aria-pressed={collapsed}
                        title="Ciutkan / lebarkan menu"
                        className="grid size-9 shrink-0 place-items-center rounded-lg text-brand-100/80 transition-colors hover:bg-white/10 hover:text-white"
                    >
                        <Icon icon={PanelLeft} className="size-[1.125rem]" />
                    </button>
                </div>
            </div>
        </aside>
    );
}
