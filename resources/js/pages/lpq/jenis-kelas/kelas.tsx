import { router, useForm } from '@inertiajs/react';
import { Building2, Check, GraduationCap, Pencil, Plus, Power, School, Trash2, UsersRound } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';

import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Toolbar } from '@/components/lpq/toolbar';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import { Modal } from '@/components/ui/modal';
import { SelectMenu } from '@/components/ui/select-menu';
import { JENIS, type Jenis } from '@/lib/lpq';
import { cn } from '@/lib/utils';
import lpq from '@/routes/admin/lpq';
import type { BreadcrumbItem, Opsi } from '@/types';

interface Kelas {
    id: string;
    nama_kelas: string;
    aktif: boolean;
    program_id: string;
    program: string | null;
    pengajar_id: string | null;
    pengajar: string | null;
    lembaga_id: string | null;
    lembaga: string | null;
    jumlah_santri: number;
}

interface OpsiKelas {
    program: Opsi[];
    pengajar: Opsi[];
    lembaga: Array<Opsi & { program_id: string | null; kelas_id: string | null }>;
}

interface Props {
    jenisKelas: { id: string; nama: string; jenis: Jenis | null };
    kelas: Kelas[];
    filter: { status: string; program: string; cari: string };
    opsi: OpsiKelas;
}

export default function DaftarKelas({ jenisKelas, kelas, filter, opsi }: Props) {
    const [form, setForm] = useState<{ open: boolean; data: Kelas | null }>({ open: false, data: null });
    const isLembaga = jenisKelas.jenis === 'lembaga';
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Jenis Kelas', href: lpq.jenisKelas.index.url() }, { label: jenisKelas.nama }];
    const adaFilter = Boolean(filter.cari || filter.program || filter.status !== 'semua');

    return (
        <LpqLayout
            title={`Kelas ${jenisKelas.nama}`}
            subtitle={jenisKelas.jenis ? JENIS[jenisKelas.jenis].deskripsi : undefined}
            breadcrumbs={breadcrumbs}
            actions={
                <Button icon={Plus} onClick={() => setForm({ open: true, data: null })}>
                    Tambah Kelas
                </Button>
            }
        >
            <Toolbar
                url={(query) => lpq.kelas.index.url(jenisKelas.id, { query })}
                filter={{ cari: filter.cari, program: filter.program, status: filter.status === 'semua' ? '' : filter.status }}
                placeholder="Cari nama kelas…"
                selects={[
                    { name: 'program', label: 'Filter program', semua: 'Semua program', options: opsi.program.map((item) => ({ value: item.id, label: item.nama })) },
                    {
                        name: 'status',
                        label: 'Filter status',
                        semua: 'Semua status',
                        options: [
                            { value: 'aktif', label: 'Aktif' },
                            { value: 'nonaktif', label: 'Nonaktif' },
                        ],
                    },
                ]}
            />

            {kelas.length === 0 ? (
                <EmptyState
                    icon={School}
                    title={adaFilter ? 'Tidak ada kelas yang cocok' : `Belum ada kelas ${jenisKelas.nama}`}
                    description={adaFilter ? 'Coba filter lain.' : 'Buat kelas agar santri dapat ditempatkan dan diabsen.'}
                />
            ) : (
                <ul className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    {kelas.map((item) => (
                        <li key={item.id} className={cn('rounded-2xl border border-line bg-surface p-5 shadow-xs', !item.aktif && 'opacity-75')}>
                            <div className="flex items-start gap-3">
                                <div className="min-w-0 flex-1">
                                    <p className="flex flex-wrap items-center gap-2 font-semibold text-fg">
                                        {item.nama_kelas}
                                        {!item.aktif && <Badge>Nonaktif</Badge>}
                                    </p>
                                    <p className="mt-0.5 text-sm text-fg-muted">{item.program ?? '-'}</p>
                                </div>

                                <div className="-mt-1 -mr-2 flex shrink-0">
                                    <Button variant="ghost" size="sm" icon={Pencil} onClick={() => setForm({ open: true, data: item })}>
                                        <span className="sr-only">Edit {item.nama_kelas}</span>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        icon={Power}
                                        title={item.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                                        onClick={() => router.patch(lpq.kelas.aktif.url(item.id), { aktif: !item.aktif }, { preserveScroll: true })}
                                    >
                                        <span className="sr-only">
                                            {item.aktif ? 'Nonaktifkan' : 'Aktifkan'} {item.nama_kelas}
                                        </span>
                                    </Button>
                                    {item.jumlah_santri === 0 && (
                                        <ConfirmDialog
                                            title={`Hapus ${item.nama_kelas}?`}
                                            url={lpq.kelas.destroy.url(item.id)}
                                            trigger={(open) => (
                                                <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="hover:text-error-700 dark:hover:text-error-300">
                                                    <span className="sr-only">Hapus {item.nama_kelas}</span>
                                                </Button>
                                            )}
                                        >
                                            Kelas yang sudah memiliki pertemuan atau riwayat tidak dapat dihapus; nonaktifkan saja.
                                        </ConfirmDialog>
                                    )}
                                </div>
                            </div>

                            <dl className="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-line pt-3 text-sm">
                                <div className="flex items-center gap-1.5">
                                    <dt>
                                        <Icon icon={GraduationCap} className="size-4 text-fg-subtle" />
                                        <span className="sr-only">Pengajar</span>
                                    </dt>
                                    <dd className={item.pengajar ? 'text-fg' : 'text-warning-700 dark:text-warning-300'}>{item.pengajar ?? 'Belum ada pengajar'}</dd>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <dt>
                                        <Icon icon={UsersRound} className="size-4 text-fg-subtle" />
                                        <span className="sr-only">Jumlah santri</span>
                                    </dt>
                                    <dd className="text-fg tabular-nums">{item.jumlah_santri} santri</dd>
                                </div>
                                {item.lembaga && (
                                    <div className="flex items-center gap-1.5">
                                        <dt>
                                            <Icon icon={Building2} className="size-4 text-fg-subtle" />
                                            <span className="sr-only">Lembaga</span>
                                        </dt>
                                        <dd className="text-fg">{item.lembaga}</dd>
                                    </div>
                                )}
                            </dl>
                        </li>
                    ))}
                </ul>
            )}

            <FormKelas
                open={form.open}
                kelas={form.data}
                kelasTypeId={jenisKelas.id}
                isLembaga={isLembaga}
                opsi={opsi}
                onClose={() => setForm((sebelum) => ({ ...sebelum, open: false }))}
            />
        </LpqLayout>
    );
}

function FormKelas({
    open,
    kelas,
    kelasTypeId,
    isLembaga,
    opsi,
    onClose,
}: {
    open: boolean;
    kelas: Kelas | null;
    kelasTypeId: string;
    isLembaga: boolean;
    opsi: OpsiKelas;
    onClose: () => void;
}) {
    const form = useForm({ nama_kelas: '', program_id: '', pengajar_id: '', lembaga_id: '', aktif: true });
    const { data, setData, errors } = form;

    useEffect(() => {
        if (open) {
            form.setData({
                nama_kelas: kelas?.nama_kelas ?? '',
                program_id: kelas?.program_id ?? '',
                pengajar_id: kelas?.pengajar_id ?? '',
                lembaga_id: kelas?.lembaga_id ?? '',
                aktif: kelas?.aktif ?? true,
            });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, kelas?.id]);

    // Lembaga yang programnya sesuai dan belum punya kelas (kecuali kelas ini sendiri).
    const lembagaTersedia = opsi.lembaga.filter((item) => item.program_id === data.program_id && (item.kelas_id === null || item.kelas_id === kelas?.id));
    const hintLembaga = data.program_id ? 'Satu lembaga hanya boleh memiliki satu kelas.' : 'Pilih program terlebih dahulu.';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const opsiKirim = { preserveScroll: true, onSuccess: () => onClose() };

        if (kelas) {
            form.put(lpq.kelas.update.url(kelas.id), opsiKirim);
        } else {
            form.post(lpq.kelas.store.url(kelasTypeId), opsiKirim);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={kelas ? 'Edit Kelas' : 'Tambah Kelas'}
            description={kelas && kelas.jumlah_santri > 0 ? 'Mengganti pengajar juga memperbarui pengajar santri di kelas ini.' : undefined}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Batal
                    </Button>
                    <Button type="submit" form="form-kelas" icon={kelas ? Check : Plus} disabled={form.processing}>
                        {kelas ? 'Simpan' : 'Tambah'}
                    </Button>
                </>
            }
        >
            <form id="form-kelas" onSubmit={submit} noValidate className="flex flex-col gap-5">
                {Object.keys(errors).length > 0 && <Alert tone="danger">Periksa kembali isian yang ditandai.</Alert>}

                <Field label="Nama kelas" htmlFor="nama_kelas" error={errors.nama_kelas} required>
                    <input
                        id="nama_kelas"
                        value={data.nama_kelas}
                        maxLength={255}
                        onChange={(event) => setData('nama_kelas', event.target.value)}
                        placeholder="Contoh: Jilid 1 Pagi"
                        className="form-control"
                        {...fieldAria('nama_kelas', errors.nama_kelas)}
                    />
                </Field>

                <Field label="Program" htmlFor="program_id" error={errors.program_id} required>
                    <select
                        id="program_id"
                        value={data.program_id}
                        onChange={(event) => setData((sebelum) => ({ ...sebelum, program_id: event.target.value, lembaga_id: '' }))}
                        className="form-control"
                        {...fieldAria('program_id', errors.program_id)}
                    >
                        <option value="">— Pilih program —</option>
                        {opsi.program.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.nama}
                            </option>
                        ))}
                    </select>
                </Field>

                {isLembaga && (
                    <Field label="Lembaga" htmlFor="lembaga_id" error={errors.lembaga_id} hint={hintLembaga} required>
                        <select
                            id="lembaga_id"
                            value={data.lembaga_id}
                            disabled={!data.program_id}
                            onChange={(event) => setData('lembaga_id', event.target.value)}
                            className="form-control"
                            {...fieldAria('lembaga_id', errors.lembaga_id, hintLembaga)}
                        >
                            <option value="">— Pilih lembaga —</option>
                            {lembagaTersedia.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.nama}
                                </option>
                            ))}
                        </select>
                    </Field>
                )}

                <Field label="Pengajar" htmlFor="pengajar_id" error={errors.pengajar_id} required={isLembaga} optional={!isLembaga}>
                    <SelectMenu
                        id="pengajar_id"
                        searchable
                        value={data.pengajar_id}
                        onChange={(value) => setData('pengajar_id', value)}
                        options={[...(isLembaga ? [] : [{ value: '', label: 'Belum ditentukan' }]), ...opsi.pengajar.map((item) => ({ value: item.id, label: item.nama }))]}
                        placeholder="Pilih pengajar"
                        searchPlaceholder="Cari nama pengajar…"
                        emptyText="Belum ada akun pengajar."
                        invalid={Boolean(errors.pengajar_id)}
                        describedBy={errors.pengajar_id ? 'pengajar_id-error' : undefined}
                    />
                </Field>

                <label className="flex items-center gap-2 text-sm font-medium text-fg">
                    <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 accent-brand-500" />
                    Kelas aktif
                </label>
            </form>
        </Modal>
    );
}
