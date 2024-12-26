<?php

namespace Xoshbin\JmeryarAccounting\Console;

use Illuminate\Console\Command;

class JmeryarAccountingInstallCommand extends Command
{
    protected $signature = 'jmeryaraccounting:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Jmeryar package migrations and seed the database using FullSeeder';

    public function handle()
    {
        $this->info('Running migrations...');
        $this->call('migrate');

        $this->info('Seeding the database with FullSeeder...');
        $this->call('db:seed', [
            '--class' => 'Xoshbin\JmeryarAccounting\Database\Seeders\FullSeeder',
        ]);

        $this->info('Package installation complete!');

        return Command::SUCCESS;
    }
}
