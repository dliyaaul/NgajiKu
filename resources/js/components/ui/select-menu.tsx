import { Check, ChevronDown, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';

import { Icon } from '@/components/ui/icon';
import { TONE_DOT, type Tone } from '@/lib/program';
import { cn } from '@/lib/utils';

export interface SelectOption {
    value: string;
    label: string;
    meta?: string;
    tone?: Tone;
}

/** Warna pemicu varian status sesuai tone opsi terpilih. */
const TONE_TRIGGER: Partial<Record<Tone, string>> = {
    success: 'border-success-300 bg-success-50 text-success-800 dark:border-success-400/40 dark:bg-success-400/10 dark:text-success-200',
    info: 'border-indigo-300 bg-indigo-50 text-indigo-800 dark:border-indigo-400/40 dark:bg-indigo-400/10 dark:text-indigo-200',
    warning: 'border-warning-300 bg-warning-50 text-warning-800 dark:border-warning-400/40 dark:bg-warning-400/10 dark:text-warning-200',
    danger: 'border-error-300 bg-error-50 text-error-800 dark:border-error-400/40 dark:bg-error-400/10 dark:text-error-200',
    neutral: 'border-line-strong bg-surface text-fg-muted',
};

/** Huruf kecil tanpa tanda baca: "al fatihah" cocok dengan "Al-Fatihah". */
const normalize = (text: string) => text.toLowerCase().replace(/[^a-z0-9]+/g, '');

interface SelectMenuProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: SelectOption[];
    searchable?: boolean;
    /** status: pemicu berwarna sesuai tone opsi terpilih. */
    variant?: 'default' | 'status';
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    invalid?: boolean;
    describedBy?: string;
    /** id label terlihat; nama aksesibel = label + nilai terpilih. */
    labelledBy?: string;
}

/**
 * Dropdown pilihan tunggal dengan pencarian opsional. Pola ARIA: tombol pemicu + listbox;
 * fokus berada di kotak cari (searchable) atau di listbox (aria-activedescendant).
 * Panel hanya dirender saat terbuka (daftar surat berisi 114 opsi per santri).
 */
export function SelectMenu({
    id,
    value,
    onChange,
    options,
    searchable = false,
    variant = 'default',
    placeholder = 'Pilih…',
    searchPlaceholder = 'Cari…',
    emptyText = 'Tidak ada yang cocok.',
    disabled = false,
    invalid = false,
    describedBy,
    labelledBy,
}: SelectMenuProps) {
    const [open, setOpen] = useState(false);
    const [dropUp, setDropUp] = useState(false);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);

    const root = useRef<HTMLDivElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    const search = useRef<HTMLInputElement>(null);
    const listbox = useRef<HTMLUListElement>(null);

    const listboxId = `${id}-listbox`;
    const isStatus = variant === 'status';
    const selected = options.find((option) => option.value === value) ?? null;

    const filtered = useMemo(() => {
        const needle = normalize(query);

        return needle ? options.filter((option) => normalize(`${option.label} ${option.value}`).includes(needle)) : options;
    }, [options, query]);

    const optionId = (index: number) => `${id}-opsi-${index}`;

    const close = (returnFocus = false) => {
        setOpen(false);

        if (returnFocus) {
            trigger.current?.focus();
        }
    };

    const show = () => {
        if (disabled || !trigger.current) {
            return;
        }

        const rect = trigger.current.getBoundingClientRect();
        const ruangBawah = window.innerHeight - rect.bottom;
        setDropUp(ruangBawah < 340 && rect.top > ruangBawah);

        setQuery('');
        setActive(Math.max(0, options.findIndex((option) => option.value === value)));
        setOpen(true);
    };

    const choose = (option: SelectOption | undefined) => {
        if (!option) {
            return;
        }

        if (option.value !== value) {
            onChange(option.value);
        }

        close(true);
    };

    const move = (step: number) => {
        if (filtered.length > 0) {
            setActive((current) => (current + step + filtered.length) % filtered.length);
        }
    };

    // Fokus ke kotak cari / listbox saat panel terbuka.
    useEffect(() => {
        if (open) {
            (searchable ? search.current : listbox.current)?.focus();
        }
    }, [open, searchable]);

    // Jaga opsi aktif tetap terlihat.
    useEffect(() => {
        if (open) {
            document.getElementById(optionId(active))?.scrollIntoView({ block: 'nearest' });
        }
    }, [open, active]);

    // Tutup saat klik di luar.
    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: PointerEvent) => {
            if (!root.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('pointerdown', onPointerDown);

        return () => document.removeEventListener('pointerdown', onPointerDown);
    }, [open]);

    const onPanelKeyDown = (event: KeyboardEvent) => {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                move(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                move(-1);
                break;
            case 'Home':
                event.preventDefault();
                setActive(0);
                break;
            case 'End':
                event.preventDefault();
                setActive(Math.max(0, filtered.length - 1));
                break;
            case 'Enter':
                event.preventDefault();
                choose(filtered[active]);
                break;
            case 'Escape':
                event.preventDefault();
                event.stopPropagation();
                close(true);
                break;
            case 'Tab':
                close();
                break;
        }
    };

    return (
        <div ref={root} className="relative">
            <button
                ref={trigger}
                type="button"
                id={id}
                onClick={() => (open ? close(true) : show())}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        show();
                    }
                }}
                disabled={disabled}
                aria-haspopup="listbox"
                aria-controls={listboxId}
                aria-expanded={open}
                aria-labelledby={labelledBy ? `${labelledBy} ${id}-nilai` : undefined}
                aria-invalid={invalid || undefined}
                aria-describedby={describedBy}
                className={cn(
                    'flex h-11 w-full items-center gap-2.5 rounded-xl border px-3.5 text-left shadow-xs outline-none transition-[border-color,box-shadow,background-color] duration-150 focus-visible:ring-4 focus-visible:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 aria-expanded:ring-4 aria-expanded:ring-brand-500/15 aria-invalid:border-error-600 sm:h-10 dark:focus-visible:ring-brand-400/25 dark:aria-invalid:border-error-400',
                    isStatus
                        ? cn('text-sm font-semibold', TONE_TRIGGER[selected?.tone ?? 'neutral'])
                        : 'border-line-strong bg-surface text-base text-fg focus-visible:border-brand-500 aria-expanded:border-brand-500 sm:text-sm dark:focus-visible:border-brand-400 dark:aria-expanded:border-brand-400',
                )}
            >
                {isStatus && (
                    <span
                        className={cn('size-2.5 shrink-0 rounded-full ring-2 ring-white/60 dark:ring-black/20', TONE_DOT[selected?.tone ?? 'neutral'])}
                        aria-hidden="true"
                    />
                )}

                <span id={`${id}-nilai`} className={cn('min-w-0 flex-1 truncate', !selected && 'font-normal text-fg-subtle')}>
                    {selected ? selected.label : placeholder}
                </span>

                <Icon icon={ChevronDown} className={cn('size-4 shrink-0 opacity-60 transition-transform duration-150', open && 'rotate-180')} />
            </button>

            {open && (
                <div
                    onKeyDown={onPanelKeyDown}
                    className={cn(
                        'absolute inset-x-0 z-40 animate-pop-in overflow-hidden rounded-xl border border-line bg-surface shadow-xl shadow-gray-950/10 dark:shadow-black/40',
                        dropUp ? 'bottom-full mb-1.5 origin-bottom' : 'top-full mt-1.5 origin-top',
                    )}
                >
                    {searchable && (
                        <div className="border-b border-line p-2">
                            <div className="relative">
                                <Icon icon={Search} className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-fg-subtle" />
                                <input
                                    ref={search}
                                    type="text"
                                    value={query}
                                    onChange={(event) => {
                                        setQuery(event.target.value);
                                        setActive(0);
                                    }}
                                    autoComplete="off"
                                    spellCheck={false}
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="true"
                                    aria-controls={listboxId}
                                    aria-activedescendant={filtered.length ? optionId(active) : undefined}
                                    aria-label={searchPlaceholder}
                                    placeholder={searchPlaceholder}
                                    className="h-10 w-full rounded-lg border-0 bg-surface-muted pr-3 pl-9 text-base text-fg outline-none placeholder:text-fg-subtle focus:ring-2 focus:ring-brand-500/30 sm:text-sm"
                                />
                            </div>
                        </div>
                    )}

                    <ul
                        ref={listbox}
                        id={listboxId}
                        role="listbox"
                        tabIndex={-1}
                        aria-labelledby={labelledBy}
                        aria-activedescendant={!searchable && filtered.length ? optionId(active) : undefined}
                        className="max-h-64 overflow-y-auto overscroll-contain p-1.5 outline-none"
                    >
                        {filtered.map((option, index) => (
                            <li
                                key={option.value}
                                id={optionId(index)}
                                role="option"
                                aria-selected={option.value === value}
                                onClick={() => choose(option)}
                                onMouseMove={() => setActive(index)}
                                className={cn(
                                    'flex min-h-10 cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-fg select-none',
                                    index === active && 'bg-surface-muted',
                                )}
                            >
                                {isStatus && <span className={cn('size-2.5 shrink-0 rounded-full', TONE_DOT[option.tone ?? 'neutral'])} aria-hidden="true" />}
                                <span className={cn('min-w-0 flex-1 truncate', option.value === value && 'font-semibold')}>{option.label}</span>
                                {option.meta && <span className="shrink-0 text-xs text-fg-muted tabular-nums">{option.meta}</span>}
                                {option.value === value && <Icon icon={Check} className="size-4 text-brand-600 dark:text-brand-300" />}
                            </li>
                        ))}

                        {filtered.length === 0 && (
                            <li role="presentation" className="px-3 py-6 text-center text-sm text-fg-muted">
                                {emptyText}
                            </li>
                        )}
                    </ul>
                </div>
            )}
        </div>
    );
}
