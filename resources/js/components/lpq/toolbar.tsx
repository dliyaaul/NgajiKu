import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { queryBersih } from '@/lib/lpq';
import { cn } from '@/lib/utils';

export interface ToolbarSelect {
    name: string;
    label: string;
    /** Teks opsi kosong, mis. "Semua program". */
    semua: string;
    options: Array<{ value: string; label: string }>;
}

/**
 * Baris pencarian + filter dropdown. Setiap perubahan memuat ulang daftar lewat query string
 * (state halaman dipertahankan agar fokus & isian tidak hilang).
 */
export function Toolbar({
    url,
    filter,
    placeholder = 'Cari…',
    selects = [],
    children,
}: {
    url: (query: Record<string, string>) => string;
    filter: Record<string, string>;
    placeholder?: string;
    selects?: ToolbarSelect[];
    children?: ReactNode;
}) {
    const [cari, setCari] = useState(filter.cari ?? '');

    const muat = (perubahan: Record<string, string>) => {
        router.get(url(queryBersih({ ...filter, ...perubahan })), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        muat({ cari: cari.trim() });
    };

    const adaFilter = Object.entries(filter).some(([, nilai]) => Boolean(nilai));

    return (
        <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <form role="search" onSubmit={submit} className="relative min-w-0 sm:max-w-sm sm:flex-1">
                <label htmlFor="cari" className="sr-only">
                    {placeholder}
                </label>
                <Icon icon={Search} className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-fg-subtle" />
                <input
                    id="cari"
                    type="search"
                    value={cari}
                    onChange={(event) => {
                        setCari(event.target.value);

                        // Tombol "x" bawaan kotak cari langsung menampilkan semua data lagi.
                        if (event.target.value === '' && filter.cari) {
                            muat({ cari: '' });
                        }
                    }}
                    placeholder={placeholder}
                    className="form-control pl-10"
                />
            </form>

            {selects.map((select) => (
                <div key={select.name} className="sm:w-48">
                    <label htmlFor={`filter-${select.name}`} className="sr-only">
                        {select.label}
                    </label>
                    <select
                        id={`filter-${select.name}`}
                        value={filter[select.name] ?? ''}
                        onChange={(event) => muat({ [select.name]: event.target.value })}
                        className={cn('form-control', filter[select.name] && 'border-brand-500 text-fg')}
                    >
                        <option value="">{select.semua}</option>
                        {select.options.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            ))}

            {adaFilter && (
                <button
                    type="button"
                    onClick={() => {
                        setCari('');
                        router.get(url({}), {}, { preserveScroll: true, replace: true });
                    }}
                    className="inline-flex h-10 items-center gap-1.5 self-start rounded-xl px-3 text-sm font-medium text-fg-muted hover:bg-surface-muted hover:text-fg sm:self-auto"
                >
                    <Icon icon={X} className="size-4" />
                    Reset
                </button>
            )}

            {children && <div className="flex flex-wrap gap-2 sm:ml-auto">{children}</div>}
        </div>
    );
}
