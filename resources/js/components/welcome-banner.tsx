import { usePage } from '@inertiajs/react';
import { Calendar } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';

import { Flash } from '@/components/flash';
import { Icon } from '@/components/ui/icon';
import { Ornament } from '@/components/ui/ornament';

/** "Al-Qur’an" jangan terpotong di tanda hubung. */
function jagaAlQuran(text: string): ReactNode {
    return text.split('Al-Qur’an').map((bagian, index, semua) => (
        <Fragment key={index}>
            {bagian}
            {index < semua.length - 1 && <span className="whitespace-nowrap">Al-Qur’an</span>}
        </Fragment>
    ));
}

/**
 * Banner sambutan dashboard bernuansa panel login: gradasi emerald, pola bintang, medali emas.
 * Gunakan tombol variant inverse / inverse-ghost di `actions`.
 */
export function WelcomeBanner({ title, subtitle, actions }: { title: string; subtitle?: string; actions?: ReactNode }) {
    const { tanggal } = usePage().props;

    return (
        <>
            <section className="on-brand relative isolate mb-6 overflow-hidden rounded-3xl bg-brand-800 px-6 py-7 text-white shadow-lg shadow-brand-900/15 sm:px-8 sm:py-9 lg:mb-8">
                {/* Latar dekoratif */}
                <div className="absolute inset-0 -z-10 bg-linear-to-br from-brand-600 via-brand-800 to-brand-950" aria-hidden="true" />
                <Ornament
                    type="pattern"
                    className="absolute inset-0 -z-10 size-full text-white opacity-[0.09] mask-[radial-gradient(ellipse_at_top_right,black_10%,transparent_70%)]"
                />
                <Ornament type="medallion" className="absolute -right-24 -bottom-32 -z-10 size-96 text-gold-200 opacity-25 sm:-right-16" />
                <div className="absolute -top-20 -left-10 -z-10 size-72 rounded-full bg-brand-400/15 blur-3xl" aria-hidden="true" />

                <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-2xl">
                        {/* Di layar xl tanggal sudah tampil di header, jadi tidak diulang */}
                        <p className="mb-4 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm font-medium text-brand-100/90 xl:hidden">
                            <Icon icon={Calendar} className="size-4 text-gold-200" />
                            <time dateTime={tanggal.iso}>{tanggal.masehi}</time>
                            {tanggal.hijriah && (
                                <>
                                    <span className="hidden text-gold-200/70 sm:inline" aria-hidden="true">
                                        •
                                    </span>
                                    <span className="basis-full pl-6 sm:basis-auto sm:pl-0">{tanggal.hijriah}</span>
                                </>
                            )}
                        </p>

                        <Ornament type="divider" className="text-gold-300/80" />

                        <h1 className="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">{title}</h1>

                        {subtitle && <p className="mt-2 text-sm leading-relaxed text-brand-50/85 sm:text-base">{jagaAlQuran(subtitle)}</p>}
                    </div>

                    {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
                </div>
            </section>

            <Flash />
        </>
    );
}
