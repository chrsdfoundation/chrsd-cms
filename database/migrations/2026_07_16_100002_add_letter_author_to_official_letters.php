<?php

use App\Models\Author;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_letters', function (Blueprint $table) {
            // New FK to the authors table — nullable so old records without an
            // Author record are not broken.
            $table->foreignId('letter_author_id')
                ->nullable()
                ->after('author_id')
                ->constrained('authors')
                ->nullOnDelete();

            // Make the legacy employee author nullable so letters can be
            // created without requiring an Employee record.
            $table->unsignedBigInteger('author_id')->nullable()->change();
        });

        // Seed Author records from existing Employee authors used in letters.
        // We do this in the migration so the data is always consistent after
        // running migrations on a fresh or existing database.
        $this->seedAuthorsFromEmployees();
    }

    protected function seedAuthorsFromEmployees(): void
    {
        // Only runs if there are existing letters with employee authors.
        $rows = DB::table('official_letters')
            ->whereNotNull('author_id')
            ->whereNull('letter_author_id')
            ->join('employees', 'employees.id', '=', 'official_letters.author_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select(
                'official_letters.id as letter_id',
                'employees.id as employee_id',
                DB::raw("TRIM(CONCAT_WS(' ', employees.first_name, employees.middle_name, employees.last_name)) as full_name"),
                'positions.title as designation',
                'departments.name as department',
                'employees.email'
            )
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        // Build a map of employee_id → author_id to avoid duplicate Author rows.
        $employeeToAuthor = [];

        foreach ($rows as $row) {
            if (! isset($employeeToAuthor[$row->employee_id])) {
                $authorId = DB::table('authors')->insertGetId([
                    'name'        => $row->full_name,
                    'designation' => $row->designation ?? 'Staff',
                    'department'  => $row->department,
                    'organization'=> config('app.name', 'CHRSD'),
                    'email'       => $row->email,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $employeeToAuthor[$row->employee_id] = $authorId;
            }

            DB::table('official_letters')
                ->where('id', $row->letter_id)
                ->update(['letter_author_id' => $employeeToAuthor[$row->employee_id]]);
        }
    }

    public function down(): void
    {
        Schema::table('official_letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('letter_author_id');
            $table->unsignedBigInteger('author_id')->nullable(false)->change();
        });
    }
};
