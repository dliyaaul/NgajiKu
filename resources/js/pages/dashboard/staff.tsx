import { usePage } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';

import { EmptyState } from '@/components/ui/empty-state';
import { WelcomeBanner } from '@/components/welcome-banner';
import AppLayout from '@/layouts/app-layout';

export default function DashboardStaff() {
    const { auth } = usePage().props;

    return (
        <AppLayout title="Dashboard Staff">
            <WelcomeBanner title={`Assalamu’alaikum, ${auth?.username ?? 'Staff'}`} subtitle="Selamat datang di dashboard staff NgajiKu." />

            <EmptyState
                icon={Sparkles}
                title="Fitur staff sedang disiapkan"
                description="Menu untuk staff akan muncul di sini setelah tersedia. Hubungi admin lembaga bila Anda membutuhkan akses lain."
            />
        </AppLayout>
    );
}
