import { cn } from '@/lib/utils';

export interface SegmentedOption {
    value: string;
    label: string;
    hint?: string;
    disabled?: boolean;
}

/**
 * Pilihan tunggal berbentuk tombol bersebelahan (radio asli, tersembunyi secara visual).
 * Bungkus dengan <Field as="fieldset"> agar memiliki legend.
 */
export function Segmented({
    name,
    value,
    onChange,
    options,
}: {
    name: string;
    value: string;
    onChange: (value: string) => void;
    options: SegmentedOption[];
}) {
    return (
        <div className={cn('grid grid-cols-1 gap-2', options.length === 2 && 'grid-cols-2', options.length >= 3 && 'sm:grid-cols-3')}>
            {options.map((option) => {
                const dipilih = value === option.value;

                return (
                    <label
                        key={option.value}
                        className={cn(
                            'flex min-h-11 cursor-pointer flex-col justify-center rounded-xl border px-3.5 py-2 text-sm transition-colors sm:min-h-10',
                            'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 has-[:focus-visible]:ring-offset-2 has-[:focus-visible]:ring-offset-surface',
                            option.disabled && 'cursor-not-allowed opacity-50',
                            dipilih
                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-400/10 dark:text-brand-200'
                                : 'border-line-strong text-fg hover:bg-surface-muted',
                        )}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={option.value}
                            checked={dipilih}
                            disabled={option.disabled}
                            onChange={() => onChange(option.value)}
                            className="sr-only"
                        />
                        <span className="font-medium">{option.label}</span>
                        {option.hint && <span className={cn('text-xs', dipilih ? 'text-brand-600/80 dark:text-brand-300/80' : 'text-fg-muted')}>{option.hint}</span>}
                    </label>
                );
            })}
        </div>
    );
}
