<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $accountClass = 'App\Models\Account';

    /**
     * Run the migrations.
     *
     * Domain moves from a hardcoded accounts.domain column to a regular
     * (Text, Account-only) Field, so it behaves like every other custom
     * field going forward. Any existing column values are carried over to
     * the new Field before the column is dropped.
     */
    public function up(): void
    {
        $fieldId = DB::table('fields')->where('entity_type', 'account')->where('name', 'Domain')->value('id');

        if (! $fieldId) {
            $now = now();

            $fieldId = DB::table('fields')->insertGetId([
                'entity_type' => 'account',
                'name' => 'Domain',
                'type' => 'text',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('accounts')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->get(['id', 'domain'])
            ->each(function (object $account) use ($fieldId): void {
                DB::table('field_values')->updateOrInsert(
                    [
                        'field_id' => $fieldId,
                        'customizable_type' => $this->accountClass,
                        'customizable_id' => $account->id,
                    ],
                    [
                        'value' => $account->domain,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('name');
        });

        $fieldId = DB::table('fields')->where('entity_type', 'account')->where('name', 'Domain')->value('id');

        if ($fieldId) {
            DB::table('field_values')
                ->where('field_id', $fieldId)
                ->where('customizable_type', $this->accountClass)
                ->get(['customizable_id', 'value'])
                ->each(function (object $fieldValue): void {
                    DB::table('accounts')->where('id', $fieldValue->customizable_id)->update([
                        'domain' => $fieldValue->value,
                    ]);
                });
        }
    }
};
