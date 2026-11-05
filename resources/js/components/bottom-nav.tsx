import { Link, usePage } from '@inertiajs/react';

import { Icon } from '@/components/ui/icon';
import { isActive, navItems } from '@/lib/navigation';
import { cn } from '@/lib/utils';

/** Navigasi bawah mengambang (ponsel & tablet). */
export function BottomNav() {
    const { auth } = usePage().props;
    const { url } = usePage();
    const items = navItems(auth?.role);

    return (
        <nav
            aria-label="Navigasi utama"
            className="fixed inset-x-3 bottom-(--bottom-nav-offset) z-30 mx-auto max-w-md rounded-2xl border border-line bg-surface/90 shadow-lg shadow-gray-950/10 backdrop-blur-xl lg:hidden dark:shadow-black/40"
        >
            <ul className="grid p-1.5" style={{ gridTemplateColumns: `repeat(${items.length}, minmax(0, 1fr))` }}>
                {items.map((item) => {
                    const active = isActive(url, item.href);

                    return (
                        <li key={item.href}>
                            <Link
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex h-13 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-semibold transition-colors duration-150',
                                    active ? 'bg-brand-500 text-white shadow-sm' : 'text-fg-muted hover:bg-surface-muted hover:text-fg',
                                )}
                            >
                                <Icon icon={item.icon} className="size-5" />
                                {item.short}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
