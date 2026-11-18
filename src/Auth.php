<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('Please sign in first.', 'error');
            redirect('admin/login');
        }
    }

    public static function hasUsers(): bool
    {
        return (bool) Database::scalar('SELECT 1 FROM users LIMIT 1');
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE username = ?', [$username]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => (string) ($user['email'] ?? ''),
        ];
        return true;
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        Database::execute('UPDATE users SET name = ?, email = ? WHERE id = ?', [$name, $email, $id]);
        $user = self::user();
        if ($user && (int) $user['id'] === $id) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        }
    }

    /** Returns an error message, or an empty string on success. */
    public static function changePassword(int $id, string $current, string $new, string $confirm): string
    {
        if ($new !== $confirm) {
            return 'New passwords do not match.';
        }
        if (mb_strlen($new) < 8) {
            return 'New password must be at least 8 characters.';
        }
        $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$id]);
        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            return 'Current password is incorrect.';
        }
        Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $id]);
        return '';
    }

    public static function createFirstUser(string $username, string $password, string $name): bool
    {
        if (self::hasUsers()) {
            return false;
        }
        Database::execute(
            'INSERT INTO users (username, password_hash, name, created_at) VALUES (?, ?, ?, ?)',
            [$username, password_hash($password, PASSWORD_DEFAULT), $name, date('Y-m-d H:i:s')]
        );
        return true;
    }

    public static function turnstileSiteKey(): string
    {
        return Env::get('TURNSTILE_SITE_KEY', '');
    }

    public static function turnstileEnabled(): bool
    {
        return self::turnstileSiteKey() !== '' && Env::get('TURNSTILE_SECRET_KEY', '') !== '';
    }

    public static function verifyTurnstile(string $token): bool
    {
        $secret = Env::get('TURNSTILE_SECRET_KEY', '');
        if ($secret === '' || $token === '') {
            return false;
        }
        $payload = http_build_query(['secret' => $secret, 'response' => $token]);
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            if (!is_string($body)) {
                return false;
            }
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
                'ignore_errors' => true,
            ]]);
            $body = file_get_contents($url, false, $context);
            if ($body === false) {
                return false;
            }
        }
        $data = json_decode($body, true);
        return is_array($data) && !empty($data['success']);
    }

    public static function loginLocked(string $username): bool
    {
        $entry = $_SESSION['login_attempts'][$username] ?? null;
        if (!is_array($entry)) {
            return false;
        }
        $lockout = Env::int('LOGIN_LOCKOUT_SECONDS', 300);
        if (time() - (int) ($entry['first'] ?? 0) >= $lockout) {
            unset($_SESSION['login_attempts'][$username]);
            return false;
        }
        return (int) ($entry['count'] ?? 0) >= Env::int('LOGIN_MAX_ATTEMPTS', 5);
    }

    public static function recordFailedLogin(string $username): void
    {
        $entry = $_SESSION['login_attempts'][$username] ?? null;
        if (!is_array($entry) || time() - (int) ($entry['first'] ?? 0) >= Env::int('LOGIN_LOCKOUT_SECONDS', 300)) {
            $entry = ['first' => time(), 'count' => 0];
        }
        $entry['count'] = (int) $entry['count'] + 1;
        $_SESSION['login_attempts'][$username] = $entry;
    }

    public static function clearLoginAttempts(string $username): void
    {
        unset($_SESSION['login_attempts'][$username]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
