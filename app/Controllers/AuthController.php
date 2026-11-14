<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Core\RateLimiter;
use ChallengeChain\Models\PasswordReset;
use ChallengeChain\Models\User;
use ChallengeChain\Services\GamificationService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->requireGuest();
        $this->view('auth/login', [
            'title' => 'Sign in — ChallengeChain',
            'user' => null,
        ], 'auth');
    }

    public function login(): void
    {
        $this->requireGuest();
        $this->verifyCsrf();

        $email = $this->str('email', '', 190);
        $password = (string) ($this->input()['password'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($password === '') {
            $errors[] = 'Enter your password.';
        }

        if ($errors !== []) {
            keep_old(['email' => $email]);
            $this->view('auth/login', ['title' => 'Sign in — ChallengeChain', 'errors' => $errors, 'user' => null], 'auth');
            return;
        }

        try {
            $user = Auth::attempt($email, $password);
        } catch (HttpException $e) {
            $this->view('auth/login', [
                'title' => 'Sign in — ChallengeChain',
                'errors' => [$e->getMessage()],
                'user' => null,
            ], 'auth');
            return;
        }

        if ($user === null) {
            keep_old(['email' => $email]);
            $this->view('auth/login', [
                'title' => 'Sign in — ChallengeChain',
                'errors' => ['Invalid email or password.'],
                'user' => null,
            ], 'auth');
            return;
        }

        clear_old();
        set_flash('success', 'Welcome back, ' . $user['display_name'] . '!');
        $next = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login']);
        redirect(is_string($next) && str_starts_with($next, '/') ? $next : '/dashboard');
    }

    public function showRegister(): void
    {
        $this->requireGuest();
        $this->view('auth/register', [
            'title' => 'Create account — ChallengeChain',
            'user' => null,
        ], 'auth');
    }

    public function register(): void
    {
        $this->requireGuest();
        $this->verifyCsrf();

        $username = $this->str('username', '', 30);
        $email = $this->str('email', '', 190);
        $displayName = $this->str('display_name', '', 60);
        $password = (string) ($this->input()['password'] ?? '');
        $confirm = (string) ($this->input()['password_confirm'] ?? '');

        $errors = [];
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore).';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if ($errors === []) {
            if (User::usernameExists($username)) {
                $errors[] = 'That username is already taken.';
            } elseif (User::emailExists($email)) {
                $errors[] = 'An account with that email already exists.';
            }
        }

        if ($errors !== []) {
            keep_old(['username' => $username, 'email' => $email, 'display_name' => $displayName]);
            $this->view('auth/register', [
                'title' => 'Create account — ChallengeChain',
                'errors' => $errors,
                'user' => null,
            ], 'auth');
            return;
        }

        $user = User::create($username, $email, $password, $displayName !== '' ? $displayName : $username);
        GamificationService::awardXp((int) $user['id'], 0, 'signup');
        Auth::login($user);
        clear_old();
        set_flash('success', 'Account created — welcome to ChallengeChain!');
        redirect('/create');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        set_flash('success', 'You have been signed out.');
        redirect('/');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', [
            'title' => 'Reset password — ChallengeChain',
            'user' => Auth::user(),
        ], 'auth');
    }

    public function forgot(): void
    {
        $this->verifyCsrf();
        $email = $this->str('email', '', 190);

        $generic = ['success' => 'If that email exists, a reset link has been sent.'];

        $user = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? User::findByEmail($email) : null;
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            PasswordReset::create((int) $user['id'], $token);
            $link = base_url('/reset-password/' . $token);

            $sent = false;
            if ((bool) config('mail.enabled', false)) {
                $sent = @mail(
                    $user['email'],
                    'Reset your ChallengeChain password',
                    "Hello {$user['display_name']},\n\nReset your password here (valid 1 hour):\n{$link}\n\nIf you did not request this, ignore this email.\n",
                    'From: ' . config('mail.from') . "\r\nContent-Type: text/plain; charset=UTF-8"
                );
            }
            if (!$sent && (bool) config('app.debug')) {
                $this->view('auth/forgot', [
                    'title' => 'Reset password — ChallengeChain',
                    'user' => Auth::user(),
                    'debugLink' => $link,
                    'message' => 'Mail delivery is not configured — use this link (debug mode):',
                ], 'auth');
                return;
            }
            if (!$sent) {
                log_message('warn', 'Password reset mail failed for user ' . $user['id']);
            }
        }

        $this->view('auth/forgot', [
            'title' => 'Reset password — ChallengeChain',
            'user' => Auth::user(),
            'message' => $generic['success'],
        ], 'auth');
    }

    public function showReset(string $token): void
    {
        $row = PasswordReset::validToken($token);
        if ($row === null) {
            $this->view('auth/reset', [
                'title' => 'Reset password — ChallengeChain',
                'user' => Auth::user(),
                'errors' => ['This reset link is invalid or has expired.'],
                'token' => $token,
                'invalid' => true,
            ], 'auth');
            return;
        }
        $this->view('auth/reset', [
            'title' => 'Reset password — ChallengeChain',
            'user' => Auth::user(),
            'token' => $token,
            'errors' => [],
        ], 'auth');
    }

    public function reset(string $token): void
    {
        $this->verifyCsrf();
        $row = PasswordReset::validToken($token);
        $password = (string) ($this->input()['password'] ?? '');
        $confirm = (string) ($this->input()['password_confirm'] ?? '');

        $errors = [];
        if ($row === null) {
            $errors[] = 'This reset link is invalid or has expired.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            $this->view('auth/reset', [
                'title' => 'Reset password — ChallengeChain',
                'user' => Auth::user(),
                'token' => $token,
                'errors' => $errors,
                'invalid' => $row === null,
            ], 'auth');
            return;
        }

        User::updatePassword((int) $row['user_id'], $password);
        PasswordReset::markUsed((int) $row['id']);
        RateLimiter::reset('login:' . ip_hash() . '|');

        set_flash('success', 'Password updated — sign in with your new password.');
        redirect('/login');
    }
}
