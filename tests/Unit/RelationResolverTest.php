<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests\Unit;

use BlissJaspis\QueryDetector\Analysis\RelationResolver;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Workbench\App\Models\Author;
use Workbench\App\Models\Profile;

class RelationResolverTest extends TestCase
{
    private RelationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new RelationResolver;
    }

    public function test_it_infers_relation_name_via_reflection_without_invoking_other_methods(): void
    {
        $author = new Author;
        $relation = $author->posts();

        $name = $this->resolver->resolveRelationName($relation, collect());

        $this->assertSame('posts', $name);
    }

    public function test_it_resolves_relation_name_from_load_missing_backtrace(): void
    {
        $author = new Author;
        $relation = $author->profile();

        $backtrace = collect([
            [
                'function' => 'loadMissing',
                'args' => [['media', 'avatar']],
            ],
        ]);

        $name = $this->resolver->resolveRelationName($relation, $backtrace);

        $this->assertSame('media', $name);
    }

    public function test_it_skips_get_relation_value_when_name_is_load_missing(): void
    {
        $author = new Author;
        $relation = $author->profile();

        $backtrace = collect([
            [
                'function' => 'getRelationValue',
                'args' => ['loadMissing'],
            ],
            [
                'function' => 'loadMissing',
                'args' => ['profile'],
            ],
        ]);

        $name = $this->resolver->resolveRelationName($relation, $backtrace);

        $this->assertSame('profile', $name);
    }

    public function test_guess_relation_name_does_not_invoke_relation_methods(): void
    {
        $model = new class extends Model
        {
            protected $table = 'authors';

            public int $invokedMethods = 0;

            public function profile(): HasOne
            {
                $this->invokedMethods++;

                return $this->hasOne(Profile::class);
            }

            public function posts(): HasMany
            {
                $this->invokedMethods++;

                return $this->hasMany(Author::class);
            }
        };

        $relation = $model->posts();
        $model->invokedMethods = 0;

        $name = $this->resolver->resolveRelationName($relation, collect());

        $this->assertSame('posts', $name);
        $this->assertSame(0, $model->invokedMethods);
    }
}
