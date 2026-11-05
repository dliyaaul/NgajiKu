import { CalendarCheck, History, LayoutDashboard, ReceiptText, Wallet, type LucideIcon } from 'lucide-react';

import admin from '@/routes/admin';
import absensi from '@/routes/absensi';
import pengajar from '@/routes/pengajar';
import santri from '@/routes/santri';
import staff from '@/routes/staff';
import type { Role } from '@/types';

export interface NavItem {
    name: string;
    /** Label pendek untuk navigasi bawah di ponsel. */
    short: string;
    href: string;
    icon: LucideIcon;
}

export function dashboardUrl(role: Role | undefined): string {
    switch (role) {
        case 'admin':
            return admin.dashboard.url();
        case 'staff':
            return staff.dashboard.url();
        case 'pengajar':
            return pengajar.dashboard.url();
        default:
            return santri.dashboard.url();
    }
}

/** Menu navigasi sesuai peran pengguna. */
export function navItems(role: Role | undefined): NavItem[] {
    const items: NavItem[] = [{ name: 'Dashboard', short: 'Beranda', href: dashboardUrl(role), icon: LayoutDashboard }];

    if (role === 'admin' || role === 'pengajar') {
        items.push({ name: 'Absensi', short: 'Absensi', href: absensi.index.url(), icon: CalendarCheck });
    }

    if (role === 'admin') {
        items.push({ name: 'Pembayaran', short: 'Bayar', href: admin.pembayaran.index.url(), icon: Wallet });
    }

    if (role === 'santri') {
        items.push({ name: 'Riwayat Absensi', short: 'Riwayat', href: santri.riwayatAbsensi.url(), icon: History });
        items.push({ name: 'Tagihan', short: 'Tagihan', href: santri.tagihan.index.url(), icon: ReceiptText });
    }

    return items;
}

/** Menu aktif juga untuk sub-halamannya (mis. /absensi/tahfidz/...). */
export function isActive(currentUrl: string, href: string): boolean {
    const path = currentUrl.split(/[?#]/)[0];

    return path === href || path.startsWith(`${href}/`);
}
