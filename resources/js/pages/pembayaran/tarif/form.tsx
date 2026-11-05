import { useForm } from '@inertiajs/react';
import { Check, Plus } from 'lucide-react';
import { useEffect, useRef, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, fieldAria } from '@/components/ui/field';
import { SelectMenu } from '@/components/ui/select-menu';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { BreadcrumbItem, Opsi } from '@/types';

type Jenis = 'reguler' | 'privat' | 'lembaga';

interface Values {
    nama_tarif: string;
    kelas_type_id: string;
    program_id: string;
    santri_program_id: string;
    lembaga_id: string;
    nominal: string;
    satuan: string;
    berlaku_mulai: string;
    berlaku_sampai: string;
    aktif: boolean;
}

interface Props {
    tarifId: string | null;
    values: Values;
    opsi: {
        jenis_layanan: Array<{ id: string; nama: string; jenis: Jenis | null; satuan: string | null }>;
        program: Opsi[];
        lembaga: Array<Opsi & { program_id: string | null }>;
        santri_privat: Opsi[];
    };
}

const PENJELASAN: Record<Jenis, string> = {
    reguler: 'Berlaku untuk semua santri Reguler dan ditagih per bulan melalui Generate Tagihan.',
    privat: 'Khusus satu santri layanan Privat, dihitung per pertemuan.',
    lembaga: 'Untuk satu lembaga beserta programnya, dihitung per pertemuan.',
};

export default function FormTarif({ tarifId, values, opsi }: Props) {
    const isEdit = tarifId !== null;
    const judul = isEdit ? 'Edit Tarif' : 'Tambah Tarif';
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Pembayaran', href: admin.pembayaran.index.url() },
        { label: 'Tarif', href: admin.pembayaran.tarif.index.url() },
        { label: judul },
    ];

    const form = useForm<Values>(values);
    const { data, setData, errors } = form;
    const layanan = opsi.jenis_layanan.find((item) => item.id === data.kelas_type_id);
    const jenis = layanan?.jenis ?? null;
    const adaError = Object.keys(errors).length > 0;
    const errorAlert = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (adaError) {
            errorAlert.current?.focus();
        }
    }, [adaError, errors]);

    /** Ganti jenis layanan: satuan mengikuti jenis, target yang tidak relevan dikosongkan. */
    const pilihJenis = (id: string) => {
        const pilihan = opsi.jenis_layanan.find((item) => item.id === id);

        setData((sebelum) => ({
            ...sebelum,
            kelas_type_id: id,
            satuan: pilihan?.satuan ?? '',
            program_id: pilihan?.jenis === 'lembaga' ? sebelum.program_id : '',
            lembaga_id: pilihan?.jenis === 'lembaga' ? sebelum.lembaga_id : '',
            santri_program_id: pilihan?.jenis === 'privat' ? sebelum.santri_program_id : '',
        }));
    };

    /** Program tarif Lembaga selalu mengikuti program lembaganya. */
    const pilihLembaga = (id: string) => {
        setData((sebelum) => ({ ...sebelum, lembaga_id: id, program_id: opsi.lembaga.find((item) => item.id === id)?.program_id ?? '' }));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(admin.pembayaran.tarif.update.url(tarifId));
        } else {
            form.post(admin.pembayaran.tarif.store.url());
        }
    };

    return (
        <AppLayout title={judul} breadcrumbs={breadcrumbs}>
            <PageHeader title={judul} breadcrumbs={breadcrumbs} />

            <MenuPembayaran />

            <form onSubmit={submit} noValidate className="max-w-3xl rounded-2xl border border-line bg-surface shadow-xs">
                {adaError && (
                    <div className="px-5 pt-5 sm:px-6 sm:pt-6">
                        <Alert ref={errorAlert} tone="danger" title="Tarif belum tersimpan">
                            Periksa kembali isian yang ditandai di bawah.
                        </Alert>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <Field as="fieldset" label="Jenis layanan" htmlFor="kelas_type_id" error={errors.kelas_type_id} required className="sm:col-span-2">
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            {opsi.jenis_layanan.map((item) => (
                                <label
                                    key={item.id}
                                    className={cn(
                                        'flex h-11 cursor-pointer items-center justify-center rounded-xl border text-sm font-medium transition-colors sm:h-10',
                                        'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 has-[:focus-visible]:ring-offset-2 has-[:focus-visible]:ring-offset-surface',
                                        data.kelas_type_id === item.id
                                            ? 'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-400 dark:bg-brand-400/10 dark:text-brand-300'
                                            : 'border-line-strong text-fg-muted hover:bg-surface-muted',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="kelas_type_id"
                                        value={item.id}
                                        checked={data.kelas_type_id === item.id}
                                        onChange={() => pilihJenis(item.id)}
                                        className="sr-only"
                                    />
                                    {item.nama}
                                </label>
                            ))}
                        </div>
                        {jenis && <p className="mt-2 text-sm text-fg-muted">{PENJELASAN[jenis]}</p>}
                    </Field>

                    <Field label="Nama tarif" htmlFor="nama_tarif" error={errors.nama_tarif} required className="sm:col-span-2">
                        <input
                            id="nama_tarif"
                            value={data.nama_tarif}
                            maxLength={255}
                            onChange={(event) => setData('nama_tarif', event.target.value)}
                            placeholder="Contoh: SPP Reguler 2026/2027"
                            className="form-control"
                            {...fieldAria('nama_tarif', errors.nama_tarif)}
                        />
                    </Field>

                    {jenis === 'privat' && (
                        <Field label="Santri" htmlFor="santri_program_id" error={errors.santri_program_id} required className="sm:col-span-2">
                            <SelectMenu
                                id="santri_program_id"
                                searchable
                                value={data.santri_program_id}
                                onChange={(value) => setData('santri_program_id', value)}
                                options={opsi.santri_privat.map((item) => ({ value: item.id, label: item.nama }))}
                                placeholder="Pilih santri Privat"
                                searchPlaceholder="Cari nama santri…"
                                emptyText="Belum ada santri layanan Privat."
                                invalid={Boolean(errors.santri_program_id)}
                                describedBy={errors.santri_program_id ? 'santri_program_id-error' : undefined}
                            />
                        </Field>
                    )}

                    {jenis === 'lembaga' && (
                        <>
                            <Field label="Lembaga" htmlFor="lembaga_id" error={errors.lembaga_id} required>
                                <select
                                    id="lembaga_id"
                                    value={data.lembaga_id}
                                    onChange={(event) => pilihLembaga(event.target.value)}
                                    className="form-control"
                                    {...fieldAria('lembaga_id', errors.lembaga_id)}
                                >
                                    <option value="">— Pilih lembaga —</option>
                                    {opsi.lembaga.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Program" htmlFor="program_id" error={errors.program_id} hint="Mengikuti program lembaga." required>
                                <select
                                    id="program_id"
                                    value={data.program_id}
                                    onChange={(event) => setData('program_id', event.target.value)}
                                    className="form-control"
                                    {...fieldAria('program_id', errors.program_id, 'Mengikuti program lembaga.')}
                                >
                                    <option value="">— Pilih program —</option>
                                    {opsi.program.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        </>
                    )}

                    <Field label="Nominal (Rp)" htmlFor="nominal" error={errors.nominal} required>
                        <input
                            id="nominal"
                            type="number"
                            min={1}
                            step={1}
                            inputMode="numeric"
                            value={data.nominal}
                            onChange={(event) => setData('nominal', event.target.value)}
                            placeholder="150000"
                            className="form-control tabular-nums"
                            {...fieldAria('nominal', errors.nominal)}
                        />
                    </Field>

                    <Field label="Satuan" htmlFor="satuan" error={errors.satuan} hint="Otomatis sesuai jenis layanan.">
                        <input
                            id="satuan"
                            value={data.satuan ? `per ${data.satuan}` : '-'}
                            readOnly
                            className="form-control bg-surface-muted"
                            {...fieldAria('satuan', errors.satuan, 'Otomatis sesuai jenis layanan.')}
                        />
                    </Field>

                    <Field label="Berlaku mulai" htmlFor="berlaku_mulai" error={errors.berlaku_mulai} required>
                        <input
                            id="berlaku_mulai"
                            type="date"
                            value={data.berlaku_mulai}
                            onChange={(event) => setData('berlaku_mulai', event.target.value)}
                            className="form-control"
                            {...fieldAria('berlaku_mulai', errors.berlaku_mulai)}
                        />
                    </Field>

                    <Field label="Berlaku sampai" htmlFor="berlaku_sampai" error={errors.berlaku_sampai} hint="Kosongkan bila berlaku seterusnya." optional>
                        <input
                            id="berlaku_sampai"
                            type="date"
                            value={data.berlaku_sampai}
                            min={data.berlaku_mulai}
                            onChange={(event) => setData('berlaku_sampai', event.target.value)}
                            className="form-control"
                            {...fieldAria('berlaku_sampai', errors.berlaku_sampai, 'Kosongkan bila berlaku seterusnya.')}
                        />
                    </Field>

                    <label className="flex items-center gap-2 text-sm font-medium text-fg sm:col-span-2">
                        <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 accent-brand-500" />
                        Tarif aktif
                    </label>
                </div>

                <div className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <Button variant="secondary" href={admin.pembayaran.tarif.index.url()}>
                        Batal
                    </Button>
                    <Button type="submit" icon={isEdit ? Check : Plus} disabled={form.processing}>
                        {isEdit ? 'Simpan Perubahan' : 'Tambah Tarif'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
