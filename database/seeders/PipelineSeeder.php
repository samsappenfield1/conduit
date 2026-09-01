<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Pipeline;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated', 'paying', 'churned'],
        ]);

        $accounts = [
            [
                'name' => 'Acme Widgets',
                'current_stage' => 'signed_up',
                'contacts' => [
                    ['name' => 'Jordan Lee', 'email' => 'jordan@acmewidgets.example'],
                ],
            ],
            [
                'name' => 'Bright Ideas Co',
                'current_stage' => 'activated',
                'contacts' => [
                    ['name' => 'Priya Shah', 'email' => 'priya@brightideas.example'],
                    ['name' => 'Sam Okafor', 'email' => 'sam@brightideas.example'],
                ],
            ],
            [
                'name' => 'Cascade Analytics',
                'current_stage' => 'paying',
                'contacts' => [
                    ['name' => 'Morgan Reyes', 'email' => 'morgan@cascadeanalytics.example'],
                ],
            ],
            [
                'name' => 'Dune Robotics',
                'current_stage' => 'paying',
                'contacts' => [
                    ['name' => 'Alex Kim', 'email' => 'alex@dunerobotics.example'],
                    ['name' => 'Taylor Brooks', 'email' => 'taylor@dunerobotics.example'],
                ],
            ],
            [
                'name' => 'Elm Street Bakery',
                'current_stage' => 'churned',
                'contacts' => [
                    ['name' => 'Casey Nguyen', 'email' => 'casey@elmstreetbakery.example'],
                ],
            ],
        ];

        foreach ($accounts as $accountData) {
            $account = Account::create([
                'pipeline_id' => $pipeline->id,
                'name' => $accountData['name'],
                'current_stage' => $accountData['current_stage'],
            ]);

            foreach ($accountData['contacts'] as $contact) {
                $account->contacts()->create([
                    'name' => $contact['name'],
                    'email' => $contact['email'],
                ]);
            }
        }
    }
}
