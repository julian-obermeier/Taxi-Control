<?php

namespace App\Services;

use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TripNumberService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function next(?int $year = null): string
    {
        $tenantId = $this->context->requireId();
        $year ??= (int) now()->format('Y');

        if ($year < 2000 || $year > 9999) {
            throw new RuntimeException('Ungültiges Jahr für die Auftragsnummer.');
        }

        return DB::transaction(function () use ($tenantId, $year): string {
            DB::table('trip_number_sequences')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('trip_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException('Auftragsnummern-Sequenz konnte nicht geladen werden.');
            }

            $next = (int) $sequence->last_number + 1;
            if ($next > 99999) {
                throw new RuntimeException('Auftragsnummern für dieses Jahr sind ausgeschöpft.');
            }

            DB::table('trip_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return sprintf('%04d%05d', $year, $next);
        }, 3);
    }
}
