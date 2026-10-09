<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

new #[Layout('components.layout.admin'), Title('Admin Customers - KarinDerya')] class extends Component
{
    use WithPagination;

    public const SORTS = ['newest', 'most_orders', 'top_spender'];

    public string $search = '';
    public string $sort = 'newest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'newest';

        return User::query()
            ->where('role', 'customer') // adjust to however you mark customers
            ->withCount('orders')
            ->withSum(['orders as spent' => fn($q) => $q->where('status', 'completed')], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($sort === 'most_orders', fn($q) => $q->orderByDesc('orders_count'))
            ->when($sort === 'top_spender', fn($q) => $q->orderByDesc('spent'))
            ->when($sort === 'newest', fn($q) => $q->latest())
            ->paginate(10);
    }
};
?>

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Customers</h1>
        <p class="text-sm text-slate-500">People who have registered to order.</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or email"
            class="w-full sm:w-72 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">

        <select wire:model.live="sort"
            class="w-full sm:w-48 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            <option value="newest">Newest first</option>
            <option value="most_orders">Most orders</option>
            <option value="top_spender">Top spenders</option>
        </select>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Customer</th>
                        <th class="px-5 py-3 font-semibold text-center">Orders</th>
                        <th class="px-5 py-3 font-semibold text-right">Total spent</th>
                        <th class="px-5 py-3 font-semibold">Last order</th>
                        <th class="px-5 py-3 font-semibold">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ $customer->name }}</p>
                                <p class="text-xs text-slate-500">{{ $customer->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-700">{{ $customer->orders_count }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">
                                ₱{{ number_format($customer->spent ?? 0, 2) }}
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                {{ $customer->last_order_at ? \Carbon\Carbon::parse($customer->last_order_at)->diffForHumans() : '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                {{ $customer->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $this->customers->links() }}
</div>