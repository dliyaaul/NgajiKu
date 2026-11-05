import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import type { Pagination } from '@/types';

/** Navigasi halaman sebelumnya/berikutnya untuk daftar berhalaman. */
export function Pager({ pagination }: { pagination: Pagination }) {
    if (pagination.total_halaman <= 1) {
        return null;
    }

    return (
        <nav aria-label="Halaman" className="mt-5 flex items-center justify-between gap-3">
            <p className="text-sm text-fg-muted tabular-nums">
                Halaman {pagination.halaman} dari {pagination.total_halaman}
            </p>

            <div className="flex gap-2">
                {pagination.sebelumnya ? (
                    <Button variant="secondary" size="sm" icon={ChevronLeft} href={pagination.sebelumnya}>
                        Sebelumnya
                    </Button>
                ) : (
                    <Button variant="secondary" size="sm" icon={ChevronLeft} disabled>
                        Sebelumnya
                    </Button>
                )}
                {pagination.berikutnya ? (
                    <Button variant="secondary" size="sm" iconRight={ChevronRight} href={pagination.berikutnya}>
                        Berikutnya
                    </Button>
                ) : (
                    <Button variant="secondary" size="sm" iconRight={ChevronRight} disabled>
                        Berikutnya
                    </Button>
                )}
            </div>
        </nav>
    );
}
