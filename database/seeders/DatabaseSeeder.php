<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Demo User for local development & testing
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@pdfeditor.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('Demo@1234'),
            ]
        );

        // Associate any unowned documents/assets to the demo user so data is preserved
        \App\Models\Document::whereNull('user_id')->update(['user_id' => $demoUser->id]);
        \App\Models\DocumentAsset::whereNull('user_id')->update(['user_id' => $demoUser->id]);
        \App\Models\Signature::whereNull('user_id')->update(['user_id' => $demoUser->id]);
        \App\Models\SavedColor::whereNull('user_id')->update(['user_id' => $demoUser->id]);

        $this->call([
            TemplateSeeder::class,
        ]);
    }
}
