<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout.admin'), Title('Admin Dashboard - KarinDerya')] class extends Component
{
    // TODO: replace the sample data below with Eloquent queries
    // once the orders / dishes tables exist.

    #[Computed]
    public function stats(): array
    {
        return [
            ['label' => "Today's Sales",  'value' => '₱4,250.00', 'icon' => 'banknote',  'note' => '+12% vs yesterday', 'badge' => 'bg-emerald-100 text-emerald-600'],
            ['label' => 'Total Orders',   'value' => '48',        'icon' => 'receipt',   'note' => '6 pending now',     'badge' => 'bg-amber-100 text-amber-600'],
            ['label' => 'Dishes on Menu', 'value' => '14',        'icon' => 'utensils',  'note' => '2 out of stock',    'badge' => 'bg-sky-100 text-sky-600'],
            ['label' => 'Customers',      'value' => '126',       'icon' => 'users',     'note' => '+5 this week',      'badge' => 'bg-violet-100 text-violet-600'],
        ];
    }

    #[Computed]
    public function recentOrders(): array
    {
        return [
            ['id' => '#1048', 'customer' => 'Juan Dela Cruz', 'items' => 'Pork Adobo x2',        'total' => '₱170.00', 'status' => 'pending'],
            ['id' => '#1047', 'customer' => 'Maria Santos',   'items' => 'Sinigang na Baboy x1', 'total' => '₱90.00',  'status' => 'preparing'],
            ['id' => '#1046', 'customer' => 'Pedro Reyes',    'items' => 'Chicken Menudo x3',    'total' => '₱225.00', 'status' => 'ready'],
            ['id' => '#1045', 'customer' => 'Ana Lopez',      'items' => 'Pork Adobo x1, Rice',  'total' => '₱95.00',  'status' => 'completed'],
        ];
    }

    #[Computed]
    public function topDishes(): array
    {
        return [
            ['name' => 'Pork Adobo',        'sold' => 32],
            ['name' => 'Sinigang na Baboy', 'sold' => 24],
            ['name' => 'Chicken Menudo',    'sold' => 18],
            ['name' => 'Pinakbet',          'sold' => 11],
        ];
    }
};
?>

@php
    $statusStyles = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'preparing' => 'bg-sky-100 text-sky-700',
        'ready'     => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-slate-100 text-slate-600',
    ];
    $maxSold = max(array_column($this->topDishes, 'sold'));
@endphp

<div class="space-y-8">

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">
                Welcome back, {{ auth()->user()->name }}. Here's what's happening today.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="#" class="inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i> View Orders
            </a>
            <a href="#" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-amber-200 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i> Add Dish
            </a>
        </div>
    </div>

    <!-- Stat cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach ($this->stats as $stat)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-start justify-between">
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
                        @foreach ($this->recentOrders as $order)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order['id'] }}</td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $order['customer'] }}</td>
                                <td class="px-6 py-3.5 text-slate-500 hidden sm:table-cell">{{ $order['items'] }}</td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order['total'] }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$order['status']] }}">
                                        {{ $order['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
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
                @foreach ($this->topDishes as $dish)
                    <li>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700">{{ $dish['name'] }}</span>
                            <span class="text-xs text-slate-500">{{ $dish['sold'] }} sold</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-amber-500"
                                 style="width: {{ round($dish['sold'] / $maxSold * 100) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>