<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

new #[Layout('components.layout.admin'), Title('Admin Reports - KarinDerya')] class extends Component
{
    public const RANGES = [
        'today' => 'Today',
        '7'     => 'Last 7 days',
        '30'    => 'Last 30 days',
        'month' => 'This month',
    ];

    public const STATUSES = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

    public const STATUS_COLORS = [
        'pending'   => '#f59e0b',
        'preparing' => '#0ea5e9',
        'ready'     => '#10b981',
        'completed' => '#64748b',
        'cancelled' => '#f43f5e',
    ];

    public string $range = '7';

    /** Data for the charts, sent to the browser as plain JSON. */
    public array $chartData = [];

    public function mount(): void
    {
        $this->chartData = $this->buildChartData();
    }

    public function updatedRange(): void
    {
        $this->chartData = $this->buildChartData();
    }

    #[Computed]
    public function ranges(): array
    {
        return self::RANGES;
    }

    #[Computed]
    public function period(): array
    {
        $range = array_key_exists($this->range, self::RANGES) ? $this->range : '7';

        $from = match ($range) {
            'today' => now()->startOfDay(),
            '30'    => now()->subDays(29)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => now()->subDays(6)->startOfDay(),
        };

        return [$from, now()->endOfDay()];
    }

    /** Same length as the selected period, directly before it. */
    #[Computed]
    public function previousPeriod(): array
    {
        [$from, $to] = $this->period;

        $days = max(1, (int) round($from->diffInDays($to)));

        return [
            $from->copy()->subDays($days),
            $from->copy()->subSecond(),
        ];
    }

    #[Computed]
    public function summary(): array
    {
        return $this->summarize(...$this->period);
    }

    #[Computed]
    public function previous(): array
    {
        return $this->summarize(...$this->previousPeriod);
    }

    #[Computed]
    public function daily(): array
    {
        [$from, $to] = $this->period;

        $rows = Order::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(total) as total, COUNT(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $row = $rows->get($date->toDateString());

            $days[] = [
                'date'   => $date,
                'total'  => (float) ($row->total ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        return $days;
    }

    #[Computed]
    public function dishSales()
    {
        [$from, $to] = $this->period;

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menus', 'menus.id', '=', 'order_items.menu_id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('menus.id, menus.name, menus.category,
                SUM(order_items.quantity) as sold,
                SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('menus.id', 'menus.name', 'menus.category')
            ->orderByDesc('sold')
            ->get();
    }

    private function summarize(Carbon $from, Carbon $to): array
    {
        $row = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("
                COUNT(*) as orders,
                COALESCE(SUM(status = 'completed'), 0) as completed,
                COALESCE(SUM(status = 'cancelled'), 0) as cancelled,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total END), 0) as sales
            ")
            ->first();

        $orders = (int) $row->orders;
        $completed = (int) $row->completed;
        $cancelled = (int) $row->cancelled;
        $sales = (float) $row->sales;

        return [
            'sales'     => $sales,
            'orders'    => $orders,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'average'   => $completed > 0 ? $sales / $completed : 0,
            'rate'      => $orders > 0 ? round($cancelled / $orders * 100, 1) : 0,
        ];
    }

    private function buildChartData(): array
    {
        [$from, $to] = $this->period;

        // Daily sales + orders
        $daily = $this->daily;
        $shortLabels = count($daily) <= 7;

        // Orders by status
        $statusCounts = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Peak hours (everything except cancelled)
        $hourCounts = Order::whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('HOUR(created_at) as hr, COUNT(*) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr');

        $hours = range(0, 23);

        // Top dishes + categories (both come from the same query)
        $dishes = $this->dishSales;
        $topDishes = $dishes->take(8);

        $categories = $dishes->groupBy('category')
            ->map(fn($rows) => (float) $rows->sum('revenue'))
            ->sortDesc();

        return [
            'daily' => [
                'labels' => array_map(
                    fn($d) => $d['date']->format($shortLabels ? 'D, M j' : 'M j'),
                    $daily
                ),
                'sales'  => array_column($daily, 'total'),
                'orders' => array_column($daily, 'orders'),
            ],
            'status' => [
                'labels' => array_map('ucfirst', self::STATUSES),
                'data'   => array_map(fn($s) => (int) ($statusCounts[$s] ?? 0), self::STATUSES),
                'colors' => array_values(self::STATUS_COLORS),
            ],
            'hours' => [
                'labels' => array_map(fn($h) => Carbon::createFromTime($h)->format('gA'), $hours),
                'data'   => array_map(fn($h) => (int) ($hourCounts[$h] ?? 0), $hours),
            ],
            'dishes' => [
                'labels' => $topDishes->pluck('name')->all(),
                'data'   => $topDishes->pluck('sold')->map(fn($v) => (int) $v)->all(),
            ],
            'categories' => [
                'labels' => $categories->keys()->all(),
                'data'   => $categories->values()->all(),
            ],
        ];
    }
};
?>

@php
    $s = $this->summary;
    $p = $this->previous;

    $delta = fn($now, $before) => $before > 0 ? (int) round(($now - $before) / $before * 100) : null;

    $cards = [
        ['label' => 'Sales', 'value' => '₱' . number_format($s['sales'], 2),
         'delta' => $delta($s['sales'], $p['sales']), 'inverse' => false,
         'note' => $s['completed'] . ' completed orders'],
        ['label' => 'Average Order', 'value' => '₱' . number_format($s['average'], 2),
         'delta' => $delta($s['average'], $p['average']), 'inverse' => false,
         'note' => 'per completed order'],
        ['label' => 'Total Orders', 'value' => (string) $s['orders'],
         'delta' => $delta($s['orders'], $p['orders']), 'inverse' => false,
         'note' => 'all statuses'],
        ['label' => 'Cancel Rate', 'value' => $s['rate'] . '%',
         'delta' => $delta($s['rate'], $p['rate']), 'inverse' => true,
         'note' => $s['cancelled'] . ' cancelled'],
    ];

    $maxSold = max(array_merge([1], $this->dishSales->pluck('sold')->map(fn($v) => (int) $v)->all()));
@endphp

<div class="space-y-8">

    <!-- Heading + range -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Reports</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $this->period[0]->format('M d, Y') }} – {{ $this->period[1]->format('M d, Y') }}
                · sales count completed orders only
            </p>
        </div>

        <div class="flex gap-2 overflow-x-auto">
            @foreach ($this->ranges as $key => $label)
                <button wire:key="range-{{ $key }}" wire:click="$set('range', '{{ $key }}')"
                    class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors
                        {{ $range === (string) $key
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-200'
                            : 'bg-white text-slate-600 border border-slate-200 hover:border-amber-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Summary cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach ($cards as $card)
            <div wire:key="card-{{ $loop->index }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $card['value'] }}</p>

                <div class="mt-1 flex items-center gap-2 text-xs">
                    @if ($card['delta'] !== null)
                        @php $good = $card['inverse'] ? $card['delta'] <= 0 : $card['delta'] >= 0; @endphp
                        <span class="font-semibold {{ $good ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $card['delta'] >= 0 ? '▲' : '▼' }} {{ abs($card['delta']) }}%
                        </span>
                        <span class="text-slate-400">vs previous period</span>
                    @else
                        <span class="text-slate-400">{{ $card['note'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    @if ($s['orders'] === 0)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-medium px-4 py-3">
            No orders in this period, so the charts are empty. Try a longer range.
        </div>
    @endif

    <!-- Daily sales -->
    <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Sales &amp; Orders Over Time</h2>
            <p class="text-xs text-slate-400">Bars: sales in ₱. Line: completed orders.</p>
        </div>
        <div class="p-6">
            <div class="relative h-72" wire:ignore>
                <canvas data-chart="daily"></canvas>
            </div>
        </div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Peak hours -->
        <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Peak Hours</h2>
                <p class="text-xs text-slate-400">When orders come in (cancelled excluded)</p>
            </div>
            <div class="p-6">
                <div class="relative h-64" wire:ignore>
                    <canvas data-chart="hours"></canvas>
                </div>
            </div>
        </section>

        <!-- Orders by status -->
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Orders by Status</h2>
                <p class="text-xs text-slate-400">All orders in this period</p>
            </div>
            <div class="p-6">
                <div class="relative h-64" wire:ignore>
                    <canvas data-chart="status"></canvas>
                </div>
            </div>
        </section>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Top dishes chart -->
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Top Dishes</h2>
                <p class="text-xs text-slate-400">Quantity sold (completed orders)</p>
            </div>
            <div class="p-6">
                <div class="relative h-72" wire:ignore>
                    <canvas data-chart="dishes"></canvas>
                </div>
            </div>
        </section>

        <!-- Sales by category -->
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Sales by Category</h2>
                <p class="text-xs text-slate-400">Revenue share</p>
            </div>
            <div class="p-6">
                <div class="relative h-72" wire:ignore>
                    <canvas data-chart="categories"></canvas>
                </div>
            </div>
        </section>
    </div>

    <!-- Dish performance table -->
    <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Dish Performance</h2>
            <p class="text-xs text-slate-400">Every dish sold in this period</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3 font-semibold">Dish</th>
                        <th class="px-6 py-3 font-semibold hidden sm:table-cell">Category</th>
                        <th class="px-6 py-3 font-semibold text-right">Sold</th>
                        <th class="px-6 py-3 font-semibold text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->dishSales as $dish)
                        <tr wire:key="dish-{{ $dish->id }}" class="hover:bg-slate-50/70">
                            <td class="px-6 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $dish->name }}</p>
                                <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-amber-500"
                                        style="width: {{ round($dish->sold / $maxSold * 100) }}%"></div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 hidden sm:table-cell">{{ $dish->category }}</td>
                            <td class="px-6 py-3.5 text-right text-slate-700">{{ $dish->sold }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold text-slate-800">
                                ₱{{ number_format($dish->revenue, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                No completed orders in this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endassets

@script
<script>
    const charts = {};
    const palette = ['#f59e0b', '#0ea5e9', '#10b981', '#8b5cf6', '#f43f5e', '#64748b', '#ec4899', '#14b8a6'];
    const peso = (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const make = (name, config) => {
        if (charts[name]) charts[name].destroy();
        const canvas = $wire.$el.querySelector('[data-chart="' + name + '"]');
        if (!canvas) return;
        charts[name] = new Chart(canvas, config);
    };

    const base = { responsive: true, maintainAspectRatio: false };

    const render = (d) => {
        if (!d || !d.daily) return;

        make('daily', {
            type: 'bar',
            data: {
                labels: d.daily.labels,
                datasets: [
                    { type: 'bar', label: 'Sales', data: d.daily.sales, backgroundColor: '#f59e0b', borderRadius: 6, yAxisID: 'y' },
                    { type: 'line', label: 'Orders', data: d.daily.orders, borderColor: '#0ea5e9', backgroundColor: '#0ea5e9', tension: 0.3, yAxisID: 'y1' },
                ],
            },
            options: {
                ...base,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => '₱' + Number(v).toLocaleString() } },
                    y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (c) => c.dataset.yAxisID === 'y'
                                ? ' Sales: ' + peso(c.parsed.y)
                                : ' Orders: ' + c.parsed.y,
                        },
                    },
                },
            },
        });

        make('hours', {
            type: 'bar',
            data: {
                labels: d.hours.labels,
                datasets: [{ label: 'Orders', data: d.hours.data, backgroundColor: '#0ea5e9', borderRadius: 4 }],
            },
            options: {
                ...base,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxRotation: 0, autoSkip: true } } },
                plugins: { legend: { display: false } },
            },
        });

        make('status', {
            type: 'doughnut',
            data: {
                labels: d.status.labels,
                datasets: [{ data: d.status.data, backgroundColor: d.status.colors, borderWidth: 2 }],
            },
            options: { ...base, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
        });

        make('dishes', {
            type: 'bar',
            data: {
                labels: d.dishes.labels,
                datasets: [{ label: 'Sold', data: d.dishes.data, backgroundColor: '#f59e0b', borderRadius: 6 }],
            },
            options: {
                ...base,
                indexAxis: 'y',
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } },
            },
        });

        make('categories', {
            type: 'doughnut',
            data: {
                labels: d.categories.labels,
                datasets: [{ data: d.categories.data, backgroundColor: palette, borderWidth: 2 }],
            },
            options: {
                ...base,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + peso(c.parsed) } },
                },
            },
        });
    };

    render($wire.chartData);
    $wire.$watch('chartData', (value) => render(value));
</script>
@endscript