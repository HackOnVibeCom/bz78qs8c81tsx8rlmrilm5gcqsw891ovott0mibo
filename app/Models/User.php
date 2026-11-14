<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = :email', ['email' => mb_strtolower(trim($email))]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE username = :username', ['username' => $username]);
    }

    public static function findByReferralCode(string $code): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE referral_code = :code', ['code' => $code]);
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    public static function usernameExists(string $username): bool
    {
        return self::findByUsername($username) !== null;
    }

    public static function create(string $username, string $email, string $password, string $displayName): array
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $referral = self::uniqueReferralCode();

        $id = Database::insert('users', [
            'username' => $username,
            'email' => mb_strtolower($email),
            'password_hash' => $hash,
            'display_name' => $displayName !== '' ? $displayName : $username,
            'referral_code' => $referral,
        ]);

        $user = self::find($id);
        if ($user === null) {
            throw new \RuntimeException('Failed to create user.');
        }
        return $user;
    }

    public static function updateProfile(int $id, string $displayName, string $bio): void
    {
        Database::query(
            'UPDATE users SET display_name = :display_name, bio = :bio, updated_at = datetime(\'now\') WHERE id = :id',
            [
                'display_name' => mb_substr(trim($displayName), 0, 60),
                'bio' => mb_substr(trim($bio), 0, 300),
                'id' => $id,
            ]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::query(
            'UPDATE users SET password_hash = :hash, updated_at = datetime(\'now\') WHERE id = :id',
            ['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]
        );
    }

    public static function addXp(int $id, int $xp): void
    {
        Database::query(
            'UPDATE users SET xp = xp + :xp, updated_at = datetime(\'now\') WHERE id = :id',
            ['xp' => max(0, $xp), 'id' => $id]
        );
    }

    private static function uniqueReferralCode(): string
    {
        for ($i = 0; $i < 25; $i++) {
            $code = random_code(8);
            if (self::findByReferralCode($code) === null) {
                return $code;
            }
        }
        return random_code(12) . strtoupper(random_code(4));
    }
}
