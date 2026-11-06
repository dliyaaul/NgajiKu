import { Link, usePage } from "@inertiajs/react";
import {
    Calendar,
    ChevronDown,
    ChevronRight,
    LogOut,
    Moon,
    Sun,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";

import { Badge } from "@/components/ui/badge";
import { Icon } from "@/components/ui/icon";
import { Ornament } from "@/components/ui/ornament";
import { UserAvatar } from "@/components/user-avatar";
import { useTheme } from "@/hooks/use-preferences";
import { dashboardUrl } from "@/lib/navigation";
import { cn } from "@/lib/utils";
import { logout } from "@/routes";
import type { BreadcrumbItem } from "@/types";

export function AppHeader({
    title,
    breadcrumbs,
}: {
    title: string;
    breadcrumbs: BreadcrumbItem[];
}) {
    const { auth, tanggal } = usePage().props;
    const { theme, toggle } = useTheme();

    return (
        <header className="sticky top-0 z-20 border-b border-line bg-surface/80 backdrop-blur-xl">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:h-[4.5rem] lg:px-10">
                {/* Kiri: logo (ponsel/tablet) */}
                <Link
                    href={dashboardUrl(auth?.role)}
                    className="flex items-center gap-2.5 rounded-xl lg:hidden"
                >
                    <span className="grid size-9 place-items-center rounded-xl bg-brand-900 p-1.5 shadow-sm">
                        <span className="grid size-full place-items-center rounded-lg bg-white p-0.5">
                            <img
                                src="/assets/images/logo/Ngajiku_Logo.png"
                                alt=""
                                width={22}
                                height={22}
                                className="size-full object-contain"
                            />
                        </span>
                    </span>
                    <span className="text-base font-bold tracking-tight text-fg">
                        NgajiKu
                    </span>
                </Link>

                {/* Kiri: konteks halaman (desktop) */}
                <div className="hidden min-w-0 lg:block">
                    {breadcrumbs.length > 0 ? (
                        <Breadcrumbs items={breadcrumbs} />
                    ) : (
                        <p className="truncate text-sm font-semibold text-fg">
                            {title}
                        </p>
                    )}
                </div>

                {/* Kanan */}
                <div className="flex items-center gap-1 sm:gap-2">
                    {/* Tanggal Masehi & Hijriah */}
                    <div className="mr-1 hidden items-center gap-2.5 rounded-xl border border-line bg-surface-muted/60 py-1.5 pr-3.5 pl-2 xl:flex">
                        <span className="grid size-7 place-items-center rounded-lg bg-surface text-brand-600 shadow-xs ring-1 ring-line dark:text-brand-300">
                            <Icon icon={Calendar} className="size-4" />
                        </span>
                        <span className="leading-tight">
                            <time
                                dateTime={tanggal.iso}
                                className="block text-xs font-semibold text-fg"
                            >
                                {tanggal.masehi}
                            </time>
                            {tanggal.hijriah && (
                                <span className="block text-[0.6875rem] text-fg-muted">
                                    {tanggal.hijriah}
                                </span>
                            )}
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={toggle}
                        aria-label={
                            theme === "dark"
                                ? "Ganti ke mode terang"
                                : "Ganti ke mode gelap"
                        }
                        title="Ganti tema"
                        className="grid size-11 place-items-center rounded-xl text-fg-muted transition-colors hover:bg-surface-muted hover:text-fg sm:size-10"
                    >
                        {/* Ikon dipilih lewat varian dark: agar langsung benar sejak dimuat */}
                        <Icon icon={Moon} className="size-5 dark:hidden" />
                        <Icon icon={Sun} className="hidden size-5 dark:block" />
                    </button>

                    <span
                        className="hidden h-6 w-px bg-line sm:block"
                        aria-hidden="true"
                    />

                    <AccountMenu />
                </div>
            </div>
        </header>
    );
}

function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex min-w-0 items-center gap-1.5 text-sm text-fg-muted">
                {items.map((item, index) => (
                    <li
                        key={`${item.label}-${index}`}
                        className="flex min-w-0 items-center gap-1.5"
                    >
                        {item.href ? (
                            <Link
                                href={item.href}
                                className="truncate rounded-md hover:text-fg hover:underline"
                            >
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                className="truncate font-semibold text-fg"
                                aria-current="page"
                            >
                                {item.label}
                            </span>
                        )}

                        {index < items.length - 1 && (
                            <Icon
                                icon={ChevronRight}
                                className="size-4 text-fg-subtle"
                            />
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

function AccountMenu() {
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: PointerEvent) => {
            if (!root.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                setOpen(false);
                trigger.current?.focus();
            }
        };

        document.addEventListener("pointerdown", onPointerDown);
        document.addEventListener("keydown", onKeyDown);

        return () => {
            document.removeEventListener("pointerdown", onPointerDown);
            document.removeEventListener("keydown", onKeyDown);
        };
    }, [open]);

    if (!auth) {
        return null;
    }

    return (
        <div ref={root} className="relative">
            <button
                ref={trigger}
                type="button"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                aria-haspopup="true"
                aria-controls="menu-akun"
                className="flex h-11 items-center gap-2.5 rounded-xl py-1 pr-2 pl-1 transition-colors hover:bg-surface-muted sm:h-10"
            >
                <UserAvatar
                    user={auth}
                    className="size-8 bg-brand-500 font-semibold text-white"
                />

                {/* Di desktop nama sudah tampil di sidebar */}
                <span className="hidden text-left sm:block lg:hidden">
                    <span className="block max-w-40 truncate text-sm leading-tight font-semibold text-fg">
                        {auth.username}
                    </span>
                    <span className="block text-xs leading-tight text-fg-muted capitalize">
                        {auth.role}
                    </span>
                </span>
                <span className="sr-only">Menu akun {auth.username}</span>

                <Icon
                    icon={ChevronDown}
                    className={cn(
                        "size-4 text-fg-subtle transition-transform",
                        open && "rotate-180",
                    )}
                />
            </button>

            {open && (
                <div
                    id="menu-akun"
                    className="absolute right-0 mt-2 w-72 animate-pop-in overflow-hidden rounded-2xl border border-line bg-surface shadow-xl"
                >
                    <div className="on-brand relative overflow-hidden bg-brand-900 px-4 py-4 text-white">
                        <Ornament
                            type="pattern"
                            className="absolute inset-0 size-full text-white opacity-[0.08]"
                        />

                        <div className="relative flex items-center gap-3">
                            <UserAvatar
                                user={auth}
                                className="size-10 bg-gold-300 text-brand-900 ring-2 ring-white/20"
                            />

                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold">
                                    {auth.username}
                                </p>
                                {auth.email && (
                                    <p className="truncate text-xs text-brand-100/80">
                                        {auth.email}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="p-2">
                        <p className="flex items-center justify-between px-3 py-2 text-sm text-fg-muted">
                            Peran
                            <Badge tone="brand" className="capitalize">
                                {auth.role}
                            </Badge>
                        </p>

                        <div className="my-1 h-px bg-line" role="separator" />

                        <Link
                            href={logout.url()}
                            method="post"
                            as="button"
                            className="flex h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-error-700 transition-colors hover:bg-error-50 dark:text-error-300 dark:hover:bg-error-400/10"
                        >
                            <Icon icon={LogOut} className="size-5" />
                            Keluar
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
