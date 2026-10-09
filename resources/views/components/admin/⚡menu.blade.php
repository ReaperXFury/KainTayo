<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Menu;

new #[Layout('components.layout.admin'), Title('Admin Menu - KarinDerya')]
    class extends Component {

    use WithFileUploads;

    // Filters
    public string $search = '';
    public string $category = 'All';
    public string $status = 'all'; // all | available | out

    // Form fields (used by the add/edit modal)
    public ?int $editingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('required|string|max:50')]
    public string $dishCategory = '';

    #[Validate('nullable|image|max:2048')] // JPG/PNG/WebP, up to 2MB
    public $photo = null;

    public ?string $existingImage = null; // URL of the saved image while editing

    public bool $available = true;

    // TODO: replace with Dish::all() once the dishes table exists.
    #[Computed]
    public function dishes(): array
    {
        return Menu::orderBy('category')->orderBy('name')->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'desc' => $m->details ?? '',
                'price' => (float) $m->price,
                'category' => $m->category,
                'image' => $m->image_path ? Storage::url($m->image_path) : null,
                'available' => $m->available,
            ])
            ->all();
    }

    #[Computed]
    public function categories(): array
    {
        return array_values(array_unique(array_column($this->dishes, 'category')));
    }

    #[Computed]
    public function filteredDishes(): array
    {
        return array_values(array_filter($this->dishes, function ($dish) {
            $matchesCategory = $this->category === 'All' || $dish['category'] === $this->category;
            $matchesStatus = $this->status === 'all'
                || ($this->status === 'available' && $dish['available'])
                || ($this->status === 'out' && !$dish['available']);
            $matchesSearch = $this->search === ''
                || str_contains(strtolower($dish['name']), strtolower($this->search));

            return $matchesCategory && $matchesStatus && $matchesSearch;
        }));
    }

    #[Computed]
    public function stats(): array
    {
        $total = Menu::count();
        $available = Menu::where('available', true)->count();

        return [
            ['label' => 'Total Dishes', 'value' => $total, 'icon' => 'utensils', 'badge' => 'bg-sky-100 text-sky-600'],
            ['label' => 'Available', 'value' => $available, 'icon' => 'circle-check', 'badge' => 'bg-emerald-100 text-emerald-600'],
            ['label' => 'Out of Stock', 'value' => $total - $available, 'icon' => 'circle-x', 'badge' => 'bg-rose-100 text-rose-600'],
        ];
    }

    // ---------------------------------------------------------------
    // Management actions: the UI already calls these. Implement them.
    // ---------------------------------------------------------------

    public function create(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'details' => $this->description ?: null,
            'category' => $this->dishCategory,
            'price' => $this->price,
            'available' => $this->available,
        ];

        $menu = $this->editingId ? Menu::findOrFail($this->editingId) : null;

        if ($this->photo) {
            // Remove the old image when it is being replaced
            if ($menu?->image_path) {
                Storage::disk('public')->delete($menu->image_path);
            }

            $data['image_path'] = $this->photo->store('menus', 'public');
        }

        $menu ? $menu->update($data) : Menu::create($data);

        $this->resetForm();
        $this->dispatch('dish-saved'); // closes the modal
    }

    public function edit(int $id): void
    {
        $menu = Menu::findOrFail($id);

        $this->resetValidation();
        $this->photo = null;
        $this->editingId = $menu->id;
        $this->name = $menu->name;
        $this->description = $menu->details ?? '';
        $this->price = (string) $menu->price;
        $this->dishCategory = $menu->category;
        $this->available = $menu->available;
        $this->existingImage = $menu->image_path ? Storage::url($menu->image_path) : null;
    }

    public function toggleAvailability(int $id): void
    {
        $menu = Menu::findOrFail($id);
        $menu->update(['available' => !$menu->available]);
    }

    public function delete(int $id): void
    {
        $menu = Menu::findOrFail($id);

        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->delete();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'price', 'dishCategory', 'photo', 'existingImage']);
        $this->available = true;
        $this->resetValidation();
    }
};
?>

<div x-data="{ formOpen: false, deleteId: null, deleteName: '' }" x-on:dish-saved.window="formOpen = false"
    x-effect="document.body.classList.toggle('overflow-hidden', formOpen || deleteId !== null)"
    class="space-y-6 sm:space-y-8">

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Menu</h1>
            <p class="text-sm text-slate-500 mt-1">Manage the dishes customers can order.</p>
        </div>
        <button type="button" wire:click="create" @click="formOpen = true"
            class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-3 sm:py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-amber-200 transition-all">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Dish
        </button>
    </div>

    <!-- Stats -->
    <section class="grid grid-cols-3 gap-3 sm:gap-5">
        @foreach ($this->stats as $stat)
            <div
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <p class="text-[10px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        {{ $stat['label'] }}
                    </p>
                    <p class="mt-1 text-xl sm:text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                </div>
                <div class="hidden sm:block p-2.5 rounded-xl {{ $stat['badge'] }}">
                    <i data-lucide="{{ $stat['icon'] }}" class="w-5 h-5"></i>
                </div>
            </div>
        @endforeach
    </section>

    <!-- Filters -->
    <section
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 sm:p-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i data-lucide="search" class="w-4 h-4"></i>
            </div>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search dishes..."
                class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-base sm:text-sm placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
        </div>

        <div class="grid grid-cols-2 gap-3 sm:contents">
            <select wire:model.live="category"
                class="block w-full sm:w-44 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="All">All categories</option>
                @foreach ($this->categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>

            <select wire:model.live="status"
                class="block w-full sm:w-40 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="all">All status</option>
                <option value="available">Available</option>
                <option value="out">Out of stock</option>
            </select>
        </div>
    </section>

    <!-- Desktop table -->
    <section class="hidden md:block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wider text-slate-400 bg-slate-50/70">
                    <th class="px-6 py-3 font-semibold">Dish</th>
                    <th class="px-6 py-3 font-semibold">Category</th>
                    <th class="px-6 py-3 font-semibold">Price</th>
                    <th class="px-6 py-3 font-semibold">Available</th>
                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->filteredDishes as $dish)
                    <tr wire:key="row-{{ $dish['id'] }}" class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-11 h-11 shrink-0 rounded-xl bg-amber-50 overflow-hidden flex items-center justify-center text-amber-300">
                                    @if ($dish['image'])
                                        <img src="{{ $dish['image'] }}" alt="{{ $dish['name'] }}" loading="lazy"
                                            class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="image" class="w-5 h-5"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800">{{ $dish['name'] }}</p>
                                    <p class="text-xs text-slate-500 truncate max-w-xs">{{ $dish['desc'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ $dish['category'] }}</span>
                        </td>
                        <td class="px-6 py-3.5 font-semibold text-slate-800">₱{{ number_format($dish['price'], 2) }}</td>
                        <td class="px-6 py-3.5">
                            <button type="button" role="switch" aria-checked="{{ $dish['available'] ? 'true' : 'false' }}"
                                wire:click="toggleAvailability({{ $dish['id'] }})" aria-label="Toggle availability"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $dish['available'] ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                <span
                                    class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform {{ $dish['available'] ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" wire:click="edit({{ $dish['id'] }})" @click="formOpen = true"
                                    aria-label="Edit"
                                    class="p-2 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button type="button" @click="deleteId = {{ $dish['id'] }}; deleteName = @js($dish['name'])"
                                    aria-label="Delete"
                                    class="p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">No dishes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <!-- Mobile cards -->
    <section class="md:hidden space-y-3">
        @forelse ($this->filteredDishes as $dish)
            <div wire:key="card-{{ $dish['id'] }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5">
                <div class="flex items-start gap-3">
                    <div
                        class="w-14 h-14 shrink-0 rounded-xl bg-amber-50 overflow-hidden flex items-center justify-center text-amber-300 {{ $dish['available'] ? '' : 'opacity-60' }}">
                        @if ($dish['image'])
                            <img src="{{ $dish['image'] }}" alt="{{ $dish['name'] }}" loading="lazy"
                                class="w-full h-full object-cover">
                        @else
                            <i data-lucide="image" class="w-6 h-6"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-slate-900 text-sm leading-tight">{{ $dish['name'] }}</h3>
                            <span
                                class="font-extrabold text-amber-600 text-sm whitespace-nowrap">₱{{ number_format($dish['price'], 2) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">{{ $dish['desc'] }}</p>
                        <span
                            class="mt-1.5 inline-block px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600">{{ $dish['category'] }}</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <label
                        class="inline-flex items-center gap-2 text-xs font-medium {{ $dish['available'] ? 'text-emerald-600' : 'text-slate-500' }}">
                        <button type="button" role="switch" aria-checked="{{ $dish['available'] ? 'true' : 'false' }}"
                            wire:click="toggleAvailability({{ $dish['id'] }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $dish['available'] ? 'bg-emerald-500' : 'bg-slate-300' }}">
                            <span
                                class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform {{ $dish['available'] ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                        {{ $dish['available'] ? 'Available' : 'Out of stock' }}
                    </label>

                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="edit({{ $dish['id'] }})" @click="formOpen = true"
                            aria-label="Edit"
                            class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 active:bg-amber-50 active:text-amber-600">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="deleteId = {{ $dish['id'] }}; deleteName = @js($dish['name'])"
                            aria-label="Delete"
                            class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 active:bg-rose-50 active:text-rose-600">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-sm text-slate-500">No dishes found.</div>
        @endforelse
    </section>

    <!-- Add / Edit modal -->
    <div x-show="formOpen" x-transition.opacity style="display: none;"
        class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center bg-slate-900/50 sm:p-4"
        @keydown.escape.window="formOpen = false" @click.self="formOpen = false">

        <div class="w-full sm:max-w-lg max-h-[92dvh] overflow-y-auto bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl">
            <div
                class="sticky top-0 z-10 bg-white px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">{{ $editingId ? 'Edit Dish' : 'Add Dish' }}</h2>
                <button type="button" @click="formOpen = false" aria-label="Close"
                    class="p-2 -mr-2 text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form wire:submit="save" class="p-5 space-y-4">

                <!-- Photo -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Photo</label>
                    <div class="flex items-center gap-4">
                        <div
                            class="w-20 h-20 shrink-0 rounded-2xl bg-amber-50 border border-dashed border-amber-200 overflow-hidden flex items-center justify-center text-amber-300">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                            @elseif ($existingImage)
                                <img src="{{ $existingImage }}" class="w-full h-full object-cover" alt="Current photo">
                            @else
                                <i data-lucide="image-plus" class="w-7 h-7"></i>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <label for="photo"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                {{ $photo || $existingImage ? 'Change photo' : 'Upload photo' }}
                            </label>
                            <input wire:model="photo" id="photo" type="file" accept="image/*" class="sr-only">
                            <p class="mt-1.5 text-xs text-slate-400">JPG, PNG or WebP. Max 2MB.</p>
                            <p wire:loading wire:target="photo" class="text-xs text-amber-600 font-medium">Uploading...
                            </p>
                            @error('photo')
                            <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name"
                        class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Name</label>
                    <input wire:model="name" id="name" type="text" placeholder="e.g. Pork Adobo"
                        class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('name')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description"
                        class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                    <textarea wire:model="description" id="description" rows="2"
                        placeholder="Short description of the dish"
                        class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                    @error('description')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Price + Category -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="price"
                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Price
                            (₱)</label>
                        <input wire:model="price" id="price" type="number" step="0.01" min="0" inputmode="decimal"
                            placeholder="0.00"
                            class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        @error('price')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="dishCategory"
                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category</label>
                        <input wire:model="dishCategory" id="dishCategory" type="text" list="category-options"
                            placeholder="e.g. Ulam"
                            class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <datalist id="category-options">
                            @foreach ($this->categories as $cat)
                                <option value="{{ $cat }}"></option>
                            @endforeach
                        </datalist>
                        @error('dishCategory')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                    <input wire:model="available" type="checkbox"
                        class="w-5 h-5 text-amber-500 border-slate-300 rounded focus:ring-amber-500">
                    <span class="text-sm text-slate-700 font-medium">Available for ordering</span>
                </label>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" @click="formOpen = false"
                        class="px-5 py-3 sm:py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex justify-center items-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-200 transition-all disabled:opacity-50">
                        <i data-lucide="check" class="w-4 h-4"></i> {{ $editingId ? 'Save Changes' : 'Add Dish' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete confirmation -->
    <div x-show="deleteId !== null" x-transition.opacity style="display: none;"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4"
        @keydown.escape.window="deleteId = null" @click.self="deleteId = null">
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                <i data-lucide="trash-2" class="w-6 h-6"></i>
            </div>
            <h3 class="font-bold text-slate-900">Delete dish?</h3>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-semibold text-slate-700" x-text="deleteName"></span> will be removed from the menu.
                This can't be undone.
            </p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button type="button" @click="deleteId = null"
                    class="px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="$wire.delete(deleteId); deleteId = null"
                    class="px-4 py-3 rounded-xl text-sm font-semibold text-white bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>