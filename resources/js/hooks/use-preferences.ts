import { useState } from 'react';

import { writePreference } from '@/lib/utils';

/**
 * Tema terang/gelap. Nilai awal dibaca dari <html class="dark"> yang sudah diset
 * skrip inline di app.blade.php sebelum halaman digambar.
 */
export function useTheme() {
    const [theme, setTheme] = useState<'light' | 'dark'>(() =>
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    );

    const toggle = () => {
        const next = theme === 'dark' ? 'light' : 'dark';
        const html = document.documentElement;

        html.classList.toggle('dark', next === 'dark');
        html.style.colorScheme = next;
        writePreference('theme', next);
        setTheme(next);
    };

    return { theme, toggle };
}

/**
 * Sidebar desktop diciutkan/dilebarkan. Status ditulis ke <html data-sidebar>
 * sehingga varian CSS `sidebar-collapsed:` langsung benar sejak halaman dimuat.
 */
export function useSidebar() {
    const [collapsed, setCollapsed] = useState(
        () => typeof document !== 'undefined' && document.documentElement.dataset.sidebar === 'collapsed',
    );

    const toggle = () => {
        const next = !collapsed;
        const state = next ? 'collapsed' : 'expanded';

        document.documentElement.dataset.sidebar = state;
        writePreference('sidebar', state);
        setCollapsed(next);
    };

    return { collapsed, toggle };
}
