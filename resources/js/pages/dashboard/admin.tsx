import { usePage } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';

import { RingkasanAbsensi, type RingkasanAbsensiProps } from '@/components/dashboard/ringkasan-absensi';
import { PengingatPembayaran } from '@/components/pembayaran/pengingat-pembayaran';
import { Button } from '@/components/ui/button';
import { WelcomeBanner } from '@/components/welcome-banner';
import AppLayout from '@/layouts/app-layout';
import absensi from '@/routes/absensi';
import admin from '@/routes/admin';

export default function DashboardAdmin({ pembayaranMenunggu, ...props }: RingkasanAbsensiProps & { pembayaranMenunggu: number }) {
    const { auth } = usePage().props;

    return (
        <AppLayout title="Dashboard Admin">
            <WelcomeBanner
                title={`Assalamu’alaikum, ${auth?.username ?? 'Administrator'}`}
                subtitle="Ringkasan kegiatan belajar di seluruh kelas lembaga."
                actions={
                    <Button variant="inverse" href={absensi.index.url()} icon={CalendarCheck}>
                        Kelola Absensi
                    </Button>
                }
            />

            {pembayaranMenunggu > 0 && (
                <PengingatPembayaran
                    jenis="verifikasi"
                    judul={`${pembayaranMenunggu} pembayaran menunggu verifikasi`}
                    keterangan="Periksa bukti transfer agar status tagihan santri segera diperbarui."
                    href={admin.pembayaran.verifikasi.index.url()}
                    aksi="Verifikasi"
                />
            )}

            <RingkasanAbsensi {...props} cakupan="seluruh lembaga" />
        </AppLayout>
    );
}
