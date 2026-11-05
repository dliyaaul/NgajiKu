import { useForm } from '@inertiajs/react';
import { Check, Plus } from 'lucide-react';
import { useEffect, type FormEvent } from 'react';

import { Segmented } from '@/components/lpq/segmented';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, fieldAria } from '@/components/ui/field';
import { Modal } from '@/components/ui/modal';
import { PBM, type Jenis } from '@/lib/lpq';
import lpq from '@/routes/admin/lpq';
import type { Opsi } from '@/types';

export interface Pendaftaran {
    id: string;
    program_id: string;
    program: string | null;
    kelas_type_id: string;
    jenis_kelas: string | null;
    jenis: Jenis | null;
    kelas_pengajaran_id: string | null;
    kelas: string | null;
    pengajar: string | null;
    lembaga_id: string | null;
    lembaga: string | null;
    default_pbm: string | null;
    terkunci: boolean;
    riwayat: Array<{ id: string; dari: string | null; ke: string | null; berlaku_mulai: string; alasan: string | null; oleh: string | null }>;
}

export interface OpsiPendaftaran {
    program: Opsi[];
    jenis_kelas: Array<Opsi & { jenis: Jenis | null }>;
    kelas: Array<Opsi & { program_id: string; kelas_type_id: string; lembaga_id: string | null; pengajar: string | null; aktif: boolean }>;
    lembaga: Array<Opsi & { program_id: string | null; pbm_type: string }>;
}

interface Values {
    sumber: 'individu' | 'lembaga';
    program_id: string;
    kelas_type_id: string;
    kelas_pengajaran_id: string;
    lembaga_id: string;
    default_pbm: string;
    alasan: string;
}

/**
 * Daftarkan/ubah program santri. Sumber "Lembaga" mengikuti lembaga (program, jenis, PBM terisi
 * otomatis); sumber "Individu" untuk layanan Reguler & Privat.
 */
export function FormPendaftaran({
    open,
    onClose,
    santriId,
    pendaftaran,
    programTerpakai,
    opsi,
}: {
    open: boolean;
    onClose: () => void;
    santriId: string;
    pendaftaran: Pendaftaran | null;
    programTerpakai: string[];
    opsi: OpsiPendaftaran;
}) {
    const isEdit = pendaftaran !== null;
    const jenisLembaga = opsi.jenis_kelas.find((item) => item.jenis === 'lembaga');
    const jenisIndividu = opsi.jenis_kelas.filter((item) => item.jenis !== 'lembaga');

    const awal = (): Values => ({
        sumber: pendaftaran?.lembaga_id ? 'lembaga' : 'individu',
        program_id: pendaftaran?.program_id ?? '',
        kelas_type_id: pendaftaran?.kelas_type_id ?? jenisIndividu[0]?.id ?? '',
        kelas_pengajaran_id: pendaftaran?.kelas_pengajaran_id ?? '',
        lembaga_id: pendaftaran?.lembaga_id ?? '',
        default_pbm: pendaftaran?.default_pbm ?? 'Offline',
        alasan: '',
    });

    const form = useForm<Values>(awal());
    const { data, setData, errors } = form;

    // Isi ulang setiap kali jendela dibuka untuk pendaftaran lain.
    useEffect(() => {
        if (open) {
            form.setDefaults(awal());
            form.reset();
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, pendaftaran?.id]);

    const lembagaTerpilih = opsi.lembaga.find((item) => item.id === data.lembaga_id);
    const programTersedia = opsi.program.filter((item) => item.id === pendaftaran?.program_id || !programTerpakai.includes(item.id));
    const lembagaTersedia = opsi.lembaga.filter((item) => item.program_id && programTersedia.some((program) => program.id === item.program_id));

    const kelasTersedia = opsi.kelas.filter((item) => {
        if (item.id === pendaftaran?.kelas_pengajaran_id) {
            return true;
        }

        if (!item.aktif) {
            return false;
        }

        return data.sumber === 'lembaga'
            ? item.lembaga_id !== null && item.lembaga_id === data.lembaga_id
            : item.lembaga_id === null && item.program_id === data.program_id && item.kelas_type_id === data.kelas_type_id;
    });

    const kelasTerpilih = opsi.kelas.find((item) => item.id === data.kelas_pengajaran_id);
    const pindahKelas = pendaftaran !== null && (pendaftaran.kelas_pengajaran_id ?? '') !== data.kelas_pengajaran_id;
    const hintKelas =
        kelasTersedia.length === 0 ? 'Belum ada kelas aktif yang cocok. Buat dulu di menu Jenis Kelas.' : 'Boleh dikosongkan bila belum ditempatkan.';

    const pilihSumber = (sumber: Values['sumber']) => {
        setData((sebelum) => ({
            ...sebelum,
            sumber,
            program_id: sumber === 'lembaga' ? '' : sebelum.program_id,
            kelas_type_id: sumber === 'lembaga' ? (jenisLembaga?.id ?? '') : (jenisIndividu[0]?.id ?? ''),
            lembaga_id: '',
            kelas_pengajaran_id: '',
        }));
    };

    /** Lembaga menentukan program, jenis kelas, PBM, dan (bila sudah ada) kelasnya. */
    const pilihLembaga = (id: string) => {
        const lembaga = opsi.lembaga.find((item) => item.id === id);
        const kelasLembaga = opsi.kelas.find((item) => item.lembaga_id === id && item.aktif);

        setData((sebelum) => ({
            ...sebelum,
            lembaga_id: id,
            program_id: lembaga?.program_id ?? '',
            kelas_type_id: jenisLembaga?.id ?? '',
            default_pbm: lembaga?.pbm_type ?? sebelum.default_pbm,
            kelas_pengajaran_id: kelasLembaga?.id ?? '',
        }));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.transform(({ sumber: _sumber, ...isian }) => isian);

        const opsiKirim = { preserveScroll: true, onSuccess: () => onClose() };

        if (pendaftaran) {
            form.put(lpq.santriProgram.update.url(pendaftaran.id), opsiKirim);
        } else {
            form.post(lpq.santriProgram.store.url(santriId), opsiKirim);
        }
    };

    const adaError = Object.keys(errors).length > 0;

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={pendaftaran ? `Ubah ${pendaftaran.program}` : 'Daftarkan Program'}
            description={isEdit ? 'Perpindahan kelas otomatis tercatat di riwayat.' : 'Pilih program dan tempatkan santri di kelas.'}
            size="lg"
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Batal
                    </Button>
                    <Button type="submit" form="form-pendaftaran" icon={isEdit ? Check : Plus} disabled={form.processing}>
                        {isEdit ? 'Simpan' : 'Daftarkan'}
                    </Button>
                </>
            }
        >
            <form id="form-pendaftaran" onSubmit={submit} noValidate className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                {adaError && (
                    <Alert tone="danger" className="sm:col-span-2">
                        Periksa kembali isian yang ditandai.
                    </Alert>
                )}

                <Field as="fieldset" label="Sumber pendaftaran" htmlFor="sumber" className="sm:col-span-2">
                    <Segmented
                        name="sumber"
                        value={data.sumber}
                        onChange={(nilai) => pilihSumber(nilai as Values['sumber'])}
                        options={[
                            { value: 'individu', label: 'Individu', hint: 'Reguler / Privat' },
                            { value: 'lembaga', label: 'Lembaga', hint: 'Melalui lembaga mitra', disabled: !jenisLembaga },
                        ]}
                    />
                </Field>

                {data.sumber === 'lembaga' ? (
                    <>
                        <Field label="Lembaga" htmlFor="lembaga_id" error={errors.lembaga_id} required className="sm:col-span-2">
                            <select
                                id="lembaga_id"
                                value={data.lembaga_id}
                                onChange={(event) => pilihLembaga(event.target.value)}
                                className="form-control"
                                {...fieldAria('lembaga_id', errors.lembaga_id)}
                            >
                                <option value="">— Pilih lembaga —</option>
                                {lembagaTersedia.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.nama}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Program" htmlFor="program_lembaga" error={errors.program_id} hint="Mengikuti program lembaga.">
                            <input
                                id="program_lembaga"
                                readOnly
                                value={opsi.program.find((item) => item.id === lembagaTerpilih?.program_id)?.nama ?? '-'}
                                className="form-control bg-surface-muted"
                                {...fieldAria('program_lembaga', errors.program_id, 'Mengikuti program lembaga.')}
                            />
                        </Field>
                    </>
                ) : (
                    <>
                        <Field label="Program" htmlFor="program_id" error={errors.program_id} required>
                            <select
                                id="program_id"
                                value={data.program_id}
                                onChange={(event) => setData((sebelum) => ({ ...sebelum, program_id: event.target.value, kelas_pengajaran_id: '' }))}
                                className="form-control"
                                {...fieldAria('program_id', errors.program_id)}
                            >
                                <option value="">— Pilih program —</option>
                                {programTersedia.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.nama}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field as="fieldset" label="Jenis kelas" htmlFor="kelas_type_id" error={errors.kelas_type_id} required>
                            <Segmented
                                name="kelas_type_id"
                                value={data.kelas_type_id}
                                onChange={(nilai) => setData((sebelum) => ({ ...sebelum, kelas_type_id: nilai, kelas_pengajaran_id: '' }))}
                                options={jenisIndividu.map((item) => ({ value: item.id, label: item.nama }))}
                            />
                        </Field>
                    </>
                )}

                <Field
                    label="Kelas"
                    htmlFor="kelas_pengajaran_id"
                    error={errors.kelas_pengajaran_id}
                    hint={hintKelas}
                    optional
                >
                    <select
                        id="kelas_pengajaran_id"
                        value={data.kelas_pengajaran_id}
                        onChange={(event) => setData('kelas_pengajaran_id', event.target.value)}
                        className="form-control"
                        {...fieldAria('kelas_pengajaran_id', errors.kelas_pengajaran_id, hintKelas)}
                    >
                        <option value="">Belum ditempatkan</option>
                        {kelasTersedia.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.nama}
                                {!item.aktif ? ' (nonaktif)' : ''}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field label="Pengajar" htmlFor="pengajar" hint="Otomatis mengikuti pengajar kelas.">
                    <input id="pengajar" readOnly value={kelasTerpilih?.pengajar ?? '-'} className="form-control bg-surface-muted" aria-describedby="pengajar-hint" />
                </Field>

                <Field as="fieldset" label="Metode PBM" htmlFor="default_pbm" error={errors.default_pbm} required className="sm:col-span-2">
                    <Segmented name="default_pbm" value={data.default_pbm} onChange={(nilai) => setData('default_pbm', nilai)} options={PBM.map((item) => ({ value: item, label: item }))} />
                </Field>

                {pindahKelas && (
                    <Field label="Alasan pindah kelas" htmlFor="alasan" error={errors.alasan} optional className="sm:col-span-2">
                        <input
                            id="alasan"
                            value={data.alasan}
                            maxLength={500}
                            onChange={(event) => setData('alasan', event.target.value)}
                            placeholder="Mis. naik jilid, permintaan orang tua"
                            className="form-control"
                            {...fieldAria('alasan', errors.alasan)}
                        />
                    </Field>
                )}
            </form>
        </Modal>
    );
}
