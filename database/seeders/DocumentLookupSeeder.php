<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use App\Models\IdCardType;
use App\Models\LetterCategory;
use Illuminate\Database\Seeder;

class DocumentLookupSeeder extends Seeder
{
    public function run(): void
    {
        $certTypes = [
            ['code' => 'COE', 'name' => 'Certificate of Employment',
                'description' => 'General employment certification for banking, immigration, etc.',
                'requires_approval' => true],
            ['code' => 'SR', 'name' => 'Service Record',
                'description' => 'Detailed history of positions held and dates of service.',
                'requires_approval' => true],
            ['code' => 'TRN', 'name' => 'Training Certificate',
                'description' => 'Confirms completion of an internal training programme.',
                'validity_days' => 730, 'requires_approval' => false],
            ['code' => 'RGD', 'name' => 'Certificate of Good Standing',
                'description' => 'Attests to the employee\'s standing at time of issuance.',
                'requires_approval' => true],
        ];

        foreach ($certTypes as $t) {
            CertificateType::updateOrCreate(['code' => $t['code']], $t);
        }

        $letterCategories = [
            ['code' => 'MEMO', 'name' => 'Internal Memorandum'],
            ['code' => 'CIRC', 'name' => 'Circular'],
            ['code' => 'EXT',  'name' => 'External Correspondence'],
            ['code' => 'NTC',  'name' => 'Notice'],
            ['code' => 'AWD',  'name' => 'Award / Recognition'],
        ];

        foreach ($letterCategories as $c) {
            LetterCategory::updateOrCreate(['code' => $c['code']], $c);
        }

        $idCardTypes = [
            ['code' => 'EMP', 'name' => 'Employee ID Card',
                'description' => 'Standard staff identification for full/part-time employees.',
                'default_validity_months' => 24],
            ['code' => 'VOL', 'name' => 'Volunteer ID Card',
                'description' => 'Identification for active volunteers on CHRSD programmes.',
                'default_validity_months' => 12],
            ['code' => 'VIS', 'name' => 'Visitor / Temporary ID Card',
                'description' => 'Short-term access pass for visitors, contractors, and event guests.',
                'default_validity_months' => 1],
        ];

        foreach ($idCardTypes as $t) {
            IdCardType::updateOrCreate(['code' => $t['code']], $t);
        }
    }
}
