<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-api-token {email : Email address of the user to generate a token for} {--name=api : A name for the token}')]
#[Description('Generate a Sanctum API token for a user')]
class GenerateApiToken extends Command
{
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'));

        $this->info('Token generated. Copy it now — it will not be shown again:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
