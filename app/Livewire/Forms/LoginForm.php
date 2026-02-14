<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|min:3')]
    public string $username = '';

    #[Validate('required|string|min:6')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /* ======================
        ACTIVITY LOGGER
    =======================*/
    private function logActivity(
        string $action,
        string $description,
        string $target = null,
        string $category = 'AUTH'
    ) {
        ActivityLog::log(
            action: $action,
            description: $description,
            target: $target,
            category: $category,
        );
    }

    public function authenticate(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        // Cek apakah user ada
        $user = User::where('username', $this->username)->first();

        if (!$user) {
            RateLimiter::hit($this->throttleKey());

            // Log username tidak ditemukan
            $this->logActivity(
                'LOGIN_FAILED',
                'Username tidak ditemukan',
                $this->username
            );

            throw ValidationException::withMessages([
                'username' => 'Username tidak ditemukan.',
            ]);
        }

        // Cek apakah user aktif
        if ($user->status !== 'aktif') {
            RateLimiter::hit($this->throttleKey());

            // Log akun tidak aktif
            $this->logActivity(
                'LOGIN_FAILED',
                'Akun tidak aktif',
                $user->username
            );

            throw ValidationException::withMessages([
                'username' => 'Akun tidak aktif. Hubungi administrator.',
            ]);
        }

        // Attempt login
        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        if (!Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            // Log password salah
            $this->logActivity(
                'LOGIN_FAILED',
                'Password salah',
                $user->username
            );

            throw ValidationException::withMessages([
                'password' => 'Password salah.',
            ]);
        }

        // Login sukses
        RateLimiter::clear($this->throttleKey());

        // Log login berhasil
        $this->logActivity(
            'LOGIN_SUCCESS',
            'User berhasil login',
            $user->username
        );
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Log lockout
        $this->logActivity(
            'LOCKOUT',
            'Terlalu banyak percobaan login',
            $this->username
        );

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username) . '|' . request()->ip());
    }
}
?>
