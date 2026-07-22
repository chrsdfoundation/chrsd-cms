<?php

namespace Database\Seeders;

use App\Models\BeneficiaryProfile;
use App\Models\DonorProfile;
use App\Models\Organization;
use App\Models\Person;
use App\Models\VolunteerProfile;
use Illuminate\Database\Seeder;

/**
 * Baseline test people for the CRM cluster. Ensures /admin/crm/people/1 loads
 * out of the box after a fresh migrate + seed.
 *
 * Why this exists: Person rows created outside a request context (seeders,
 * tinker, migrations) don't get their `organization_id` auto-stamped by
 * App\Concerns\BelongsToOrganization — that trait only stamps when
 * `session('current_organization_id')` is set. A NULL organization_id then
 * makes the record invisible to the admin panel, which scopes to the current
 * org. Every row created here sets `organization_id` explicitly.
 */
class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = Organization::where('code', 'CHRSD')->value('id');

        if (! $orgId) {
            $this->command?->warn('CrmDemoSeeder: no CHRSD organization found — skipping.');
            return;
        }

        // Backfill anything that predates this seeder (e.g. rows created
        // manually via the admin during dev).
        Person::withoutGlobalScope('organization')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);

        $people = [
            [
                'full_name'    => 'Fatima Islam',
                'email'        => 'fatima@example.com',
                'phone'        => '+8801711223344',
                'city'         => 'Dhaka',
                'notes'        => 'Demo donor — placeholder record for CRM dev.',
                '_role'        => 'donor',
            ],
            [
                'full_name'    => 'Abdul Jalil',
                'email'        => null,
                'phone'        => '+8801755667788',
                'city'         => 'Sonaimuri',
                'notes'        => 'Demo beneficiary — placeholder record for CRM dev.',
                '_role'        => 'beneficiary',
            ],
            [
                'full_name'    => 'Nusrat Chowdhury',
                'email'        => 'nusrat@example.com',
                'phone'        => '+8801799889900',
                'city'         => 'Chattogram',
                'notes'        => 'Demo volunteer — placeholder record for CRM dev.',
                '_role'        => 'volunteer',
            ],
        ];

        foreach ($people as $data) {
            $role = $data['_role'];
            unset($data['_role']);

            $person = Person::withoutGlobalScope('organization')->updateOrCreate(
                ['full_name' => $data['full_name']],
                array_merge($data, [
                    'organization_id' => $orgId,
                    'country'         => 'Bangladesh',
                    'is_active'       => true,
                ]),
            );

            match ($role) {
                'donor'       => DonorProfile::firstOrCreate(['person_id' => $person->id]),
                'volunteer'   => VolunteerProfile::firstOrCreate(['person_id' => $person->id]),
                'beneficiary' => BeneficiaryProfile::firstOrCreate(['person_id' => $person->id]),
            };
        }

        $this->command?->info('CrmDemoSeeder: seeded ' . count($people) . ' demo people (all attached to CHRSD org).');
    }
}
