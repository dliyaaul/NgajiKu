import { usePage } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';

import { RingkasanAbsensi, type RingkasanAbsensiProps } from '@/components/dashboard/ringkasan-absensi';
import { Button } from '@/components/ui/button';
import { WelcomeBanner } from '@/components/welcome-banner';
import AppLayout from '@/layouts/app-layout';
import absensi from '@/routes/absensi';

export default function DashboardPengajar(props: RingkasanAbsensiProps) {
    const { auth } = usePage().props;

    return (
        <AppLayout title="Dashboard Pengajar">
            <WelcomeBanner
                title={`Assalamu’alaikum, ${auth?.username ?? 'Ustadz/Ustadzah'}`}
                subtitle="Pantau kelas yang Anda ampu dan selesaikan absensi sebelum terkunci."
                actions={
                    <Button variant="inverse" href={absensi.index.url()} icon={CalendarCheck}>
                        Kelola Absensi
                    </Button>
                }
            />

            <RingkasanAbsensi {...props} cakupan="kelas Anda" />
        </AppLayout>
    );
}
