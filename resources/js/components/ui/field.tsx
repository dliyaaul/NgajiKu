import { CircleAlert } from 'lucide-react';
import type { ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

/**
 * Pembungkus isian: label, petunjuk, dan pesan error yang terhubung ke input.
 * Input di dalamnya sebaiknya memakai `fieldAria(id, error, hint)`.
 */
export function Field({
    label,
    htmlFor,
    error,
    hint,
    required = false,
    optional = false,
    as = 'div',
    className,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: string;
    required?: boolean;
    optional?: boolean;
    as?: 'div' | 'fieldset';
    className?: string;
    children: ReactNode;
}) {
    const labelContent = (
        <>
            {label}
            {required && (
                <>
                    {' '}
                    <span className="text-error-600 dark:text-error-400" aria-hidden="true">
                        *
                    </span>
                    <span className="sr-only">(wajib)</span>
                </>
            )}
            {!required && optional && <span className="font-normal text-fg-subtle"> (opsional)</span>}
        </>
    );

    const Wrapper = as;

    return (
        <Wrapper className={className}>
            {as === 'fieldset' ? (
                <legend className="mb-1.5 text-sm font-medium text-fg">{labelContent}</legend>
            ) : (
                <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-medium text-fg">
                    {labelContent}
                </label>
            )}

            {children}

            {hint && (
                <p id={`${htmlFor}-hint`} className="mt-1.5 text-xs text-fg-muted">
                    {hint}
                </p>
            )}

            {error && <FieldError id={`${htmlFor}-error`}>{error}</FieldError>}
        </Wrapper>
    );
}

export function FieldError({ id, className, children }: { id: string; className?: string; children: ReactNode }) {
    return (
        <p id={id} className={cn('mt-1.5 flex items-start gap-1 text-xs font-medium text-error-700 dark:text-error-300', className)}>
            <Icon icon={CircleAlert} className="mt-px size-3.5" />
            {children}
        </p>
    );
}

/** Atribut ARIA untuk input yang dibungkus Field. */
export function fieldAria(id: string, error?: string, hint?: string) {
    const describedBy = [hint && `${id}-hint`, error && `${id}-error`].filter(Boolean).join(' ');

    return {
        'aria-invalid': error ? true : undefined,
        'aria-describedby': describedBy || undefined,
    };
}
