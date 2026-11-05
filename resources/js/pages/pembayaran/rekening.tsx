import { router, useForm } from '@inertiajs/react';
import { Check, Landmark, Pencil, Plus, Power } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { BreadcrumbItem, Rekening } from '@/types';

interface Props {
    rekening: Rekening[];
}

const KOSONG = { nama_bank: '', nomor_rekening: '', nama_pemilik: '', keterangan: '', urutan: 0, aktif: true };

export default function RekeningPembayaran({ rekening }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Pembayaran', href: admin.pembayaran.index.url() }, { label: 'Rekening' }];
    const [editId, setEditId] = useState<string | null>(null);
    const form = useForm(KOSONG);
    const { data, setData, errors } = form;

    const mulaiEdit = (item: Rekening) => {
        setEditId(item.id);
        form.clearErrors();
        form.setData({
            nama_bank: item.nama_bank,
            nomor_rekening: item.nomor_rekening,
            nama_pemilik: item.nama_pemilik,
            keterangan: item.keterangan ?? '',
            urutan: item.urutan,
            aktif: item.aktif,
        });
        document.getElementById('nama_bank')?.focus();
    };

    const batal = () => {
        setEditId(null);
        form.reset();
        form.clearErrors();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: batal };

        if (editId) {
            form.put(admin.pembayaran.rekening.update.url(editId), options);
        } else {
            form.post(admin.pembayaran.rekening.store.url(), options);
        }
    };

    return (
        <AppLayout title="Rekening Pembayaran" breadcrumbs={breadcrumbs}>
            <PageHeader title="Rekening Pembayaran" subtitle="Rekening tujuan transfer yang ditampilkan kepada santri dan koordinator lembaga." breadcrumbs={breadcrumbs} />

            <MenuPembayaran />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_24rem] *:min-w-0">
                <div>
                    {rekening.length === 0 ? (
                        <EmptyState icon={Landmark} title="Belum ada rekening" description="Tambahkan rekening agar pembayar dapat mentransfer." />
                    ) : (
                        <ul className="flex flex-col gap-3">
                            {rekening.map((item) => (
                                <li
                                    key={item.id}
                                    className={cn(
                                        'flex flex-wrap items-start gap-4 rounded-2xl border bg-surface p-4 shadow-xs sm:p-5',
                                        editId === item.id ? 'border-brand-500' : 'border-line',
                                        !item.aktif && 'opacity-70',
                                    )}
                                >
                                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-400/10 dark:text-brand-300">
                                        <Icon icon={Landmark} className="size-5" />
                                    </span>

                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold text-fg">{item.nama_bank}</p>
                                            <Badge tone={item.aktif ? 'success' : 'neutral'}>{item.aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                                        </div>
                                        <p className="font-mono text-fg tabular-nums">{item.nomor_rekening}</p>
                                        <p className="text-sm text-fg-muted">a.n. {item.nama_pemilik}</p>
                                        {item.keterangan && <p className="mt-1 text-xs text-fg-subtle">{item.keterangan}</p>}
                                    </div>

                                    <div className="flex gap-2">
                                        <Button variant="secondary" size="sm" icon={Pencil} onClick={() => mulaiEdit(item)}>
                                            Edit
                                        </Button>
                                        <Button
                                            variant={item.aktif ? 'ghost' : 'success-outline'}
                                            size="sm"
                                            icon={Power}
                                            onClick={() => router.patch(admin.pembayaran.rekening.aktif.url(item.id), { aktif: !item.aktif }, { preserveScroll: true })}
                                        >
                                            {item.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                                            <span className="sr-only"> rekening {item.nama_bank}</span>
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <form onSubmit={submit} noValidate className="h-fit rounded-2xl border border-line bg-surface shadow-xs lg:sticky lg:top-20">
                    <header className="border-b border-line px-5 py-4">
                        <h2 className="text-base font-semibold text-fg">{editId ? 'Edit Rekening' : 'Tambah Rekening'}</h2>
                    </header>

                    <div className="flex flex-col gap-4 p-5">
                        <Field label="Nama bank" htmlFor="nama_bank" error={errors.nama_bank} required>
                            <input
                                id="nama_bank"
                                value={data.nama_bank}
                                maxLength={100}
                                onChange={(event) => setData('nama_bank', event.target.value)}
                                placeholder="Contoh: BSI"
                                className="form-control"
                                {...fieldAria('nama_bank', errors.nama_bank)}
                            />
                        </Field>
                        <Field label="Nomor rekening" htmlFor="nomor_rekening" error={errors.nomor_rekening} required>
                            <input
                                id="nomor_rekening"
                                inputMode="numeric"
                                value={data.nomor_rekening}
                                maxLength={50}
                                onChange={(event) => setData('nomor_rekening', event.target.value)}
                                className="form-control font-mono"
                                {...fieldAria('nomor_rekening', errors.nomor_rekening)}
                            />
                        </Field>
                        <Field label="Nama pemilik" htmlFor="nama_pemilik" error={errors.nama_pemilik} required>
                            <input
                                id="nama_pemilik"
                                value={data.nama_pemilik}
                                maxLength={255}
                                onChange={(event) => setData('nama_pemilik', event.target.value)}
                                className="form-control"
                                {...fieldAria('nama_pemilik', errors.nama_pemilik)}
                            />
                        </Field>
                        <Field label="Keterangan" htmlFor="keterangan" error={errors.keterangan} optional>
                            <input
                                id="keterangan"
                                value={data.keterangan}
                                maxLength={1000}
                                onChange={(event) => setData('keterangan', event.target.value)}
                                placeholder="Contoh: Khusus pembayaran SPP"
                                className="form-control"
                                {...fieldAria('keterangan', errors.keterangan)}
                            />
                        </Field>
                        <Field label="Urutan tampil" htmlFor="urutan" error={errors.urutan} hint="Angka kecil tampil lebih dulu.">
                            <input
                                id="urutan"
                                type="number"
                                min={0}
                                value={data.urutan}
                                onChange={(event) => setData('urutan', Number(event.target.value))}
                                className="form-control"
                                {...fieldAria('urutan', errors.urutan, 'Angka kecil tampil lebih dulu.')}
                            />
                        </Field>
                        <label className="flex items-center gap-2 text-sm font-medium text-fg">
                            <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 accent-brand-500" />
                            Aktif (ditawarkan kepada pembayar)
                        </label>
                    </div>

                    <div className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end">
                        {editId && (
                            <Button variant="secondary" onClick={batal}>
                                Batal
                            </Button>
                        )}
                        <Button type="submit" icon={editId ? Check : Plus} disabled={form.processing}>
                            {editId ? 'Simpan Perubahan' : 'Tambah Rekening'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
