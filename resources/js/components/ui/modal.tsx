import { X } from 'lucide-react';
import { useEffect, useId, useRef, type ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

/**
 * Jendela formulir memakai <dialog> bawaan browser (fokus terkunci, Esc menutup).
 * Dikendalikan lewat `open`; `onClose` dipanggil saat ditutup dengan cara apa pun.
 */
export function Modal({
    open,
    onClose,
    title,
    description,
    size = 'md',
    footer,
    children,
}: {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    size?: 'md' | 'lg';
    footer?: ReactNode;
    children: ReactNode;
}) {
    const dialog = useRef<HTMLDialogElement>(null);
    const id = useId();

    useEffect(() => {
        const element = dialog.current;

        if (!element) {
            return;
        }

        if (open && !element.open) {
            element.showModal();
        } else if (!open && element.open) {
            element.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialog}
            onClose={onClose}
            aria-labelledby={`${id}-judul`}
            className={cn(
                'm-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-hidden rounded-2xl border border-line bg-surface p-0 text-fg shadow-2xl open:flex open:animate-pop-in open:flex-col',
                size === 'md' ? 'max-w-lg' : 'max-w-2xl',
            )}
        >
            {open && (
                <>
                    <header className="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                        <div className="min-w-0">
                            <h2 id={`${id}-judul`} className="text-base font-semibold text-fg">
                                {title}
                            </h2>
                            {description && <p className="mt-0.5 text-sm text-fg-muted">{description}</p>}
                        </div>

                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Tutup"
                            className="-m-1.5 grid size-9 shrink-0 place-items-center rounded-lg text-fg-muted hover:bg-surface-muted hover:text-fg"
                        >
                            <Icon icon={X} className="size-[1.125rem]" />
                        </button>
                    </header>

                    <div className="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">{children}</div>

                    {footer && <footer className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end sm:px-6">{footer}</footer>}
                </>
            )}
        </dialog>
    );
}
