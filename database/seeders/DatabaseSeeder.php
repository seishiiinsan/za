<?php

namespace Database\Seeders;

use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $system = System::factory()->create([
            'email' => 'system@za.test',
            'password' => Hash::make('password'),
        ]);

        $alters = collect([
            ['name' => 'Kai', 'handle' => 'kai'],
            ['name' => 'Nori', 'handle' => 'nori'],
        ])->map(fn (array $attrs) => Alter::factory()->for($system)->create($attrs));

        $other = Alter::factory()->create(['name' => 'Sora', 'handle' => 'sora']);

        foreach ($alters->push($other) as $alter) {
            Post::factory()->count(3)->create()->each(
                fn (Post $post) => $post->authors()->attach($alter->getKey(), ['accepted' => true])
            );
        }

        $alters->first()->following()->attach($other->getKey(), ['accepted' => true]);
    }
}
