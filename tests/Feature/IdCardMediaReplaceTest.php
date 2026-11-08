<?php

namespace Tests\Feature;

use App\Filament\Resources\IdCardResource\Pages\EditIdCard;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The photo and both signatures on an ID card can be replaced from the edit form.
 */
class IdCardMediaReplaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private IdCard $card;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('tmp-for-tests');
        Organization::firstOrCreate(['id' => config('chrsd.org_id')], ['code' => 'CHRSD', 'name' => 'CHRSD']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'media-admin@test.test', 'password' => Hash::make('secret'),
        ]);
        $this->admin->assignRole('super_admin');

        $dept = Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $pos = Position::firstOrCreate(['department_id' => $dept->id, 'code' => 'S'], ['title' => 'Staff']);
        $employee = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => uniqid('media', true) . '@test.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);

        $this->card = IdCard::create([
            'organization_id' => config('chrsd.org_id'),
            'employee_id' => $employee->id,
            'designation' => 'Field Officer',
            'blood_group' => 'O+',
            'nationality' => 'Bangladeshi',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYears(2)->toDateString(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_photo_and_both_signatures_can_be_replaced(): void
    {
        $this->card->addMedia(UploadedFile::fake()->image('old-photo.jpg'))->toMediaCollection('photo');
        $this->card->addMedia(UploadedFile::fake()->image('old-bearer.png'))->toMediaCollection('bearer_signature');
        $this->card->addMedia(UploadedFile::fake()->image('old-sig.png'))->toMediaCollection('signature');

        // Uploads go through Livewire's temporary-file path, as they do in the browser.
        $component = Livewire::actingAs($this->admin)
            ->test(EditIdCard::class, ['record' => $this->card->getRouteKey()])
            ->fillForm(['id_type_label' => 'Official Identity'])
            ->set('data.photo', [UploadedFile::fake()->image('new-photo.jpg')])
            ->set('data.bearer_signature', [UploadedFile::fake()->image('new-bearer.png')])
            ->set('data.signature', [UploadedFile::fake()->image('new-sig.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        $card = $this->card->fresh();

        $this->assertSame(['new-photo'], $card->getMedia('photo')->pluck('name')->all());
        $this->assertSame(['new-bearer'], $card->getMedia('bearer_signature')->pluck('name')->all());
        $this->assertSame(['new-sig'], $card->getMedia('signature')->pluck('name')->all());
    }
}
