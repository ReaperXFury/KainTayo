<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;

new #[Layout('components.layout.customer'), Title('My Orders - KarinDerya')] class extends Component
{
    use WithPagination;

    public const ACTIVE = ['pending', 'preparing', 'ready'];
    public const HISTORY = ['completed', 'cancelled'];
    public const STEPS = ['pending', 'preparing', 'ready', 'completed'];

    public string $tab = 'active'; // active | history

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        $counts = Order::where('user_id', auth()->id())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active'  => (int) $counts->only(self::ACTIVE)->sum(),
            'history' => (int) $counts->only(self::HISTORY)->sum(),
        ];
    }

    #[Computed]
    public function orders()
    {
        $statuses = $this->tab === 'history' ? self::HISTORY : self::ACTIVE;

        return Order::with('items.menu')
            ->where('user_id', auth()->id())
            ->whereIn('status', $statuses)
            ->latest()
            ->paginate(8);
    }

    public function cancel(int $orderId): void
    {
        // Scoped to the logged-in customer and to pending orders only
        $cancelled = Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->whereKey($orderId)
            ->update(['status' => 'cancelled']);

        if ($cancelled) {
            session()->flash('success', "Order #{$orderId} was cancelled.");
        } else {
            session()->flash('error', "Order #{$orderId} can't be cancelled anymore. It's already being prepared.");
        }
    }
};
?>

@php
    $badge = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'preparing' => 'bg-sky-100 text-sky-700',
        'ready'     => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-slate-100 text-slate-600',
        'cancelled' => 'bg-rose-100 text-rose-700',
    ];

    $messages = [
        'pending'   => 'Waiting for the kitchen to accept your order.',
        'preparing' => 'Your order is being prepared.',
        'ready'     => 'Ready for pickup! Pay at the counter.',
        'completed' => 'Thank you for ordering!',
        'cancelled' => 'This order was cancelled.',
    ];
@endphp

<div class="space-y-5 sm:space-y-6" @if ($tab === 'active') wire:poll.15s @endif>

    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">My Orders</h1>
        <p class="text-sm text-slate-500 mt-1">Track your orders and see your past ones.</p>
    </div>

    @if (session('success'))
        <div class="flex items-start gap-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-4 py-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-start gap-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium px-4 py-3">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex gap-2">
        @foreach (['active' => 'Active', 'history' => 'History'] as $key => $label)
            <button wire:key="tab-{{ $key }}" wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2 rounded-full text-sm font-medium transition-colors
                    {{ $tab === $key
                        ? 'bg-amber-500 text-white shadow-md shadow-amber-200'
                        : 'bg-white text-slate-600 border border-slate-200 active:border-amber-300' }}">
                {{ $label }}
                <span class="ml-1 text-xs opacity-80">{{ $this->counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <!-- Orders -->
    <div class="space-y-4">
        @forelse ($this->orders as $order)
            <div wire:key="order-{{ $order->id }}"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 space-y-4">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-900">Order #{{ $order->id }}</p>
                        <p class="text-xs text-slate-500">{{ $order->created_at->format('M d, Y · g:i A') }}</p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold capitalize {{ $badge[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $order->status }}
                    </span>
                </div>

                <!-- Progress tracker -->
                @if ($order->status !== 'cancelled')
                    @php $current = array_search($order->status, $this::STEPS, true); @endphp
                    <div>
                        <div class="flex items-center">
                            @foreach ($this::STEPS as $i => $step)
                                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                    <div class="w-3 h-3 rounded-full shrink-0 {{ $i <= $current ? 'bg-amber-500' : 'bg-slate-200' }}"></div>
                                    @unless ($loop->last)
                                        <div class="h-0.5 flex-1 mx-1 {{ $i < $current ? 'bg-amber-500' : 'bg-slate-200' }}"></div>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1.5 flex justify-between text-[10px] sm:text-xs text-slate-400 capitalize">
                            @foreach ($this::STEPS as $step)
                                <span class="{{ $step === $order->status ? 'text-amber-600 font-semibold' : '' }}">{{ $step }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <p class="text-sm text-slate-600">{{ $messages[$order->status] ?? '' }}</p>

                <!-- Items -->
                <ul class="divide-y divide-slate-100 text-sm border-t border-slate-100">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3 py-2.5">
                            <span class="text-slate-700">{{ $item->quantity }} × {{ $item->menu->name }}</span>
                            <span class="font-medium text-slate-800 whitespace-nowrap">
                                ₱{{ number_format($item->price * $item->quantity, 2) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-sm text-slate-500">Total</span>
                    <span class="text-lg font-extrabold text-slate-900">₱{{ number_format($order->total, 2) }}</span>
                </div>

                @if ($order->status === 'pending')
                    <button wire:click="cancel({{ $order->id }})"
                        wire:confirm="Cancel this order?"
                        class="w-full sm:w-auto px-4 py-3 sm:py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-rose-600 hover:border-rose-200 text-sm font-semibold transition-colors">
                        Cancel order
                    </button>
                @endif
            </div>
        @empty
            <div class="text-center py-14 text-sm text-slate-500">
                <div class="mx-auto mb-3 w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-6 h-6"></i>
                </div>
                @if ($tab === 'active')
                    You have no active orders.
                @else
                    No past orders yet.
                @endif
            </div>
        @endforelse
    </div>

    {{ $this->orders->links() }}
</div>