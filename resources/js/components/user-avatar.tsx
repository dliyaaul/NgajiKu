import { cn } from '@/lib/utils';
import type { AuthUser } from '@/types';

/** Foto profil, atau huruf awal nama bila tidak ada foto. Ukuran & warna lewat className. */
export function UserAvatar({ user, className }: { user: AuthUser; className?: string }) {
    if (user.avatar) {
        return <img src={user.avatar} alt="" className={cn('shrink-0 rounded-full object-cover', className)} />;
    }

    return (
        <span className={cn('grid shrink-0 place-items-center rounded-full text-sm font-bold', className)} aria-hidden="true">
            {user.username.charAt(0).toUpperCase()}
        </span>
    );
}
