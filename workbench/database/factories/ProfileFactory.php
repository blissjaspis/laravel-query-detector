<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Author;
use Workbench\App\Models\Profile;

/**
 * @template TModel of \Workbench\App\Profile
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'birthday' => $this->faker->dateTimeBetween('-100 years', '-18 years'),
            'author_id' => function () {
                return Author::factory()->create()->id;
            },
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'website' => $this->faker->domainName,
        ];
    }
}
