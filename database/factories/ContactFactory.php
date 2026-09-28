<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * first_name は姓、last_name は名（仕様書のとおり）
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days');

        return [
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'first_name' => fake()->lastName(),
            'last_name' => fake()->firstName(),
            'gender' => fake()->numberBetween(1, 3),
            'email' => fake()->unique()->safeEmail(),
            'tel' => fake()->numerify(fake()->randomElement(['0#########', '0##########'])),
            'address' => fake()->prefecture().fake()->city().fake()->streetAddress(),
            'building' => fake()->optional(0.7)->secondaryAddress(),
            'detail' => mb_substr(fake()->realText(fake()->numberBetween(20, 120)), 0, 120),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
