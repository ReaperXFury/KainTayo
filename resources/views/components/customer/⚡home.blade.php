<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout.customer'), Title('Menu - KarinDerya')] class extends Component
{
    public string $search = '';
    public string $category = 'All';

    /** @var array<int,int> dish id => quantity */
    public array $cart = [];

    // TODO: replace with Dish::all() once the dishes table exists.
    #[Computed]
    public function dishes(): array
    {
        return [
            ['id' => 1, 'name' => 'Pork Adobo',         'desc' => 'Soy-vinegar braised pork, served with rice', 'price' => 85, 'category' => 'Ulam',    'emoji' => '🍲', 'available' => true],
            ['id' => 2, 'name' => 'Sinigang na Baboy',  'desc' => 'Hot and sour tamarind pork soup',            'price' => 90, 'category' => 'Sabaw',   'emoji' => '🥘', 'available' => true],
            ['id' => 3, 'name' => 'Chicken Menudo',     'desc' => 'Chicken stew in rich tomato sauce',          'price' => 75, 'category' => 'Ulam',    'emoji' => '🍛', 'available' => true],
            ['id' => 4, 'name' => 'Pinakbet',           'desc' => 'Mixed vegetables with bagoong',              'price' => 60, 'category' => 'Gulay',   'emoji' => '🥬', 'available' => true],
            ['id' => 5, 'name' => 'Tinolang Manok',     'desc' => 'Ginger chicken soup with papaya',            'price' => 80, 'category' => 'Sabaw',   'emoji' => '🍜', 'available' => false],
            ['id' => 6, 'name' => 'Extra Rice',         'desc' => 'One cup of steamed rice',                    'price' => 15, 'category' => 'Add-ons', 'emoji' => '🍚', 'available' => true],
            ['id' => 7, 'name' => 'Fried Egg',          'desc' => 'Sunny-side up egg',                          'price' => 15, 'category' => 'Add-ons', 'emoji' => '🍳', 'available' => true],
            ['id' => 8, 'name' => 'Ginataang Kalabasa', 'desc' => 'Squash in coconut milk with shrimp',        'price' => 65, 'category' => 'Gulay',   'emoji' => '🥥', 'available' => true],
        ];
    }

    #[Computed]
    public function categories(): array
    {
        return array_merge(['All'], array_values(array_unique(array_column($this->dishes, 'category'))));
    }

    #[Computed]
    public function filteredDishes(): array
    {
        return array_values(array_filter($this->dishes, function ($dish) {
            $matchesCategory = $this->category === 'All' || $dish['category'] === $this->category;
            $matchesSearch   = $this->search === ''
                || str_contains(strtolower($dish['name']), strtolower($this->search));

            return $matchesCategory && $matchesSearch;
        }));
    }

    #[Computed]
    public function cartItems(): array
    {
        $dishes = collect($this->dishes)->keyBy('id');

        return collect($this->cart)
            ->filter(fn ($qty, $id) => $dishes->has($id))
            ->map(fn ($qty, $id) => [
                'id'       => $id,
                'name'     => $dishes[$id]['name'],
                'price'    => $dishes[$id]['price'],
                'qty'      => $qty,
                'subtotal' => $dishes[$id]['price'] * $qty,
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function cartTotal(): float
    {
        return array_sum(array_column($this->cartItems, 'subtotal'));
    }

    #[Computed]
    public function cartCount(): int
    {
        return array_sum($this->cart);
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function addToCart(int $id): void
    {
        $dish = collect($this->dishes)->firstWhere('id', $id);

        if (! $dish || ! $dish['available']) {
            return;
        }

        $this->cart[$id] = ($this->cart[$id] ?? 0) + 1;
    }

    public function decrease(int $id): void
    {
        if (! isset($this->cart[$id])) {
            return;
        }

        $this->cart[$id]--;

        if ($this->cart[$id] <= 0) {
            unset($this->cart[$id]);
        }
    }

    public function remove(int $id): void
    {
        unset($this->cart[$id]);
    }

    public function placeOrder(): void
    {
        if (empty($this->cart)) {
            return;
        }

        // TODO: create the Order and OrderItem records for auth()->id() here.

        $this->cart = [];

        session()->flash('success', 'Order placed! We will let you know when it is ready for pickup.');
    }
};
?>

<div x-data="{ cartOpen: false }"
     x-effect="document.body.classList.toggle('overflow-hidden', cartOpen && window.innerWidth < 1024)"
     class="space-y-5 sm:space-y-8 pb-28 lg:pb-0">

    <!-- Greeting -->
    <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500 to-amber-600 text-white p-5 sm:p-8 shadow-lg shadow-amber-200">
        <p class="text-xs sm:text-sm font-medium text-amber-100">Good day,</p>
        <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight truncate">{{ auth()->user()->name }}!</h1>
        <p class="mt-1 text-xs sm:text-sm text-amber-50">What would you like to eat today? Pick from today's lutong bahay menu.</p>
    </div>

    @if (session('success'))
        <div class="flex items-start gap-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-4 py-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-5 lg:gap-8 items-start">

        <!-- Menu -->
        <section class="lg:col-span-2 min-w-0 space-y-4">

            <!-- Search + categories (pinned under the header) -->
            <div class="sticky top-16 z-30 -mx-4 px-4 sm:mx-0 sm:px-0 py-3 bg-slate-50/95 backdrop-blur space-y-3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search dishes..."
                        class="block w-full pl-10 pr-3 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-base sm:text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                </div>

                <div class="flex gap-2 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($this->categories as $cat)
                        <button wire:key="cat-{{ $cat }}" wire:click="setCategory('{{ $cat }}')"
                            class="shrink-0 whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition-colors
                                   {{ $category === $cat
                                        ? 'bg-amber-500 text-white shadow-md shadow-amber-200'
                                        : 'bg-white text-slate-600 border border-slate-200 active:border-amber-300 active:text-amber-600' }}">
                            {{ $cat }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Dish list -->
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                @forelse ($this->filteredDishes as $dish)
                    @php $qty = $cart[$dish['id']] ?? 0; @endphp

                    <div wire:key="dish-{{ $dish['id'] }}"
                         class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 flex items-start gap-3 {{ $dish['available'] ? '' : 'opacity-60' }}">

                        <div class="w-14 h-14 sm:w-16 sm:h-16 shrink-0 rounded-xl bg-amber-50 flex items-center justify-center text-3xl">
                            {{ $dish['emoji'] }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-tight">{{ $dish['name'] }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">{{ $dish['desc'] }}</p>

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="font-extrabold text-amber-600">₱{{ number_format($dish['price'], 2) }}</span>

                                @if (! $dish['available'])
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 whitespace-nowrap">Out of stock</span>
                                @elseif ($qty > 0)
                                    <div class="inline-flex items-center rounded-full bg-amber-500 text-white shadow-sm">
                                        <button wire:click="decrease({{ $dish['id'] }})" aria-label="Remove one"
                                            class="w-10 h-10 flex items-center justify-center rounded-full active:bg-amber-600">
                                            <i data-lucide="minus" class="w-4 h-4"></i>
                                        </button>
                                        <span class="w-5 text-center text-sm font-bold">{{ $qty }}</span>
                                        <button wire:click="addToCart({{ $dish['id'] }})" aria-label="Add one"
                                            class="w-10 h-10 flex items-center justify-center rounded-full active:bg-amber-600">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                @else
                                    <button wire:click="addToCart({{ $dish['id'] }})"
                                        class="inline-flex items-center gap-1.5 h-10 pl-3.5 pr-4 rounded-full bg-amber-500 active:bg-amber-600 text-white text-sm font-semibold shadow-sm">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 text-center py-12 text-slate-500 text-sm">
                        No dishes found. Try a different search or category.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Mobile overlay behind the cart sheet -->
        <div x-show="cartOpen" x-transition.opacity @click="cartOpen = false" style="display: none;"
             class="fixed inset-0 z-[55] bg-slate-900/50 lg:hidden"></div>

        <!-- Cart: bottom sheet on mobile, sticky sidebar on desktop -->
        <aside :class="cartOpen ? 'translate-y-0' : 'translate-y-full'"
               class="fixed inset-x-0 bottom-0 z-[60] max-h-[85dvh] overflow-y-auto overscroll-contain rounded-t-3xl bg-white shadow-2xl transition-transform duration-300 ease-out
                      lg:translate-y-0 lg:sticky lg:top-24 lg:inset-x-auto lg:bottom-auto lg:z-auto lg:max-h-none lg:overflow-visible lg:rounded-2xl lg:border lg:border-slate-100 lg:shadow-sm">

            <!-- Sheet header -->
            <div class="sticky top-0 z-10 bg-white rounded-t-3xl lg:rounded-t-2xl">
                <div class="mx-auto mt-2 h-1.5 w-10 rounded-full bg-slate-200 lg:hidden"></div>
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shopping-bag" class="w-5 h-5 text-amber-500"></i> Your Order
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                            {{ $this->cartCount }} {{ Str::plural('item', $this->cartCount) }}
                        </span>
                    </h2>
                    <button type="button" @click="cartOpen = false" aria-label="Close"
                        class="lg:hidden p-2 -mr-2 text-slate-400 hover:text-slate-700">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            @if (count($this->cartItems))
                <ul class="divide-y divide-slate-100">
                    @foreach ($this->cartItems as $item)
                        <li wire:key="cart-{{ $item['id'] }}" class="px-5 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-800">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-500">₱{{ number_format($item['price'], 2) }} each</p>
                                </div>
                                <button wire:click="remove({{ $item['id'] }})" aria-label="Remove"
                                    class="p-2 -mr-2 -mt-1 text-slate-400 hover:text-rose-500">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <div class="mt-1 flex items-center justify-between">
                                <div class="inline-flex items-center rounded-full border border-slate-200">
                                    <button wire:click="decrease({{ $item['id'] }})" aria-label="Remove one"
                                        class="w-10 h-10 flex items-center justify-center text-slate-500 active:text-amber-600">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                    <span class="w-6 text-center text-sm font-semibold">{{ $item['qty'] }}</span>
                                    <button wire:click="addToCart({{ $item['id'] }})" aria-label="Add one"
                                        class="w-10 h-10 flex items-center justify-center text-slate-500 active:text-amber-600">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <span class="text-sm font-bold text-slate-800">₱{{ number_format($item['subtotal'], 2) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="sticky bottom-0 bg-white border-t border-slate-100 px-5 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Total</span>
                        <span class="text-xl font-extrabold text-slate-900">₱{{ number_format($this->cartTotal, 2) }}</span>
                    </div>

                    <button wire:click="placeOrder" wire:loading.attr="disabled" wire:target="placeOrder"
                        @click="cartOpen = false; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="w-full inline-flex justify-center items-center gap-2 bg-amber-500 active:bg-amber-600 text-white font-semibold py-3.5 rounded-xl shadow-md shadow-amber-200 transition-all disabled:opacity-50">
                        <i data-lucide="check" class="w-4 h-4"></i> Place Order
                    </button>
                    <p class="text-[11px] text-center text-slate-400">Pay at the counter when you pick up.</p>
                </div>
            @else
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    <div class="mx-auto mb-3 w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center">
                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                    </div>
                    Your cart is empty.<br>Add a dish to get started.
                </div>
            @endif
        </aside>
    </div>

    <!-- Mobile: sticky "View order" bar -->
    @if ($this->cartCount > 0)
        <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] bg-gradient-to-t from-slate-50 via-slate-50/95 to-transparent">
            <button type="button" @click="cartOpen = true"
                class="w-full flex items-center justify-between bg-amber-500 active:bg-amber-600 text-white rounded-2xl px-4 py-3.5 shadow-lg shadow-amber-300/50">
                <span class="flex items-center gap-2.5">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span class="text-sm font-semibold">View order · {{ $this->cartCount }} {{ Str::plural('item', $this->cartCount) }}</span>
                </span>
                <span class="font-extrabold">₱{{ number_format($this->cartTotal, 2) }}</span>
            </button>
        </div>
    @endif
</div>