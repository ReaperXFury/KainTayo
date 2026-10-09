<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

new #[Layout('components.layout.admin'), Title('Admin Dashboard - KarinDerya')] class extends Component
{
    #[Computed]
    public function stats(): array
    {
        $sales = fn($date) => (float) Order::where('status', 'completed')
            ->whereDate('created_at', $date)
            ->sum('total');

        $today = $sales(today());
        $yesterday = $sales(today()->subDay());
        $change = $yesterday > 0 ? round(($today - $yesterday) / $yesterday * 100) : null;

        $pending = Order::where('status', 'pending')->count();
        $outOfStock = Menu::where('available', false)->count();

        // adjust to however you mark customers
        $customers = User::where('role', 'customer');
        $newCustomers = (clone $customers)->where('created_at', '>=', now()->subWeek())->count();

        return [
            [
                'label' => "Today's Sales",
                'value' => '₱' . number_format($today, 2),
                'icon'  => 'banknote',
                'note'  => $change === null ? 'No sales yesterday' : sprintf('%+d%% vs yesterday', $change),
                'badge' => 'bg-emerald-100 text-emerald-600',
            ],
            [
                'label' => 'Total Orders',
                'value' => (string) Order::whereDate('created_at', today())->count(),
                'icon'  => 'receipt',
                'note'  => "{$pending} pending now",
                'badge' => 'bg-amber-100 text-amber-600',
            ],
            [
                'label' => 'Dishes on Menu',
                'value' => (string) Menu::count(),
                'icon'  => 'utensils',
                'note'  => "{$outOfStock} out of stock",
                'badge' => 'bg-sky-100 text-sky-600',
            ],
            [
                'label' => 'Customers',
                'value' => (string) $customers->count(),
                'icon'  => 'users',
                'note'  => "+{$newCustomers} this week",
                'badge' => 'bg-violet-100 text-violet-600',
            ],
        ];
    }

    #[Computed]
    public function recentOrders(): array
    {
        return Order::with('items.menu')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($o) => [
                'id'       => '#' . $o->id,
                'customer' => $o->name,
                'items'    => $o->items->map(fn($i) => $i->menu->name . ' x' . $i->quantity)->implode(', '),
                'total'    => '₱' . number_format($o->total, 2),
                'status'   => $o->status,
            ])
            ->all();
    }

    #[Computed]
    public function topDishes(): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menus', 'menus.id', '=', 'order_items.menu_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereDate('orders.created_at', today())
            ->selectRaw('menus.name, SUM(order_items.quantity) as sold')
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('sold')
            ->limit(4)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'sold' => (int) $r->sold])
            ->all();
    }
};
?>

@php
    $statusStyles = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'preparing' => 'bg-sky-100 text-sky-700',
        'ready'     => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-slate-100 text-slate-600',
        'cancelled' => 'bg-rose-100 text-rose-700',
    ];
    $maxSold = max(array_merge([1], array_column($this->topDishes, 'sold')));
@endphp

<div class="space-y-8" wire:poll.30s>

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">
                Welcome back, {{ auth()->user()->name }}. Here's what's happening today.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.orders') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i> View Orders
            </a>
            <a href="{{ route('admin.menu') }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-amber-200 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i> Add Dish
            </a>
        </div>
    </div>

    <!-- Stat cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach ($this->stats as $stat)
            <div wire:key="stat-{{ $loop->index }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['note'] }}</p>
                </div>
                <div class="p-2.5 rounded-xl {{ $stat['badge'] }}">
                    <i data-lucide="{{ $stat['icon'] }}" class="w-5 h-5"></i>
                </div>
            </div>
        @endforeach
    </section>

    <div class="grid xl:grid-cols-3 gap-6">

        <!-- Recent orders -->
        <section class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Recent Orders</h2>
                <a href="#" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3 font-semibold">Order</th>
                            <th class="px-6 py-3 font-semibold">Customer</th>
                            <th class="px-6 py-3 font-semibold hidden sm:table-cell">Items</th>
                            <th class="px-6 py-3 font-semibold">Total</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->recentOrders as $order)
                            <tr wire:key="order-{{ $order['id'] }}" class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order['id'] }}</td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $order['customer'] }}</td>
                                <td class="px-6 py-3.5 text-slate-500 hidden sm:table-cell">{{ $order['items'] }}</td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order['total'] }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$order['status']] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $order['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">No orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Top dishes -->
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Top Dishes</h2>
                <p class="text-xs text-slate-400">Most ordered today</p>
            </div>

            <ul class="p-6 space-y-5">
                @forelse ($this->topDishes as $dish)
                    <li wire:key="dish-{{ $loop->index }}">
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700">{{ $dish['name'] }}</span>
                            <span class="text-xs text-slate-500">{{ $dish['sold'] }} sold</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-amber-500"
                                 style="width: {{ round($dish['sold'] / $maxSold * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-center text-sm text-slate-500 py-4">No orders yet today.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>