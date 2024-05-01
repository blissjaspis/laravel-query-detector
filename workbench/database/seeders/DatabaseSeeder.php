<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Workbench\App\Models\Author;
use Workbench\App\Models\Comment;
use Workbench\App\Models\Post;
use Workbench\App\Models\Profile;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authors = Author::factory()->count(5)->create();

        $authors->each(function ($author) {
            Profile::factory()->create([
                'author_id' => $author->id
            ]);

            $posts = Post::factory()->count(5)->create([
                'author_id' => $author->id
            ]);

            $posts->each(function ($post) {
                Comment::factory()->count(2)->create([
                    'commentable_id' => $post->id,
                    'commentable_type' => Post::class,
                ]);
            });
        });
    }
}
