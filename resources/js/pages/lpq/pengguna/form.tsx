import { useForm } from '@inertiajs/react';
import { Check, Eye, EyeOff, Plus } from 'lucide-react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';

import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import { PERAN, type Peran } from '@/lib/lpq';
import { cn } from '@/lib/utils';
import lpq from '@/routes/admin/lpq';
import type { BreadcrumbItem, Opsi } from '@/types';

interface Values {
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    no_hp: string;
    alamat: string;
    angkatan: string;
    program_ids: string[];
}

interface Props {
    peran: Peran;
    userId: string | null;
    values: Values;
    opsi: { program: Opsi[] };
}

export default function FormPengguna({ peran, userId, values, opsi }: Props) {
    const meta = PERAN[peran];
    const isEdit = userId !== null;
    const judul = isEdit ? `Edit ${meta.label}` : `Tambah ${meta.label}`;
    const punyaDetail = peran === 'santri' || peran === 'pengajar';
    const kembali = isEdit && punyaDetail ? lpq.pengguna.show.url([peran, userId]) : lpq.pengguna.index.url(peran);

    const breadcrumbs: BreadcrumbItem[] = [{ label: meta.jamak, href: lpq.pengguna.index.url(peran) }, { label: judul }];

    const form = useForm<Values>(values);
    const { data, setData, errors } = form;
    const [lihatPassword, setLihatPassword] = useState(false);
    const adaError = Object.keys(errors).length > 0;
    const errorAlert = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (adaError) {
            errorAlert.current?.focus();
        }
    }, [adaError, errors]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const opsiKirim = { onSuccess: () => form.reset('password', 'password_confirmation') };

        if (isEdit) {
            form.put(lpq.pengguna.update.url([peran, userId]), opsiKirim);
        } else {
            form.post(lpq.pengguna.store.url(peran), opsiKirim);
        }
    };

    const toggleProgram = (id: string) => {
        setData('program_ids', data.program_ids.includes(id) ? data.program_ids.filter((item) => item !== id) : [...data.program_ids, id]);
    };

    const hintPassword = isEdit ? 'Kosongkan bila tidak ingin mengganti password.' : 'Minimal 8 karakter.';

    return (
        <LpqLayout title={judul} breadcrumbs={breadcrumbs}>
            <form onSubmit={submit} noValidate className="max-w-3xl rounded-2xl border border-line bg-surface shadow-xs">
                {adaError && (
                    <div className="px-5 pt-5 sm:px-6 sm:pt-6">
                        <Alert ref={errorAlert} tone="danger" title="Data belum tersimpan">
                            Periksa kembali isian yang ditandai di bawah.
                        </Alert>
                    </div>
                )}

                <Bagian judul="Akun" deskripsi="Dipakai untuk masuk ke web & aplikasi NgajiKu.">
                    <Field label="Nama lengkap" htmlFor="username" error={errors.username} required className="sm:col-span-2">
                        <input
                            id="username"
                            value={data.username}
                            maxLength={255}
                            autoComplete="off"
                            onChange={(event) => setData('username', event.target.value)}
                            className="form-control"
                            {...fieldAria('username', errors.username)}
                        />
                    </Field>

                    <Field label="Email" htmlFor="email" error={errors.email} required className="sm:col-span-2">
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            autoComplete="off"
                            inputMode="email"
                            onChange={(event) => setData('email', event.target.value)}
                            placeholder="nama@contoh.com"
                            className="form-control"
                            {...fieldAria('email', errors.email)}
                        />
                    </Field>

                    <Field label={isEdit ? 'Password baru' : 'Password'} htmlFor="password" error={errors.password} hint={hintPassword} required={!isEdit} optional={isEdit}>
                        <div className="relative">
                            <input
                                id="password"
                                type={lihatPassword ? 'text' : 'password'}
                                value={data.password}
                                autoComplete="new-password"
                                onChange={(event) => setData('password', event.target.value)}
                                className="form-control pr-11"
                                {...fieldAria('password', errors.password, hintPassword)}
                            />
                            <button
                                type="button"
                                onClick={() => setLihatPassword((nilai) => !nilai)}
                                aria-label={lihatPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                                aria-pressed={lihatPassword}
                                className="absolute inset-y-0 right-0 grid w-11 place-items-center text-fg-subtle hover:text-fg"
                            >
                                <Icon icon={lihatPassword ? EyeOff : Eye} className="size-4" />
                            </button>
                        </div>
                    </Field>

                    <Field label="Ulangi password" htmlFor="password_confirmation" error={errors.password_confirmation} required={!isEdit || data.password !== ''}>
                        <input
                            id="password_confirmation"
                            type={lihatPassword ? 'text' : 'password'}
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            className="form-control"
                            {...fieldAria('password_confirmation', errors.password_confirmation)}
                        />
                    </Field>
                </Bagian>

                <Bagian judul="Profil" deskripsi="Kontak dan informasi tambahan.">
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

                    {peran === 'santri' && (
                        <Field label="Angkatan" htmlFor="angkatan" error={errors.angkatan} optional>
                            <input
                                id="angkatan"
                                value={data.angkatan}
                                maxLength={32}
                                onChange={(event) => setData('angkatan', event.target.value)}
                                placeholder={String(new Date().getFullYear())}
                                className="form-control"
                                {...fieldAria('angkatan', errors.angkatan)}
                            />
                        </Field>
                    )}

                    <Field label="Alamat" htmlFor="alamat" error={errors.alamat} optional className="sm:col-span-2">
                        <textarea
                            id="alamat"
                            rows={3}
                            value={data.alamat}
                            onChange={(event) => setData('alamat', event.target.value)}
                            className="form-control h-auto py-2.5"
                            {...fieldAria('alamat', errors.alamat)}
                        />
                    </Field>
                </Bagian>

                {peran === 'pengajar' && (
                    <Bagian judul="Program yang diajar" deskripsi="Menentukan pengajar yang bisa dipilih di absensi tiap program.">
                        <fieldset className="sm:col-span-2">
                            <legend className="sr-only">Program yang diajar</legend>
                            {opsi.program.length === 0 ? (
                                <p className="text-sm text-fg-muted">Belum ada program.</p>
                            ) : (
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {opsi.program.map((program) => {
                                        const dipilih = data.program_ids.includes(program.id);

                                        return (
                                            <label
                                                key={program.id}
                                                className={cn(
                                                    'flex h-11 cursor-pointer items-center gap-3 rounded-xl border px-3.5 text-sm font-medium transition-colors',
                                                    'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500',
                                                    dipilih
                                                        ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-400/10 dark:text-brand-200'
                                                        : 'border-line-strong text-fg hover:bg-surface-muted',
                                                )}
                                            >
                                                <input type="checkbox" checked={dipilih} onChange={() => toggleProgram(program.id)} className="size-4 accent-brand-500" />
                                                {program.nama}
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                            {errors.program_ids && <p className="mt-1.5 text-xs font-medium text-error-700 dark:text-error-300">{errors.program_ids}</p>}
                        </fieldset>
                    </Bagian>
                )}

                <div className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <Button variant="secondary" href={kembali}>
                        Batal
                    </Button>
                    <Button type="submit" icon={isEdit ? Check : Plus} disabled={form.processing}>
                        {isEdit ? 'Simpan Perubahan' : `Buat Akun ${meta.label}`}
                    </Button>
                </div>
            </form>
        </LpqLayout>
    );
}

function Bagian({ judul, deskripsi, children }: { judul: string; deskripsi: string; children: ReactNode }) {
    return (
        <section className="border-b border-line p-5 last-of-type:border-b-0 sm:p-6">
            <div className="mb-4">
                <h2 className="text-sm font-semibold text-fg">{judul}</h2>
                <p className="text-sm text-fg-muted">{deskripsi}</p>
            </div>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">{children}</div>
        </section>
    );
}
