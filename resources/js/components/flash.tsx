import { usePage } from '@inertiajs/react';

import { Alert } from '@/components/ui/alert';

/** Pesan sesi `success` / `error` setelah redirect. */
export function Flash() {
    const { flash } = usePage().props;

    return (
        <>
            {flash.success && (
                <Alert key={flash.success} tone="success" dismissible className="mb-5">
                    {flash.success}
                </Alert>
            )}
            {flash.error && (
                <Alert key={flash.error} tone="danger" dismissible className="mb-5">
                    {flash.error}
                </Alert>
            )}
        </>
    );
}
