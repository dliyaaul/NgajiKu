import { useForm } from '@inertiajs/react';
import { ArrowRight, Calendar, Check, MapPin, Monitor } from 'lucide-react';
import { useEffect, useRef, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM } from '@/lib/program';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, KelasRingkas, Opsi, ProgramKey } from '@/types';

interface Values {
    tanggal: string;
    pengajar_id: string | null;
    pengganti_id: string | null;
    materi_pembelajaran: string;
    metode: string;
}

interface Props {
    program: ProgramKey;
    kelas: KelasRingkas;
    /** null = tambah pertemuan baru. */
    pertemuanId: string | null;
    values: Values;
    metodeOptions: string[];
    pengajarOptions: Opsi[];
    penggantiOptions: Opsi[];
}

const METODE_ICON = { Offline: MapPin, Online: Monitor } as const;

export default function PertemuanForm({ program, kelas, pertemuanId, values, metodeOptions, pengajarOptions, penggantiOptions }: Props) {
    const isEdit = pertemuanId !== null;
    const label = PROGRAM[program].label;
    const judul = `${isEdit ? 'Edit' : 'Tambah'} Pertemuan`;
    const daftarUrl = absensi.pertemuan.index.url({ program, kelas: kelas.id });

    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Absensi', href: absensi.index.url() },
        { label, href: absensi.kelas.url(program) },
        { label: kelas.nama_kelas, href: daftarUrl },
        { label: judul },
    ];

    const form = useForm({
        tanggal: values.tanggal,
        pengajar_id: values.pengajar_id ?? '',
        pengganti_id: values.pengganti_id ?? '',
        materi_pembelajaran: values.materi_pembelajaran,
        metode: values.metode,
    });
    const { data, setData, errors } = form;
    const adaError = Object.keys(errors).length > 0;

    // Arahkan fokus ke ringkasan galat agar pembaca layar langsung tahu form belum tersimpan.
    const errorAlert = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (adaError) {
            errorAlert.current?.focus();
        }
    }, [adaError, errors]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(absensi.pertemuan.update.url({ program, pertemuan: pertemuanId }));
        } else {
            form.post(absensi.pertemuan.store.url({ program, kelas: kelas.id }));
        }
    };

    return (
        <AppLayout title={judul} breadcrumbs={breadcrumbs}>
            <PageHeader title={`${judul} ${label}`} subtitle={kelas.nama_kelas} breadcrumbs={breadcrumbs} />

            <form onSubmit={submit} noValidate className="max-w-3xl rounded-2xl border border-line bg-surface shadow-xs">
                {adaError && (
                    <div className="px-5 pt-5 sm:px-6 sm:pt-6">
                        <Alert ref={errorAlert} tone="danger" title="Pertemuan belum tersimpan">
                            Periksa kembali isian yang ditandai di bawah.
                        </Alert>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <Field label="Tanggal" htmlFor="tanggal" error={errors.tanggal} required>
                        <input
                            type="date"
                            id="tanggal"
                            value={data.tanggal}
                            onChange={(event) => setData('tanggal', event.target.value)}
                            required
                            className="form-control"
                            {...fieldAria('tanggal', errors.tanggal)}
                        />
                    </Field>

                    <Field as="fieldset" label="Metode" htmlFor="metode" error={errors.metode} required>
                        <div className="grid grid-cols-2 gap-2">
                            {metodeOptions.map((metode) => (
                                <label
                                    key={metode}
                                    className="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-line-strong bg-surface text-sm font-medium text-fg-muted transition-colors hover:bg-surface-muted has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-600 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 has-[:focus-visible]:ring-offset-2 has-[:focus-visible]:ring-offset-surface sm:h-10 dark:has-checked:border-brand-400 dark:has-checked:bg-brand-400/10 dark:has-checked:text-brand-300"
                                >
                                    <input
                                        type="radio"
                                        name="metode"
                                        value={metode}
                                        checked={data.metode === metode}
                                        onChange={() => setData('metode', metode)}
                                        className="sr-only"
                                    />
                                    <Icon icon={METODE_ICON[metode as keyof typeof METODE_ICON] ?? Calendar} className="size-4" />
                                    {metode}
                                </label>
                            ))}
                        </div>
                    </Field>

                    <Field label="Pengajar" htmlFor="pengajar_id" error={errors.pengajar_id}>
                        <select
                            id="pengajar_id"
                            value={data.pengajar_id}
                            onChange={(event) => setData('pengajar_id', event.target.value)}
                            className="form-control"
                            {...fieldAria('pengajar_id', errors.pengajar_id)}
                        >
                            <option value="">— Pilih pengajar —</option>
                            {pengajarOptions.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.nama}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Pengajar Pengganti" htmlFor="pengganti_id" error={errors.pengganti_id} optional>
                        <select
                            id="pengganti_id"
                            value={data.pengganti_id}
                            onChange={(event) => setData('pengganti_id', event.target.value)}
                            className="form-control"
                            {...fieldAria('pengganti_id', errors.pengganti_id)}
                        >
                            <option value="">— Tidak ada —</option>
                            {penggantiOptions.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.nama}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field
                        label="Materi Pembelajaran"
                        htmlFor="materi_pembelajaran"
                        error={errors.materi_pembelajaran}
                        hint="Maksimal 1.000 karakter."
                        optional
                        className="sm:col-span-2"
                    >
                        <textarea
                            id="materi_pembelajaran"
                            rows={4}
                            maxLength={1000}
                            value={data.materi_pembelajaran}
                            onChange={(event) => setData('materi_pembelajaran', event.target.value)}
                            placeholder="Contoh: Makharijul huruf, Iqro jilid 3 halaman 10–12"
                            className="form-control h-auto py-2.5"
                            {...fieldAria('materi_pembelajaran', errors.materi_pembelajaran, 'Maksimal 1.000 karakter.')}
                        />
                    </Field>
                </div>

                <div className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <Button variant="secondary" href={daftarUrl}>
                        Batal
                    </Button>
                    <Button type="submit" icon={isEdit ? Check : ArrowRight} disabled={form.processing}>
                        {isEdit ? 'Simpan Perubahan' : 'Simpan & Isi Absensi'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
