<x-layout.app>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between font-sans">
        
        <!-- Header / Navigation -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-amber-100 shadow-sm">
            <div class="max-w-7xl mx-m auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <!-- Brand / Logo -->
                <a href="/" class="flex items-center gap-2.5 text-amber-600 font-bold text-xl tracking-tight">
                    <div class="bg-amber-500 text-white p-2 rounded-xl shadow-md shadow-amber-200">
                        <i data-lucide="utensils-crossed" class="w-5 h-5"></i>
                    </div>
                    <span>Karin<span class="text-amber-800">Derya</span></span>
                </a>

                <!-- Auth Navigation -->
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('customer.home') }}"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                    {{ auth()->user()->role === 'admin' ? 'Dashboard' : 'My Orders' }}
                                </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="inline-flex items-center gap-1.5 text-slate-600 hover:text-amber-600 font-medium text-sm px-3 py-2 rounded-lg hover:bg-amber-50 transition-colors">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                Log In
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md shadow-amber-200">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                    Sign Up
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-gradient-to-b from-amber-500/10 via-amber-50/50 to-slate-50 py-16 lg:py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-12 items-center">
                        
                        <!-- Left Text Content -->
                        <div class="space-y-6 text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold tracking-wide">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-600"></i>
                                Freshly Cooked Daily Lutong Bahay
                            </div>

                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                                Order your favorite <span class="text-amber-600">Home-Cooked</span> meals online.
                            </h1>

                            <p class="text-slate-600 text-base sm:text-lg leading-relaxed max-w-xl mx-auto lg:mx-0">
                                Skip the queue! Browse today's lutong bahay menu, place your order in seconds, and track its preparation—all in one place.
                            </p>

                            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                                <a href="{{ Route::has('menu') ? route('menu') : '#menu-preview' }}" 
                                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3.5 rounded-xl font-semibold shadow-lg shadow-amber-200 transition-all hover:-translate-y-0.5">
                                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                                    View Today's Menu
                                </a>
                                <a href="{{ route('login') }}" 
                                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 px-6 py-3.5 rounded-xl font-semibold transition-all">
                                    <i data-lucide="user" class="w-5 h-5 text-slate-500"></i>
                                    Sign In to Order
                                </a>
                            </div>

                            <!-- Highlights Banner -->
                            <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-200/80">
                                <div class="text-center lg:text-left">
                                    <p class="text-2xl font-bold text-slate-800">100%</p>
                                    <p class="text-xs text-slate-500 font-medium">Fresh Daily</p>
                                </div>
                                <div class="text-center lg:text-left">
                                    <p class="text-2xl font-bold text-slate-800">Fast</p>
                                    <p class="text-xs text-slate-500 font-medium">Order Pickups</p>
                                </div>
                                <div class="text-center lg:text-left">
                                    <p class="text-2xl font-bold text-slate-800">Affordable</p>
                                    <p class="text-xs text-slate-500 font-medium">Student Friendly</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Card Banner -->
                        <div class="relative flex justify-center">
                            <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-xl border border-slate-100">
                                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                            <i data-lucide="soup" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-sm">Today's Special</h3>
                                            <p class="text-xs text-slate-400">Available now</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs font-medium rounded-full">In Stock</span>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🍲</span>
                                            <div>
                                                <p class="font-semibold text-slate-800 text-sm">Pork Adobo</p>
                                                <p class="text-xs text-slate-500">Served with rice</p>
                                            </div>
                                        </div>
                                        <span class="font-bold text-amber-600 text-sm">₱85.00</span>
                                    </div>

                                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🥘</span>
                                            <div>
                                                <p class="font-semibold text-slate-800 text-sm">Sinigang na Baboy</p>
                                                <p class="text-xs text-slate-500">Hot & sour soup</p>
                                            </div>
                                        </div>
                                        <span class="font-bold text-amber-600 text-sm">₱90.00</span>
                                    </div>

                                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🍛</span>
                                            <div>
                                                <p class="font-semibold text-slate-800 text-sm">Chicken Menudo</p>
                                                <p class="text-xs text-slate-500">Rich tomato sauce</p>
                                            </div>
                                        </div>
                                        <span class="font-bold text-amber-600 text-sm">₱75.00</span>
                                    </div>
                                </div>

                                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                                    <a href="{{ route('login') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700 inline-flex items-center gap-1">
                                        Sign in to see full menu <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Features Section (Customer & Admin Capabilities) -->
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-12">
                        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Built for both Customers & Staff</h2>
                        <p class="text-slate-500 mt-2 text-sm sm:text-base">A seamless experience for diners and a powerful management hub for karinderya owners.</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Customer Column -->
                        <div class="p-8 rounded-2xl bg-amber-50/50 border border-amber-100 space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-200">
                                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">For Customers</h3>
                            <ul class="space-y-3 text-slate-600 text-sm">
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                                    <span><strong>Interactive Menu:</strong> Browse daily available dishes with pricing and status.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                                    <span><strong>Quick Ordering:</strong> Add items to cart and place orders instantly.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                                    <span><strong>Real-Time Updates:</strong> Track order status from pending to ready for pickup.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Admin Column -->
                        <div class="p-8 rounded-2xl bg-slate-900 text-white space-y-4 shadow-lg">
                            <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                                <i data-lucide="shield-check" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">For Admin & Staff</h3>
                            <ul class="space-y-3 text-slate-300 text-sm">
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                                    <span><strong>Dashboard Analytics:</strong> View sales summaries, total orders, and top dishes.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                                    <span><strong>Menu Management:</strong> Add, edit, or set dishes out-of-stock easily.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                                    <span><strong>Order Fulfillment:</strong> Manage live queue and mark orders completed.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section class="py-16 bg-slate-50 border-t border-slate-200/60">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-12">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">How It Works</h2>
                        <p class="text-slate-500 mt-2 text-sm">Getting your meal is as easy as 1-2-3.</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 text-center space-y-3 shadow-sm">
                            <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-lg">
                                1
                            </div>
                            <h4 class="font-bold text-slate-800">Browse Menu</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Check out today's fresh lutong bahay items and daily specials.</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 text-center space-y-3 shadow-sm">
                            <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-lg">
                                2
                            </div>
                            <h4 class="font-bold text-slate-800">Place Order</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Add items to your cart, review your order, and confirm.</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 text-center space-y-3 shadow-sm">
                            <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-lg">
                                3
                            </div>
                            <h4 class="font-bold text-slate-800">Pick Up & Enjoy</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Get notified when your order is ready and enjoy your fresh meal!</p>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-8 text-slate-500 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="utensils-crossed" class="w-4 h-4 text-amber-500"></i>
                    <span class="font-semibold text-slate-700">KarinDerya Ordering System</span>
                </div>
                <p>&copy; {{ date('Y') }} KarinDerya. All rights reserved.</p>
            </div>
        </footer>

    </div>
</x-layout.app>