import { router } from '@inertiajs/react';
import { Trash2, TriangleAlert } from 'lucide-react';
import { useId, useRef, useState, type ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { Icon } from '@/components/ui/icon';

/**
 * Konfirmasi tindakan berisiko memakai <dialog> bawaan browser
 * (fokus terkunci di dalam dialog, Esc menutup, latar diredupkan).
 * `trigger` menerima fungsi pembuka dialog.
 */
export function ConfirmDialog({
    title,
    url,
    method = 'delete',
    confirmLabel = 'Ya, hapus',
    cancelLabel = 'Batal',
    trigger,
    children,
}: {
    title: string;
    url: string;
    method?: 'delete' | 'post' | 'put';
    confirmLabel?: string;
    cancelLabel?: string;
    trigger: (open: () => void) => ReactNode;
    children: ReactNode;
}) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [processing, setProcessing] = useState(false);
    const id = useId();

    const confirm = () => {
        router.visit(url, {
            method,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                dialog.current?.close();
            },
        });
    };

    return (
        <>
            {trigger(() => dialog.current?.showModal())}

            <dialog
                ref={dialog}
                aria-labelledby={`${id}-judul`}
                aria-describedby={`${id}-isi`}
                className="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-line bg-surface p-0 text-fg shadow-2xl open:animate-pop-in"
            >
                <div className="p-6">
                    <div className="flex items-start gap-4">
                        <span className="grid size-10 shrink-0 place-items-center rounded-full bg-error-50 text-error-600 dark:bg-error-400/10 dark:text-error-300">
                            <Icon icon={TriangleAlert} className="size-5" />
                        </span>

                        <div className="min-w-0">
                            <h2 id={`${id}-judul`} className="text-base font-semibold text-fg">
                                {title}
                            </h2>
                            <div id={`${id}-isi`} className="mt-1 text-sm text-fg-muted">
                                {children}
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button variant="secondary" autoFocus onClick={() => dialog.current?.close()}>
                            {cancelLabel}
                        </Button>
                        <Button variant="danger" icon={Trash2} disabled={processing} onClick={confirm}>
                            {confirmLabel}
                        </Button>
                    </div>
                </div>
            </dialog>
        </>
    );
}
