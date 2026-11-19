<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use App\Models\Viajero;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba. Todas las contraseñas son "password" (ficticias, documentadas en el README).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Dos administradores-vendedores: permiten mostrar que cada uno sólo edita los viajes que cargó.
        foreach (['admin@viaaliwen.test', 'ventas@viaaliwen.test'] as $email) {
            $admin = new User(['email' => $email, 'password' => 'password']);
            $admin->rol = Rol::Admin; // el rol se asigna explícitamente, no por asignación masiva
            $admin->save();
        }

        // Viajeros con datos conocidos para las pruebas manuales.
        $viajeros = [
            ['lucia.gomez@viaaliwen.test', 'Lucía', 'Gómez', '35123456', '1990-04-12'],
            ['martin.paz@viaaliwen.test', 'Martín', 'Paz', '40234567', '1997-09-03'],
            ['ana.quiroga@viaaliwen.test', 'Ana', 'Quiroga', '28345678', '1981-01-25'],
        ];

        foreach ($viajeros as [$email, $nombre, $apellido, $dni, $nacimiento]) {
            $user = User::create(['email' => $email, 'password' => 'password']); // rol por defecto: viajero
            $user->viajero()->create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'fecha_nacimiento' => $nacimiento,
            ]);
        }

        // Pasajeros ficticios adicionales para el historial de ventas y el viaje completo.
        Viajero::factory()->count(60)->create();
    }
}
