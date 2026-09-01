<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Account;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_another_user(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Jamie Rivera',
                'email' => 'jamie@example.com',
                'password' => 'super-secret',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
        ]);

        $this->assertTrue(Hash::check('super-secret', User::where('email', 'jamie@example.com')->value('password')));
    }

    public function test_editing_a_user_without_a_new_password_keeps_the_existing_one(): void
    {
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();
        $originalPassword = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $user->email,
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($originalPassword, $user->refresh()->password);
        $this->assertSame('Updated Name', $user->name);
    }

    public function test_account_owner_can_be_assigned_from_the_account_edit_form(): void
    {
        $this->actingAs(User::factory()->create());

        $owner = User::factory()->create();
        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);
        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['owner_id' => $owner->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($owner->id, $account->refresh()->owner_id);
    }
}
