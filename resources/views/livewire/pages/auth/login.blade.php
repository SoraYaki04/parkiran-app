<?php

use App\Livewire\Forms\LoginForm;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public bool $showPassword = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);
    }
    
    /**
     * Toggle password visibility
     */
    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }
}; ?>

<!-- Main Container -->

<main class="w-full max-w-[440px] bg-white dark:bg-card-dark rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden relative">
    <div class="h-1.5 w-full bg-primary"></div>
    <!-- Login Card -->
    <div class="card-dark p-8 sm:p-10 min-w-[440px] flex flex-col gap-8">
        <!-- Header Section -->
        <div class="flex flex-col items-center text-center gap-4">
            <!-- Icon Placeholder: Minimalist Car Silhouette -->
            <div class="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-2">
                <span class="material-symbols-outlined text-[32px]">directions_car</span>
            </div>
            
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Parkiran App
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Login untuk menggunakan aplikasi
                </p>
            </div>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <!-- Form Section -->
        <form wire:submit="login" class="flex flex-col gap-5 w-full">
            <!-- Username Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="username">
                    Username
                </label>
                <div class="relative">
                    <input wire:model="form.username"
                           id="username"
                           class="form-input-custom"
                           type="text"
                           name="username"
                           required
                           autofocus
                           autocomplete="username"
                           placeholder="Masukkan Username" />
                    
                    @error('form.username')
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <span class="material-symbols-outlined text-red-500 text-sm">error</span>
                        </div>
                    @enderror
                </div>
                
                @error('form.username')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Input -->
            <div class="space-y-1.5">
                <div class="flex justify-between items-center">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="password">
                        Password
                    </label>
                </div>
                
                <div class="relative">
                    <input wire:model="form.password"
                           id="password"
                           class="form-input-custom pr-10"
                           :type="{{ $showPassword ? "'text'" : "'password'" }}"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="Masukkan Password" />
                    
                    <button type="button"
                            wire:click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <span class="material-symbols-outlined text-[20px]">
                            {{ $showPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>
                
                @error('form.password')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <input wire:model="form.remember"
                           id="remember-me"
                           class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 
                                  bg-transparent text-primary focus:ring-primary 
                                  focus:ring-offset-0 dark:focus:ring-offset-gray-900"
                           type="checkbox" />
                    <label class="text-sm text-gray-600 dark:text-gray-400 select-none cursor-pointer" 
                           for="remember-me">
                        Remember me
                    </label>
                </div>
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-primary hover:text-primary-400 transition-colors" 
                       href="{{ route('password.request') }}" 
                       wire:navigate>
                        Forgot Password?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <button class="btn-primary-custom mt-2" type="submit">
                Login
            </button>

            <div class="text-center">
                <p class="text-s text-gray-400 dark:text-gray-600">
                    Belum punya akun?
                    <a class="underline text-primary hover:text-primary-400 transition-colors" 
                    href="{{ route('register') }}" 
                    wire:navigate>
                        Register
                    </a>
                </p>
            </div>

        </form>
        
        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mt-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                <ul class="text-sm text-red-700 dark:text-red-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Footer -->
        <div class="text-center pt-4 border-t border-gray-200 dark:border-gray-800">
            <p class="text-xs text-gray-400 dark:text-gray-600">
                © {{ date('Y') }} Parkiran Langit 
                <a class="underline hover:text-gray-500 dark:hover:text-gray-400 ml-1" href="https://github.com/SoraYaki04/parkiran-app">
                    Contact Support
                </a>
            </p>
        </div>
    </div>
</main>

<script>
// Toggle password visibility functionality
document.addEventListener('livewire:init', () => {
    Livewire.on('password-toggled', (show) => {
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.type = show ? 'text' : 'password';
        }
    });
});
</script>