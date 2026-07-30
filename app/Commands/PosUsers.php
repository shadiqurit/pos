<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use RuntimeException;

final class PosUsers extends BaseCommand
{
    protected $group       = 'POS';
    protected $name        = 'pos:users';
    protected $description = 'Lists POS users and optionally resets one user password.';
    protected $usage       = 'pos:users [username] [new-password]';
    protected $arguments   = [
        'username'     => 'Username or email to update.',
        'new-password' => 'New password. Omit both arguments to list users.',
    ];

    public function run(array $params): void
    {
        $database = Database::connect();

        if ($params === []) {
            $rows = $database->query(
                'SELECT u.id, u.username, u.email, u.role_id, r.role_name, u.status, u.store_id
                   FROM db_users u
              LEFT JOIN db_roles r ON r.id = u.role_id
               ORDER BY u.id',
            )->getResultArray();

            CLI::table($rows, ['id', 'username', 'email', 'role_id', 'role_name', 'status', 'store_id']);

            return;
        }

        $identity = (string) ($params[0] ?? '');
        $password = (string) ($params[1] ?? '');
        if ($identity === '' || $password === '') {
            throw new RuntimeException('Both username and new password are required.');
        }
        if (strlen($password) < 10) {
            throw new RuntimeException('The new password must contain at least 10 characters.');
        }

        $builder = $database->table('db_users');
        $user    = $builder
            ->groupStart()
                ->where('username', $identity)
                ->orWhere('email', $identity)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if ($user === null) {
            throw new RuntimeException('User not found: ' . $identity);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $builder->where('id', $user['id'])->update([
            'password'   => $hash,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $storedHash = (string) $database->table('db_users')
            ->select('password')
            ->where('id', $user['id'])
            ->get()
            ->getRow('password');
        if (! password_verify($password, $storedHash)) {
            throw new RuntimeException('The password update could not be verified.');
        }

        CLI::write(
            sprintf('Password updated and verified for user #%d (%s).', $user['id'], $user['username']),
            'green',
        );
    }
}
