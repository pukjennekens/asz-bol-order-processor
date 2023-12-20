<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maak nieuw admin gebruikersaccount aan om in te loggen op de applicatie';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Maak nieuw admin gebruikersaccount aan om in te loggen op de applicatie');
        $this->info('Vul de volgende gegevens in:');

        $name     = $this->ask('Naam');
        $email    = $this->ask('E-mailadres');
        $password = $this->secret('Wachtwoord');

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => bcrypt($password),
        ]);

        $this->info('Gebruiker ' . $user->name . ' (' . $user->email . ') is aangemaakt.');
    }
}
