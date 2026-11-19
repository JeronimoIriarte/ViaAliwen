<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Viajero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Viajeros de prueba con datos ficticios (nombres argentinos generados por Faker).
 *
 * @extends Factory<Viajero>
 */
class ViajeroFactory extends Factory
{
    protected $model = Viajero::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(20000000, 45999999),
            'fecha_nacimiento' => fake()->dateTimeBetween('-75 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
