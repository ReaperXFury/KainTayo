<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layout.app'), Title('Log In - KarinDerya')]
    class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $home = Auth::user()->role === 'admin'
            ? route('admin.dashboard', absolute: false)
            : route('customer.home', absolute: false);

        $this->redirect($home, navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
};
?>

<div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8">

    <!-- Top Brand Logo -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="/" class="inline-flex items-center gap-2 text-amber-600 font-bold text-2xl tracking-tight">
            <div class="bg-amber-500 text-white p-2.5 rounded-xl shadow-md shadow-amber-200">
                <i data-lucide="utensils-crossed" class="w-6 h-6"></i>
            </div>
            <span>Karin<span class="text-amber-800">Derya</span></span>
        </a>
        <h2 class="mt-4 text-2xl font-extrabold text-slate-900">Welcome Back!</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to place orders or manage your karinderya.</p>
    </div>

    <!-- Login Form Card -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-100 sm:px-8">
            <form wire:submit="login" class="space-y-5">

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Email Address
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <input wire:model="email" id="email" type="email" autocomplete="email" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                            placeholder="juan@example.com">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password"
                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                            Password
                        </label>
                    </div>
                    <div class="relative rounded-xl shadow-sm">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input wire:model="password" id="password" type="password" autocomplete="current-password"
                            required
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                            placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Remember Me Options -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="remember" type="checkbox"
                            class="w-4 h-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500">
                        <span class="text-xs text-slate-600 font-medium">Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                        class="w-full inline-flex justify-center items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-xl shadow-md shadow-amber-200 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">
                            <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                        </span>
                        <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Authenticating...
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500">
                    Don't have an account?
                    <a href="{{ route('register') }}"
                        class="font-bold text-amber-600 hover:text-amber-700 transition-colors">
                        Sign up now
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>