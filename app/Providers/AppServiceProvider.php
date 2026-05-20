<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureEmailVerification();
        $this->configurePasswordReset();
    }

    protected function configurePasswordReset(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return $this->authMailMessage()
                ->subject('Redefinir senha — '.config('app.name'))
                ->greeting('Olá!')
                ->line('Recebemos uma solicitação para redefinir a senha da sua conta no '.config('app.name').'.')
                ->action('Redefinir senha', $url)
                ->line('Este link expira em '.config('auth.passwords.users.expire').' minutos.')
                ->line('Se você não solicitou a redefinição, ignore este e-mail — sua senha permanecerá a mesma.')
                ->salutation('Atenciosamente, equipe '.config('app.name'));
        });
    }

    protected function configureEmailVerification(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return $this->authMailMessage()
                ->subject('Confirme seu e-mail — '.config('app.name'))
                ->greeting('Olá!')
                ->line('Obrigado por se cadastrar no '.config('app.name').'.')
                ->line('Para confirmar que este endereço de e-mail é seu e liberar o acesso ao portal, clique no botão abaixo.')
                ->action('Confirmar e-mail', $url)
                ->line('Se você não criou uma conta, ignore este e-mail.')
                ->salutation('Atenciosamente, equipe '.config('app.name'));
        });
    }

    protected function authMailMessage(): MailMessage
    {
        return (new MailMessage)->markdown('emails.auth-mail', [
            'logoUrl' => url('img/logo_proensp_email.png'),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
