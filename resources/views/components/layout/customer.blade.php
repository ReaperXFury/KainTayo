<x-layout.app>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">

        <!-- Navbar -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-amber-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

                <a href="{{ route('customer.home') }}" wire:navigate
                   class="flex items-center gap-2.5 text-amber-600 font-bold text-xl tracking-tight">
                    <div class="bg-amber-500 text-white p-2 rounded-xl shadow-md shadow-amber-200">
                        <i data-lucide="utensils-crossed" class="w-5 h-5"></i>
                    </div>
                    <span>Karin<span class="text-amber-800">Derya</span></span>
                </a>

                <nav class="hidden sm:flex items-center gap-1">
                    <a href="{{ route('customer.home') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium
                              {{ request()->routeIs('customer.home') ? 'text-amber-600 bg-amber-50' : 'text-slate-600 hover:text-amber-600 hover:bg-amber-50' }}">
                        <i data-lucide="utensils" class="w-4 h-4"></i> Menu
                    </a>
                    <a href="#"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-amber-600 hover:bg-amber-50">
                        <i data-lucide="receipt" class="w-4 h-4"></i> My Orders
                    </a>
                </nav>

                <div class="flex items-center gap-1 sm:gap-3">
                    <a href="#" aria-label="My Orders"
                       class="sm:hidden p-2.5 rounded-xl text-slate-600 hover:text-amber-600 hover:bg-amber-50">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </a>

                    <span class="hidden md:inline text-sm text-slate-500">{{ auth()->user()->name }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Log Out"
                            class="inline-flex items-center gap-1.5 text-slate-600 hover:text-rose-600 font-medium text-sm p-2.5 sm:px-3 sm:py-2 rounded-lg hover:bg-rose-50 transition-colors">
                            <i data-lucide="log-out" class="w-5 h-5 sm:w-4 sm:h-4"></i>
                            <span class="hidden sm:inline">Log Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8">
            {{ $slot }}
        </main>

        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} KarinDerya. All rights reserved.
        </footer>
    </div>
</x-layout.app>