<?php

namespace Database\Factories;

use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\odel=Record>
 */
class RecordFactory extends Factory
{
    protected $model = Record::class;
    public function definition(): array
    {
        return [
            'title' =>  $this->faker->sentence(),
            'description' => $this->faker->paragraph(1),
            'status' => $this->faker->randomElement(['Allowed', 'Prohibited']),
        ];
    }
}
