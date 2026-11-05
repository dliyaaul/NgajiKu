import { Head, router } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, Lock, RotateCw, SearchX, TriangleAlert } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';

const PESAN = {
    403: {
        icon: Lock,
        title: 'Akses ditolak',
        description: 'Anda tidak memiliki akses ke halaman ini.',
    },
    404: {
        icon: SearchX,
        title: 'Halaman tidak ditemukan',
        description: 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.',
    },
    500: {
        icon: CircleAlert,
        title: 'Terjadi kesalahan',
        description: 'Maaf, terjadi kesalahan pada server. Silakan coba beberapa saat lagi.',
    },
    503: {
        icon: TriangleAlert,
        title: 'Layanan sementara tidak tersedia',
        description: 'Kami belum dapat memverifikasi profil Anda melalui server. Silakan coba beberapa saat lagi.',
    },
};

/** Halaman galat mandiri (tanpa sidebar), juga dipakai saat sesi belum siap. */
export default function ErrorPage({ status, message }: { status: keyof typeof PESAN; message: string | null }) {
    const pesan = PESAN[status] ?? PESAN[500];
    const bisaDicobaLagi = status === 500 || status === 503;

    return (
        <>
            <Head title={`${pesan.title} - NgajiKu`} />

            <div className="grid min-h-dvh place-items-center p-4">
                <main className="w-full max-w-md text-center">
                    <span className="mx-auto mb-6 grid size-14 place-items-center rounded-2xl bg-white p-2.5 shadow-sm ring-1 ring-line">
                        <img src="/assets/images/logo/Ngajiku_Logo.png" alt="NgajiKu" width={36} height={36} className="size-full object-contain" />
                    </span>

                    <EmptyState
                        tone="danger"
                        icon={pesan.icon}
                        headingLevel="h1"
                        title={pesan.title}
                        description={message || pesan.description}
                        className="border-solid shadow-xs"
                    >
                        <Button variant="secondary" icon={ArrowLeft} onClick={() => window.history.back()}>
                            Kembali
                        </Button>
                        {bisaDicobaLagi && (
                            <Button icon={RotateCw} onClick={() => router.reload()}>
                                Coba Lagi
                            </Button>
                        )}
                    </EmptyState>

                    <p className="mt-4 text-xs text-fg-subtle tabular-nums">Kode galat {status}</p>
                </main>
            </div>
        </>
    );
}
