<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $showPassword = false;
    public bool $showConfirmPassword = false;

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:'.User::class, 'regex:/^[a-zA-Z0-9_.]+$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Tambahkan field yang diperlukan
        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = 2; // Default sebagai petugas
        $validated['status'] = 'aktif'; // Sesuai dengan database Anda

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(RouteServiceProvider::HOME, navigate: true);
    }
    
    /**
     * Toggle password visibility
     */
    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }
    
    /**
     * Toggle confirm password visibility
     */
    public function toggleConfirmPasswordVisibility(): void
    {
        $this->showConfirmPassword = !$this->showConfirmPassword;
    }
}; ?>

<!-- Main Container -->
<main class="w-full max-w-[440px] flex flex-col items-center">
    <!-- Register Card -->
    <div class="w-full bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 p-8 sm:p-10 flex flex-col gap-8">
        <!-- Header Section -->
        <div class="flex flex-col items-center text-center gap-4">
            <!-- Icon -->
            <div class="h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-600 dark:text-yellow-400 mb-2">
                <span class="material-symbols-outlined text-[32px]">person_add</span>
            </div>
            
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Buat Akun Baru
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Daftar untuk mengakses sistem parkir
                </p>
            </div>
        </div>

        <!-- Form Section -->
        <form wire:submit="register" class="flex flex-col gap-5 w-full">
            <!-- Full Name Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">
                    Nama Lengkap
                </label>
                <div class="relative">
                    <input wire:model="name"
                           id="name"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 
                                  bg-transparent text-gray-900 dark:text-white 
                                  placeholder:text-gray-400 dark:placeholder:text-gray-500 
                                  focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 
                                  sm:text-sm sm:leading-6 h-11 px-3"
                           type="text"
                           name="name"
                           required
                           autofocus
                           autocomplete="name"
                           placeholder="Masukkan nama lengkap" />
                    
                    @error('name')
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <span class="material-symbols-outlined text-red-500 text-sm">error</span>
                        </div>
                    @enderror
                </div>
                
                @error('name')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="username">
                    Username
                </label>
                <div class="relative">
                    <input wire:model="username"
                           id="username"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 
                                  bg-transparent text-gray-900 dark:text-white 
                                  placeholder:text-gray-400 dark:placeholder:text-gray-500 
                                  focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 
                                  sm:text-sm sm:leading-6 h-11 px-3"
                           type="text"
                           name="username"
                           required
                           autocomplete="username"
                           placeholder="Pilih username" />
                    
                    @error('username')
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <span class="material-symbols-outlined text-red-500 text-sm">error</span>
                        </div>
                    @enderror
                </div>
                
                @error('username')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Username minimal 3 karakter, hanya boleh huruf, angka, underscore (_), dan titik (.)
                    </p>
                @enderror
            </div>

            <!-- Password Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="password">
                    Password
                </label>
                
                <div class="relative">
                    <input wire:model="password"
                           id="password"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 
                                  bg-transparent text-gray-900 dark:text-white 
                                  placeholder:text-gray-400 dark:placeholder:text-gray-500 
                                  focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 
                                  sm:text-sm sm:leading-6 h-11 px-3 pr-10"
                           :type="{{ $showPassword ? "'text'" : "'password'" }}"
                           name="password"
                           required
                           autocomplete="new-password"
                           placeholder="Buat password" />
                    
                    <button type="button"
                            wire:click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <span class="material-symbols-outlined text-[20px]">
                            {{ $showPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>
                
                @error('password')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Password minimal 8 karakter
                    </p>
                @enderror
            </div>

            <!-- Confirm Password Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="password_confirmation">
                    Konfirmasi Password
                </label>
                
                <div class="relative">
                    <input wire:model="password_confirmation"
                           id="password_confirmation"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 
                                  bg-transparent text-gray-900 dark:text-white 
                                  placeholder:text-gray-400 dark:placeholder:text-gray-500 
                                  focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 
                                  sm:text-sm sm:leading-6 h-11 px-3 pr-10"
                           :type="{{ $showConfirmPassword ? "'text'" : "'password'" }}"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           placeholder="Ulangi password" />
                    
                    <button type="button"
                            wire:click="toggleConfirmPasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <span class="material-symbols-outlined text-[20px]">
                            {{ $showConfirmPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>
                
                @error('password_confirmation')
                    <p class="text-sm text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Information Box -->
            <div class="mt-2 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    <strong class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">info</span>
                        Catatan:
                    </strong> 
                    Setiap pendaftaran baru akan otomatis menjadi <strong class="text-blue-900 dark:text-blue-200">Petugas Parkir</strong>. 
                    Hubungi Administrator untuk perubahan role.
                </p>
            </div>

            <!-- Submit Button -->
            <button class="mt-2 flex w-full justify-center items-center rounded-lg 
                          bg-yellow-500 hover:bg-yellow-600 px-3 py-3 text-sm font-semibold 
                          leading-6 text-black shadow-sm focus-visible:outline focus-visible:outline-2 
                          focus-visible:outline-offset-2 focus-visible:outline-yellow-500 
                          transition-colors duration-200"
                    type="submit">
                Daftar
            </button>
        </form>
        
        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mt-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                <ul class="text-sm text-red-700 dark:text-red-300">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">error</span>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Footer -->
        <div class="text-center pt-4 border-t border-gray-200 dark:border-gray-800">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" 
                   wire:navigate
                   class="font-medium text-yellow-600 dark:text-yellow-400 
                          hover:text-yellow-500 dark:hover:text-yellow-300 
                          transition-colors ml-1">
                    Masuk disini
                </a>
            </p>
            
            <p class="text-xs text-gray-400 dark:text-gray-600 mt-4">
                © {{ date('Y') }} Parking Systems Inc. 
                <a class="underline hover:text-gray-500 dark:hover:text-gray-400 ml-1" href="#">
                    Kebijakan Privasi
                </a>
            </p>
        </div>
    </div>
</main>

<script>
// Password visibility toggles
document.addEventListener('livewire:init', () => {
    Livewire.on('password-toggled', (show) => {
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.type = show ? 'text' : 'password';
        }
    });
    
    Livewire.on('confirm-password-toggled', (show) => {
        const confirmInput = document.getElementById('password_confirmation');
        if (confirmInput) {
            confirmInput.type = show ? 'text' : 'password';
        }
    });
});
</script>