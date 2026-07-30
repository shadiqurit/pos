<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use RuntimeException;

final class PosRepair extends BaseCommand
{
    protected $group       = 'POS';
    protected $name        = 'pos:repair-superadmin';
    protected $description = 'Repairs the superadmin store currency reference required by the dashboard.';

    public function run(array $params): void
    {
        $database = Database::connect();
        $user     = $database->table('db_users')
            ->select('id, store_id')
            ->where('username', 'superadmin')
            ->get()
            ->getRowArray();

        if ($user === null) {
            throw new RuntimeException('The superadmin user does not exist.');
        }

        $store = $database->table('db_store')
            ->select('id, currency_id')
            ->where('id', $user['store_id'])
            ->get()
            ->getRowArray();

        if ($store === null) {
            throw new RuntimeException('The superadmin store does not exist.');
        }

        $currencyExists = $database->table('db_currency')
            ->where('id', $store['currency_id'])
            ->countAllResults() === 1;

        if (! $currencyExists) {
            $currency = $database->table('db_currency')
                ->select('id')
                ->where('status', 1)
                ->orderBy('id')
                ->get()
                ->getRowArray();

            if ($currency === null) {
                throw new RuntimeException('No active currency exists for the superadmin store.');
            }

            $database->table('db_store')
                ->where('id', $store['id'])
                ->update(['currency_id' => $currency['id']]);

            CLI::write(
                sprintf('Store #%d currency repaired: %d.', $store['id'], $currency['id']),
                'yellow',
            );
        }

        CLI::write('Superadmin store configuration verified.', 'green');
    }
}
