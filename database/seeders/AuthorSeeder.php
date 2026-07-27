<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    /**
     * Seed the real letter/certificate authors carried over from the live CMS.
     * Keyed on name because two authors legitimately share an email address.
     * Signature/initial image paths are intentionally omitted — those files
     * live in (git-ignored) storage and are re-uploaded per environment.
     */
    public function run(): void
    {
        $authors = require __DIR__ . '/data/sync_authors.php';

        foreach ($authors as $author) {
            Author::updateOrCreate(['name' => $author['name']], $author);
        }
    }
}
