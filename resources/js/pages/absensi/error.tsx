import { router } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, RotateCw } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import AppLayout from '@/layouts/app-layout';

/** Data absensi tidak dapat ditampilkan karena aturan absensi (pesan sudah aman ditampilkan). */
export default function AbsensiError({ message }: { message: string }) {
    return (
        <AppLayout title="Absensi">
            <EmptyState
                tone="danger"
                icon={CircleAlert}
                headingLevel="h1"
                title="Data absensi tidak dapat dimuat"
                description={message}
                className="mx-auto mt-6 max-w-xl"
            >
                <Button variant="secondary" icon={ArrowLeft} onClick={() => window.history.back()}>
                    Kembali
                </Button>
                <Button icon={RotateCw} onClick={() => router.reload()}>
                    Coba Lagi
                </Button>
            </EmptyState>
        </AppLayout>
    );
}
