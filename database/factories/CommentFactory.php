<?php

namespace Database\Factories;

use App\Models\Alter;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
class CommentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'alter_id' => Alter::factory(),
            'content' => fake()->sentence(),
        ];
    }
}
