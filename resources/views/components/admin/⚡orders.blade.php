<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;

new #[Layout('components.layout.admin'), Title('Admin Orders - KarinDerya')] class extends Component
{
    use WithPagination;

    public const STATUSES = ['pending', 'ready', 'completed', 'cancelled'];

    public string $status = 'pending';
    public string $search = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        $counts = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return collect(self::STATUSES)
            ->mapWithKeys(fn($s) => [$s => $counts[$s] ?? 0])
            ->all();
    }

    #[Computed]
    public function orders()
    {
        return Order::with('items.menu')
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('id', ltrim($this->search, '#'));
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function setStatus(int $orderId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }

        Order::whereKey($orderId)->update(['status' => $status]);
    }
};
?>

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Orders</h1>
        <p class="text-sm text-slate-500">Manage incoming customer orders.</p>
    </div>

    <!-- Status tabs + search -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2 overflow-x-auto">
            @foreach (['pending', 'ready', 'completed', 'cancelled'] as $s)
                <button wire:key="tab-{{ $s }}" wire:click="$set('status', '{{ $s }}')"
                    class="shrink-0 px-4 py-2 rounded-full text-sm font-medium capitalize transition-colors
                        {{ $status === $s
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-200'
                            : 'bg-white text-slate-600 border border-slate-200 hover:border-amber-300' }}">
                    {{ $s }}
                    <span class="ml-1 text-xs opacity-80">{{ $this->counts[$s] }}</span>
                </button>
            @endforeach
        </div>

        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or order #"
            class="w-full sm:w-64 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
    </div>

    <!-- Orders -->
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($this->orders as $order)
            <div wire:key="order-{{ $order->id }}"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-900 truncate">#{{ $order->id }} · {{ $order->name }}</p>
                        <p class="text-xs text-slate-500">{{ $order->created_at->format('M d, Y g:i A') }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                        @class([
                            'bg-amber-100 text-amber-700' => $order->status === 'pending',
                            'bg-sky-100 text-sky-700' => $order->status === 'ready',
                            'bg-emerald-100 text-emerald-700' => $order->status === 'completed',
                            'bg-slate-100 text-slate-500' => $order->status === 'cancelled',
                        ])">
                        {{ $order->status }}
                    </span>
                </div>

                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="text-slate-700">{{ $item->quantity }} × {{ $item->menu->name }}</span>
                            <span class="font-medium text-slate-800">
                                ₱{{ number_format($item->price * $item->quantity, 2) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-sm text-slate-500">Total</span>
                    <span class="text-lg font-extrabold text-slate-900">₱{{ number_format($order->total, 2) }}</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($order->status === 'pending')
                        <button wire:click="setStatus({{ $order->id }}, 'ready')"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">
                            Mark ready
                        </button>
                        <button wire:click="setStatus({{ $order->id }}, 'cancelled')"
                            wire:confirm="Cancel this order?"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:text-rose-600 text-sm font-semibold">
                            Cancel
                        </button>
                    @elseif ($order->status === 'ready')
                        <button wire:click="setStatus({{ $order->id }}, 'completed')"
                            class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold">
                            Mark completed (paid)
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="lg:col-span-2 text-center py-12 text-slate-500 text-sm">
                No {{ $status }} orders found.
            </div>
        @endforelse
    </div>

    {{ $this->orders->links() }}
</div>