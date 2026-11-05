@php
    use App\Helpers\MenuHelper;

    $menuGroups = MenuHelper::getMenuGroups();
    $username = data_get(session('user_profile', []), 'username', 'Pengguna');
    $avatar = data_get(session('user_profile', []), 'avatar_url');
    $role = session('user_role', 'santri');
@endphp

{{-- Sidebar desktop: panel emerald seperti halaman login, tetap gelap di kedua tema --}}
<aside
    class="on-brand fixed inset-y-0 left-0 z-30 hidden w-68 flex-col overflow-hidden bg-brand-900 text-white transition-[width] duration-200 lg:flex sidebar-collapsed:w-20">

    {{-- Latar: gradasi, pola bintang yang memudar, dan cahaya lembut --}}
    <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-brand-700/70 via-brand-900 to-brand-950"
        aria-hidden="true"></div>
    <x-ornament type="pattern"
        class="absolute inset-0 size-full text-white opacity-[0.07] mask-[linear-gradient(to_bottom,black,transparent_55%)]" />
    <div class="pointer-events-none absolute -top-24 -left-16 size-64 rounded-full bg-brand-400/20 blur-3xl"
        aria-hidden="true"></div>

    {{-- Identitas --}}
    <div class="relative flex h-20 shrink-0 items-center px-5 sidebar-collapsed:justify-center sidebar-collapsed:px-0">
        <a href="{{ MenuHelper::dashboardPath() }}" class="flex min-w-0 items-center gap-3 rounded-xl">
            <span
                class="grid size-11 shrink-0 place-items-center rounded-xl bg-white p-1.5 shadow-lg shadow-brand-950/30">
                <img src="{{ asset('assets/images/logo/Ngajiku_Logo.png') }}" alt="" width="32"
                    height="32" class="size-full object-contain">
            </span>

            <span class="min-w-0 sidebar-collapsed:sr-only">
                <span class="block text-lg leading-tight font-bold tracking-tight">NgajiKu</span>
                <span class="block text-xs whitespace-nowrap text-brand-100/75">Lembaga Pendidikan Al-Qur'an</span>
            </span>
        </a>
    </div>

    <x-ornament type="divider"
        class="relative mx-5 justify-center text-gold-300/80 sidebar-collapsed:mx-auto sidebar-collapsed:[&>span:first-child]:hidden sidebar-collapsed:[&>span:last-child]:hidden" />

    {{-- Menu --}}
    <nav class="no-scrollbar relative flex-1 overflow-y-auto px-3 py-6" aria-label="Navigasi utama">
        @foreach ($menuGroups as $menuGroup)
            <p
                class="mb-2 px-3 text-[0.6875rem] font-semibold tracking-[0.12em] text-brand-100/60 uppercase sidebar-collapsed:sr-only">
                {{ $menuGroup['title'] }}
            </p>

            <ul class="flex flex-col gap-1">
                @foreach ($menuGroup['items'] as $item)
                    @php($active = MenuHelper::isActive($item['path']))

                    <li>
                        <a href="{{ $item['path'] }}" title="{{ $item['name'] }}"
                            @if ($active) aria-current="page" @endif @class([
                                'group relative flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors duration-150 sidebar-collapsed:justify-center sidebar-collapsed:px-0',
                                'bg-white/10 text-white shadow-sm ring-1 ring-white/10 ring-inset' => $active,
                                'text-brand-50/80 hover:bg-white/[0.06] hover:text-white' => !$active,
                            ])>
                            @if ($active)
                                <span class="absolute inset-y-2.5 left-0 w-1 rounded-r-full bg-gold-300"
                                    aria-hidden="true"></span>
                            @endif

                            <x-icon :name="$item['icon']" :class="$active ? 'size-5 text-gold-200' : 'size-5'" />
                            <span class="truncate sidebar-collapsed:sr-only">{{ $item['name'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    {{-- Profil & ciutkan --}}
    <div class="relative border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-xl px-2 py-2 sidebar-collapsed:flex-col sidebar-collapsed:px-0">
            @if ($avatar)
                <img src="{{ $avatar }}" alt="" width="36" height="36"
                    class="size-9 shrink-0 rounded-full object-cover ring-2 ring-white/20">
            @else
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-full bg-gold-300 text-sm font-bold text-brand-900"
                    aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($username, 0, 1)) }}
                </span>
            @endif

            <span class="min-w-0 flex-1 sidebar-collapsed:sr-only">
                <span class="block truncate text-sm font-semibold">{{ $username }}</span>
                <span class="block text-xs text-brand-100/75 capitalize">{{ $role }}</span>
            </span>

            <button type="button" @click="$store.sidebar.toggle()"
                :aria-label="$store.sidebar.collapsed ? 'Lebarkan menu' : 'Ciutkan menu'"
                :aria-pressed="$store.sidebar.collapsed.toString()" aria-label="Ciutkan menu"
                title="Ciutkan / lebarkan menu"
                class="grid size-9 shrink-0 place-items-center rounded-lg text-brand-100/80 transition-colors hover:bg-white/10 hover:text-white">
                <x-icon name="panel-left" class="size-[1.125rem]" />
            </button>
        </div>
    </div>
</aside>
