import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, BookHeart, CircleAlert, CircleCheck, History, Info, TrendingUp } from 'lucide-react';

import { PengingatPembayaran } from '@/components/pembayaran/pengingat-pembayaran';
import { Alert } from '@/components/ui/alert';
import { Badge, ProgramIcon, StatusBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import { StatCard } from '@/components/ui/stat-card';
import { WelcomeBanner } from '@/components/welcome-banner';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/pembayaran';
import { PROGRAM } from '@/lib/program';
import { formatTanggal } from '@/lib/utils';
import santri from '@/routes/santri';
import type { RiwayatItem, StatusKehadiran } from '@/types';

interface Props {
    ringkasan: Record<StatusKehadiran, number>;
    persenHadir: number | null;
    setoranTerakhir: RiwayatItem | null;
    terbaru: RiwayatItem[];
    gagal: string[];
    tagihanBelumDibayar: { jumlah: number; total: number };
}

export default function DashboardSantri({ ringkasan, persenHadir, setoranTerakhir, terbaru, gagal, tagihanBelumDibayar }: Props) {
    const { auth } = usePage().props;

    return (
        <AppLayout title="Dashboard Santri">
            <WelcomeBanner
                title={`Assalamu’alaikum, ${auth?.username ?? 'Santri'}`}
                subtitle="Pantau kehadiran dan setoran hafalan Anda. Semoga Allah mudahkan dalam menjaga Al-Qur’an."
                actions={
                    <Button variant="inverse" href={santri.riwayatAbsensi.url()} icon={History}>
                        Lihat Riwayat
                    </Button>
                }
            />

            {gagal.length > 0 && (
                <Alert tone="warning" className="mb-6">
                    Data {gagal.join(', ')} belum dapat dimuat, sehingga ringkasan belum lengkap. Silakan coba lagi nanti.
                </Alert>
            )}

            {tagihanBelumDibayar.jumlah > 0 && (
                <PengingatPembayaran
                    jenis="tagihan"
                    judul={`${tagihanBelumDibayar.jumlah} tagihan belum dibayar`}
                    keterangan={`Total ${formatRupiah(tagihanBelumDibayar.total)}. Kirim bukti transfer setelah membayar.`}
                    href={santri.tagihan.index.url()}
                    aksi="Lihat Tagihan"
                />
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <StatCard
                    label="Kehadiran"
                    value={persenHadir === null ? '—' : `${persenHadir}%`}
                    icon={TrendingUp}
                    hint={persenHadir === null ? 'Belum ada absensi' : 'Dari seluruh pertemuan'}
                />
                <StatCard label="Hadir" value={ringkasan.Hadir} icon={CircleCheck} tone="success" hint="Pertemuan" />
                <StatCard
                    label="Izin & Sakit"
                    value={ringkasan.Izin + ringkasan.Sakit}
                    icon={Info}
                    tone="info"
                    hint={`${ringkasan.Izin} izin · ${ringkasan.Sakit} sakit`}
                />
                <StatCard label="Alpha" value={ringkasan.Alpha} icon={CircleAlert} tone={ringkasan.Alpha > 0 ? 'danger' : 'neutral'} hint="Tanpa keterangan" />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Setoran hafalan terakhir */}
                <section className="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 shadow-xs sm:p-6 lg:order-last">
                    <div className="pointer-events-none absolute inset-x-0 top-0 h-1 bg-linear-to-r from-brand-500 via-gold-500 to-brand-500" aria-hidden="true" />

                    <h2 className="flex items-center gap-2 text-base font-semibold text-fg">
                        <Icon icon={BookHeart} className="size-5 text-amber-600 dark:text-amber-300" />
                        Setoran hafalan terakhir
                    </h2>

                    {setoranTerakhir ? (
                        <>
                            <p className="mt-4 text-2xl font-bold tracking-tight text-fg">{setoranTerakhir.surat}</p>

                            {setoranTerakhir.ayat_mulai && (
                                <p className="mt-0.5 text-sm text-fg-muted tabular-nums">
                                    Ayat {setoranTerakhir.ayat_mulai}–{setoranTerakhir.ayat_selesai}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                {setoranTerakhir.jenis_tahfidz && <Badge tone="amber">{setoranTerakhir.jenis_tahfidz}</Badge>}
                                <span className="text-sm text-fg-muted">{formatTanggal(setoranTerakhir.tanggal)}</span>
                            </div>

                            {setoranTerakhir.keterangan && (
                                <p className="mt-4 rounded-xl bg-surface-muted px-4 py-3 text-sm text-fg-muted">“{setoranTerakhir.keterangan}”</p>
                            )}
                        </>
                    ) : (
                        <p className="mt-3 text-sm text-fg-muted">
                            Belum ada setoran hafalan yang tercatat. Setoran akan muncul setelah pengajar Tahfidz mengisinya.
                        </p>
                    )}
                </section>

                {/* Kehadiran terbaru */}
                <Card
                    title="Kehadiran terbaru"
                    padded={false}
                    className="lg:col-span-2"
                    actions={
                        <Link
                            href={santri.riwayatAbsensi.url()}
                            className="inline-flex items-center gap-1 rounded-md text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300"
                        >
                            Semua riwayat
                            <Icon icon={ArrowRight} className="size-4" />
                        </Link>
                    }
                >
                    {terbaru.length === 0 ? (
                        <div className="px-6 py-10 text-center">
                            <p className="font-semibold text-fg">Belum ada riwayat absensi</p>
                            <p className="mt-1 text-sm text-fg-muted">Riwayat akan muncul setelah pengajar mengisi absensi.</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-line">
                            {terbaru.map((item, index) => (
                                <li key={`${item.program}-${item.tanggal}-${index}`} className="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <ProgramIcon program={item.program} size="sm" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-fg">{PROGRAM[item.program].label}</p>
                                            <p className="truncate text-sm text-fg-muted">
                                                {formatTanggal(item.tanggal)}
                                                {item.pengajar && ` · ${item.pengajar}`}
                                            </p>
                                        </div>
                                    </div>
                                    <StatusBadge status={item.status} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
