
import { BadgeCheck, CircleAlert, Clock3, ReceiptText } from 'lucide-react';

import { StatCard } from '@/components/ui/stat-card';
import { formatRupiah } from '@/lib/pembayaran';

interface RingkasanNominal {
    jumlah: number;
    nominal: number;
}

interface Props {
    totalTagihan: RingkasanNominal;
    lunas: RingkasanNominal;
    belumDibayar: RingkasanNominal;
    menungguVerifikasi: RingkasanNominal;
}

export function StatistikKeuangan({
    totalTagihan,
    lunas,
    belumDibayar,
    menungguVerifikasi,
}: Props) {
    return (
        <section className="mb-8">
            <div className="mb-4">
                <h2 className="text-lg font-bold text-fg">Statistik Keuangan</h2>
                <p className="mt-1 text-sm text-fg-muted">
                    Ringkasan tagihan dan pembayaran seluruh lembaga.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
                <StatCard
                    label="Total Tagihan"
                    value={formatRupiah(totalTagihan.nominal)}
                    icon={ReceiptText}
                    tone="brand"
                    hint={`${totalTagihan.jumlah} tagihan`}
                />
                <StatCard
                    label="Sudah Lunas"
                    value={formatRupiah(lunas.nominal)}
                    icon={BadgeCheck}
                    tone="success"
                    hint={`${lunas.jumlah} tagihan lunas`}
                />
                <StatCard
                    label="Belum Dibayar"
                    value={formatRupiah(belumDibayar.nominal)}
                    icon={CircleAlert}
                    tone="warning"
                    hint={`${belumDibayar.jumlah} tagihan`}
                />
                <StatCard
                    label="Menunggu Verifikasi"
                    value={formatRupiah(menungguVerifikasi.nominal)}
                    icon={Clock3}
                    tone="info"
                    hint={`${menungguVerifikasi.jumlah} pembayaran`}
                />
            </div>
        </section>
    );
}
