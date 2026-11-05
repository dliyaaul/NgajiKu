import { router, useForm } from '@inertiajs/react';
import { Check, Pencil, Phone, Plus, Power, Repeat2, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';

import { Inisial } from '@/components/lpq/inisial';
import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Toolbar } from '@/components/lpq/toolbar';
import { FilterChips } from '@/components/pembayaran/filter-chips';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import { Modal } from '@/components/ui/modal';
import { queryBersih } from '@/lib/lpq';
import { cn } from '@/lib/utils';
import lpq from '@/routes/admin/lpq';

type Status = 'aktif' | 'nonaktif' | 'semua';

interface Pengganti {
    id: string;
    nama: string;
    no_hp: string | null;
    aktif: boolean;
    jumlah_pertemuan: number;
}

interface Props {
    pengganti: Pengganti[];
    jumlahPerStatus: Record<Status, number>;
    filter: { status: Status; cari: string };
}

const LABEL_STATUS: Record<Status, string> = { aktif: 'Aktif', nonaktif: 'Nonaktif', semua: 'Semua' };

export default function DaftarPengajarPengganti({ pengganti, jumlahPerStatus, filter }: Props) {
    const [form, setForm] = useState<{ open: boolean; data: Pengganti | null }>({ open: false, data: null });
    const daftarUrl = (query: Record<string, string>) => lpq.pengajarPengganti.index.url({ query: queryBersih(query) });

    return (
        <LpqLayout
            title="Pengajar Pengganti"
            subtitle="Pengajar dari luar yang dapat dipilih saat mengisi pertemuan. Tidak memerlukan akun."
            actions={
                <Button icon={Plus} onClick={() => setForm({ open: true, data: null })}>
                    Tambah Pengganti
                </Button>
            }
        >
            <FilterChips
                label="Filter status"
                chips={(['aktif', 'nonaktif', 'semua'] as Status[]).map((status) => ({
                    label: LABEL_STATUS[status],
                    href: daftarUrl({ status, cari: filter.cari }),
                    aktif: filter.status === status,
                    jumlah: jumlahPerStatus[status],
                }))}
            />

            <Toolbar url={(query) => daftarUrl({ ...query, status: filter.status })} filter={{ cari: filter.cari }} placeholder="Cari nama atau nomor HP…" />

            {pengganti.length === 0 ? (
                <EmptyState
                    icon={Repeat2}
                    title={filter.cari ? 'Tidak ada yang cocok' : 'Belum ada pengajar pengganti'}
                    description={filter.cari ? 'Coba kata kunci lain.' : 'Tambahkan pengajar pengganti untuk dipilih saat mengisi pertemuan.'}
                />
            ) : (
                <ul className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    {pengganti.map((item) => (
                        <li key={item.id} className={cn('flex items-center gap-4 rounded-2xl border border-line bg-surface p-4 shadow-xs', !item.aktif && 'opacity-75')}>
                            <Inisial nama={item.nama} />

                            <div className="min-w-0 flex-1">
                                <p className="flex flex-wrap items-center gap-2 font-medium text-fg">
                                    <span className="truncate">{item.nama}</span>
                                    {!item.aktif && <Badge>Nonaktif</Badge>}
                                </p>
                                <p className="mt-0.5 flex flex-wrap items-center gap-x-3 text-sm text-fg-muted">
                                    <span className="inline-flex items-center gap-1 tabular-nums">
                                        <Icon icon={Phone} className="size-3.5" />
                                        {item.no_hp ?? '-'}
                                    </span>
                                    <span className="tabular-nums">{item.jumlah_pertemuan} pertemuan</span>
                                </p>
                            </div>

                            <div className="flex shrink-0 gap-0.5">
                                <Button variant="ghost" size="sm" icon={Pencil} onClick={() => setForm({ open: true, data: item })}>
                                    <span className="sr-only">Edit {item.nama}</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    icon={Power}
                                    title={item.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                                    onClick={() => router.patch(lpq.pengajarPengganti.aktif.url(item.id), { aktif: !item.aktif }, { preserveScroll: true })}
                                >
                                    <span className="sr-only">
                                        {item.aktif ? 'Nonaktifkan' : 'Aktifkan'} {item.nama}
                                    </span>
                                </Button>
                                {item.jumlah_pertemuan === 0 && (
                                    <ConfirmDialog
                                        title={`Hapus ${item.nama}?`}
                                        url={lpq.pengajarPengganti.destroy.url(item.id)}
                                        trigger={(open) => (
                                            <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="hover:text-error-700 dark:hover:text-error-300">
                                                <span className="sr-only">Hapus {item.nama}</span>
                                            </Button>
                                        )}
                                    >
                                        Data pengajar pengganti ini akan dihapus permanen.
                                    </ConfirmDialog>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <FormPengganti open={form.open} data={form.data} onClose={() => setForm((sebelum) => ({ ...sebelum, open: false }))} />
        </LpqLayout>
    );
}

function FormPengganti({ open, data: pengganti, onClose }: { open: boolean; data: Pengganti | null; onClose: () => void }) {
    const form = useForm({ nama: '', no_hp: '', aktif: true });
    const { data, setData, errors } = form;
    // Isi formulir setiap kali dibuka.
    useEffect(() => {
        if (open) {
            form.setData({ nama: pengganti?.nama ?? '', no_hp: pengganti?.no_hp ?? '', aktif: pengganti?.aktif ?? true });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, pengganti?.id]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => onClose() };

        if (pengganti) {
            form.put(lpq.pengajarPengganti.update.url(pengganti.id), opsi);
        } else {
            form.post(lpq.pengajarPengganti.store.url(), opsi);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={pengganti ? 'Edit Pengajar Pengganti' : 'Tambah Pengajar Pengganti'}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Batal
                    </Button>
                    <Button type="submit" form="form-pengganti" icon={pengganti ? Check : Plus} disabled={form.processing}>
                        {pengganti ? 'Simpan' : 'Tambah'}
                    </Button>
                </>
            }
        >
            <form id="form-pengganti" onSubmit={submit} noValidate className="flex flex-col gap-5">
                <Field label="Nama" htmlFor="nama" error={errors.nama} required>
                    <input id="nama" value={data.nama} maxLength={255} onChange={(event) => setData('nama', event.target.value)} className="form-control" {...fieldAria('nama', errors.nama)} />
                </Field>

                <Field label="Nomor HP" htmlFor="no_hp" error={errors.no_hp} optional>
                    <input
                        id="no_hp"
                        type="tel"
                        inputMode="tel"
                        value={data.no_hp}
                        onChange={(event) => setData('no_hp', event.target.value)}
                        placeholder="08xxxxxxxxxx"
                        className="form-control tabular-nums"
                        {...fieldAria('no_hp', errors.no_hp)}
                    />
                </Field>

                <label className="flex items-center gap-2 text-sm font-medium text-fg">
                    <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 accent-brand-500" />
                    Aktif (dapat dipilih saat mengisi pertemuan)
                </label>
            </form>
        </Modal>
    );
}
