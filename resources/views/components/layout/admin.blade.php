@php
    $links = [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Orders',    'icon' => 'clipboard-list',    'href' => route('admin.orders'),    'active' => request()->routeIs('admin.orders*')],
        ['label' => 'Menu',      'icon' => 'utensils',          'href' => route('admin.menu'),      'active' => request()->routeIs('admin.menu*')],
        ['label' => 'Customers', 'icon' => 'users',             'href' => route('admin.customers'), 'active' => request()->routeIs('admin.customers*')],
        ['label' => 'Reports',   'icon' => 'bar-chart-3',       'href' => route('admin.reports'),   'active' => request()->routeIs('admin.reports*')],
    ];
@endphp

<x-layout.app :title="$title ?? null">
    <div x-data="{
            open: false,
            collapsed: (() => { try { return localStorage.getItem('admin-sidebar-collapsed') === '1' } catch (e) { return false } })(),
         }"
         x-init="$watch('collapsed', value => { try { localStorage.setItem('admin-sidebar-collapsed', value ? '1' : '0') } catch (e) {} })"
         class="min-h-screen bg-slate-50 text-slate-800 lg:flex">

        <!-- Mobile overlay -->
        <div @click="open = false"
             :class="open ? 'block' : 'hidden'"
             class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="[open ? 'translate-x-0' : '-translate-x-full', collapsed ? 'lg:w-20' : 'lg:w-64']"
               class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 bg-slate-900 text-slate-300 flex flex-col transition-[width,transform] duration-200 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen">

            <!-- Collapse toggle (desktop only) -->
            <button type="button" @click="collapsed = !collapsed"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    class="hidden lg:flex absolute -right-3 top-5 z-10 w-6 h-6 items-center justify-center rounded-full bg-amber-500 text-white shadow-md hover:bg-amber-600 transition-colors">
                <span x-show="!collapsed"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                <span x-show="collapsed" style="display: none;"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
            </button>

            <!-- Brand -->
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800"
                 :class="collapsed && 'lg:justify-center lg:px-0'">
                <a href="/" class="flex items-center gap-2.5 text-amber-500 font-bold text-xl tracking-tight">
                    <div class="bg-amber-500 text-white p-2 rounded-xl shadow-md shadow-amber-500/20 shrink-0">
                        <i data-lucide="utensils-crossed" class="w-5 h-5"></i>
                    </div>
                    <span :class="collapsed && 'lg:hidden'">Karin<span class="text-white">Derya</span></span>
                </a>
                <button @click="open = false" class="lg:hidden text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5 space-y-1">
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500"
                   :class="collapsed && 'lg:hidden'">Management</p>
                <div class="hidden mb-3 border-t border-slate-800" :class="collapsed && 'lg:block'"></div>

                @foreach ($links as $link)
                    <a href="{{ $link['href'] }}" wire:navigate
                       title="{{ $link['label'] }}"
                       :class="collapsed && 'lg:justify-center lg:px-0'"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                              {{ $link['active']
                                    ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20'
                                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="{{ $link['icon'] }}" class="w-5 h-5 shrink-0"></i>
                        <span class="truncate" :class="collapsed && 'lg:hidden'">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <!-- User + logout -->
            <div class="p-4 border-t border-slate-800" :class="collapsed && 'lg:px-3'">
                <div class="flex items-center gap-3 mb-3" :class="collapsed && 'lg:justify-center'">
                    <div class="w-9 h-9 shrink-0 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm"
                         title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0" :class="collapsed && 'lg:hidden'">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">Administrator</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Log Out"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-slate-300 bg-slate-800 hover:bg-rose-500/10 hover:text-rose-400 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                        <span :class="collapsed && 'lg:hidden'">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Content area -->
        <div class="flex-1 min-w-0">

            <!-- Mobile top bar -->
            <div class="lg:hidden sticky top-0 z-30 h-14 px-4 flex items-center gap-3 bg-white/95 backdrop-blur-md border-b border-amber-100">
                <button @click="open = true" class="text-slate-600 hover:text-amber-600">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-amber-600">Karin<span class="text-amber-800">Derya</span></span>
            </div>

            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layout.app>