<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([GrantPrincipal::Role->value => 'roles', GrantPrincipal::User->value => 'admins'] as $type => $table) {
            DB::table('mdm_access_grants')
                ->where('principal_type', $type)
                ->whereNotExists(fn (Builder $principal) => $principal
                    ->selectRaw('1')
                    ->from($table)
                    ->whereColumn($table.'.id', 'mdm_access_grants.principal_id'))
                ->delete();
        }
    }

    public function down(): void {}
};
