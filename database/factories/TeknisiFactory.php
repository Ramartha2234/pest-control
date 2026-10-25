<?php

namespace Database\Factories;

use App\Models\Teknisi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teknisi>
 */
class TeknisiFactory extends Factory
{
    protected $model = Teknisi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'no_telp' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
