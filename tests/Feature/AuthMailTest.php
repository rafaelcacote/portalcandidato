<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

test('password reset email uses proensp logo template', function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        expect($mail->markdown)->toBe('emails.auth-mail')
            ->and($mail->viewData['logoUrl'])->toContain('logo_proensp_email.png');

        return true;
    });
});

test('email verification notification uses proensp logo template', function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());

    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        expect($mail->markdown)->toBe('emails.auth-mail')
            ->and($mail->viewData['logoUrl'])->toContain('logo_proensp_email.png');

        return true;
    });
});
