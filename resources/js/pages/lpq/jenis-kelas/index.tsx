import { Link, router } from '@inertiajs/react';
import { ArrowRight, Layers, Plus, Trash2 } from 'lucide-react';

import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import { JENIS, type Jenis } from '@/lib/lpq';
import lpq from '@/routes/admin/lpq';

interface JenisKelas {
    id: string;
    nama: string;
    jenis: Jenis | null;
    satuan_tarif: string | null;
    jumlah_kelas: number;
    jumlah_kelas_aktif: number;
    jumlah_santri: number;
}

export default function DaftarJenisKelas({ jenisKelas, jenisTersedia }: { jenisKelas: JenisKelas[]; jenisTersedia: string[] }) {
    const tambah = (nama: string) => router.post(lpq.jenisKelas.store.url(), { nama }, { preserveScroll: true });

    return (
        <LpqLayout title="Jenis Kelas" subtitle="Jenis layanan menentukan aturan kelas, pendaftaran santri, dan tarif. Pilih salah satu untuk mengelola kelasnya.">
            {jenisTersedia.length > 0 && (
                <Alert tone="info" className="mb-5">
                    <span className="flex flex-wrap items-center gap-2">
                        Jenis kelas berikut belum tersedia:
                        {jenisTersedia.map((nama) => (
                            <Button key={nama} variant="secondary" size="sm" icon={Plus} onClick={() => tambah(nama)}>
                                {nama}
                            </Button>
                        ))}
                    </span>
                </Alert>
            )}

            {jenisKelas.length === 0 ? (
                <EmptyState icon={Layers} title="Belum ada jenis kelas" description="Tambahkan jenis Reguler, Privat, atau Lembaga di atas." />
            ) : (
                <ul className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {jenisKelas.map((item) => {
                        const meta = item.jenis ? JENIS[item.jenis] : null;
                        const dipakai = item.jumlah_kelas > 0 || item.jumlah_santri > 0;

                        return (
                            <li key={item.id} className="relative flex flex-col rounded-2xl border border-line bg-surface p-5 shadow-xs transition-colors hover:border-brand-300 dark:hover:border-brand-400/40">
                                <div className="flex items-start justify-between gap-3">
                                    <Badge tone={meta?.tone ?? 'neutral'}>{item.nama}</Badge>
                                    {item.satuan_tarif && <span className="text-xs text-fg-muted">Tarif per {item.satuan_tarif}</span>}
                                </div>

                                <p className="mt-3 flex-1 text-sm text-fg-muted">{meta?.deskripsi ?? 'Jenis ini belum memiliki aturan kelas.'}</p>

                                <dl className="mt-5 grid grid-cols-2 gap-3 border-t border-line pt-4">
                                    <div>
                                        <dt className="text-xs text-fg-muted">Kelas aktif</dt>
                                        <dd className="text-xl font-semibold text-fg tabular-nums">
                                            {item.jumlah_kelas_aktif}
                                            <span className="text-sm font-normal text-fg-subtle"> / {item.jumlah_kelas}</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-fg-muted">Santri</dt>
                                        <dd className="text-xl font-semibold text-fg tabular-nums">{item.jumlah_santri}</dd>
                                    </div>
                                </dl>

                                <div className="mt-4 flex items-center justify-between gap-2">
                                    <Link
                                        href={lpq.kelas.index.url(item.id)}
                                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 after:absolute after:inset-0 after:rounded-2xl hover:text-brand-700 dark:text-brand-300"
                                    >
                                        Kelola kelas
                                        <Icon icon={ArrowRight} className="size-4" />
                                    </Link>

                                    {!dipakai && (
                                        <ConfirmDialog
                                            title={`Hapus jenis kelas ${item.nama}?`}
                                            url={lpq.jenisKelas.destroy.url(item.id)}
                                            trigger={(open) => (
                                                <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="relative z-10 hover:text-error-700 dark:hover:text-error-300">
                                                    <span className="sr-only">Hapus {item.nama}</span>
                                                </Button>
                                            )}
                                        >
                                            Jenis kelas yang belum dipakai akan dihapus. Tarif yang memakai jenis ini akan menolak penghapusan.
                                        </ConfirmDialog>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </LpqLayout>
    );
}
