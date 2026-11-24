<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\InputGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(): View|RedirectResponse
    {
        return User::query()->exists() ? view('auth.login') : redirect()->route('setup');
    }

    public function setup(): View|RedirectResponse
    {
        return User::query()->exists() ? redirect()->route('login') : view('auth.setup');
    }

    public function createFirstAdmin(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $validated['name'] = InputGuard::normalizePersonName($validated['name'], 'name');
        $admin = User::query()->create($validated + [
            'role_id' => Role::ensureDefaultAdminId(),
            'job_title' => 'Administrador principal',
            'active' => true,
            'last_login_at' => now(),
        ]);
        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'El correo o la contraseña no son correctos.'])->onlyInput('email');
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Esta cuenta está desactivada. Contacta al administrador principal.'])->onlyInput('email');
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route($user->firstAllowedAdminRoute()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function googleRedirect(): RedirectResponse
    {
        abort_unless(config('services.google.client_id') && config('services.google.client_secret'), 503, 'Configura Google OAuth en .env para habilitar este acceso.');

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        abort_unless(config('services.google.client_id') && config('services.google.client_secret'), 503, 'Google OAuth todavía no está configurado.');
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();
        $admin = $email ? User::query()->where('email', $email)->first() : null;

        if (! $admin) {
            return redirect()->route('login')->withErrors(['email' => 'Esta cuenta de Google no está autorizada como administradora.']);
        }

        if (! $admin->isActive()) {
            return redirect()->route('login')->withErrors(['email' => 'Esta cuenta está desactivada. Contacta al administrador principal.']);
        }

        $admin->forceFill(['last_login_at' => now()])->save();
        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->intended(route($admin->firstAllowedAdminRoute()));
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Espera un minuto antes de solicitar otro enlace.']);
        }

        return back()->with('status', 'Si el correo está asociado a una cuenta administradora, recibirás un enlace de recuperación.');
    }

    public function resetPasswordForm(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'El enlace de recuperación no es válido o ya venció.',
            Password::RESET_THROTTLED => 'Espera un minuto antes de intentarlo nuevamente.',
            default => 'No se pudo restablecer la contraseña. Verifica los datos e inténtalo otra vez.',
        };

        return back()->withErrors(['email' => $message]);
    }
}
