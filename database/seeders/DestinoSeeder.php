<?php

namespace Database\Seeders;

use App\Models\Destino;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Destinos neuquinos con sus galerías. Las fotos están en database/seeders/imagenes
 * (Wikimedia Commons, licencias libres; autor y licencia en creditos.json) y se copian
 * al disco "public" para que la aplicación las sirva.
 */
class DestinoSeeder extends Seeder
{
    public function run(): void
    {
        $destinos = [
            'san-martin-de-los-andes' => [
                'nombre' => 'San Martín de los Andes',
                'destacado' => true,
                'descripcion' => 'A orillas del lago Lácar y rodeada de bosques andinos, es una de las villas más elegidas de la Patagonia. Ofrece playas de lago, miradores como Bandurrias y Arrayán, el cerro Chapelco para esquiar en invierno y es punto de partida de la Ruta de los Siete Lagos.',
            ],
            'villa-la-angostura' => [
                'nombre' => 'Villa La Angostura',
                'destacado' => true,
                'descripcion' => 'Sobre la costa norte del lago Nahuel Huapi, combina bosques de coihues con una vida tranquila de montaña. Desde allí se visita el Parque Nacional Los Arrayanes, la Bahía Mansa, la cascada del río Bonito y el centro de esquí Cerro Bayo.',
            ],
            'junin-de-los-andes' => [
                'nombre' => 'Junín de los Andes',
                'destacado' => true,
                'descripcion' => 'Conocida como la capital de la pesca con mosca, la atraviesa el río Chimehuín. Es la puerta de acceso al volcán Lanín y al Parque Nacional homónimo, y conserva una fuerte identidad mapuche visible en el Vía Christi del cerro de la Cruz.',
            ],
            'villa-traful' => [
                'nombre' => 'Villa Traful',
                'destacado' => true,
                'descripcion' => 'Pequeña aldea de montaña sobre el lago Traful, dentro del Parque Nacional Nahuel Huapi. Sus aguas cristalinas guardan un bosque sumergido ideal para el buceo, y sus senderos llevan a miradores, cascadas y a las pinturas rupestres de la zona.',
            ],
            'caviahue' => [
                'nombre' => 'Caviahue',
                'destacado' => true,
                'descripcion' => 'Villa de montaña junto al lago Caviahue, en el norte neuquino, rodeada de araucarias. Ofrece cascadas como las del Agrio, esquí y caminatas sobre nieve en invierno, y es el acceso natural al volcán Copahue.',
            ],
            'copahue' => [
                'nombre' => 'Copahue',
                'destacado' => true,
                'descripcion' => 'Centro termal de fama internacional a los pies del volcán Copahue. Sus lagunas, barros y vapores volcánicos se aprovechan con fines terapéuticos durante el verano, y paseos como Las Maquinitas muestran la actividad geotérmica de la zona.',
            ],
            'neuquen-capital' => [
                'nombre' => 'Neuquén Capital',
                'destacado' => false,
                'descripcion' => 'La capital provincial, en la confluencia de los ríos Limay y Neuquén, es el principal nodo de transporte de la región. Tiene paseos costeros, balnearios sobre el Limay, el Museo Nacional de Bellas Artes y es punto de partida de la mayoría de nuestros servicios.',
            ],
            'alumine' => [
                'nombre' => 'Aluminé',
                'destacado' => false,
                'descripcion' => 'Ubicada sobre el río Aluminé, famoso por el rafting y la pesca deportiva. Desde allí se recorren lagos como Ruca Choroy y Aluminé, bosques de araucarias y comunidades mapuches que ofrecen turismo rural.',
            ],
            'chos-malal' => [
                'nombre' => 'Chos Malal',
                'destacado' => false,
                'descripcion' => 'Primera capital del Neuquén, en la confluencia de los ríos Curí Leuvú y Neuquén. Conserva el Fortín IV División y edificios históricos, y es base para conocer el volcán Tromen, las lagunas del norte y la Ruta 40.',
            ],
            'zapala' => [
                'nombre' => 'Zapala',
                'destacado' => false,
                'descripcion' => 'Ciudad del centro de la provincia y cruce de rutas hacia la cordillera. A pocos kilómetros está el Parque Nacional Laguna Blanca, refugio del cisne de cuello negro, y la ciudad cuenta con el Museo Olsacher de minerales y fósiles.',
            ],
        ];

        $creditos = $this->leerCreditos();
        $disco = Storage::disk('public');
        $disco->deleteDirectory('destinos'); // evita fotos huérfanas si se vuelve a sembrar

        foreach ($destinos as $slug => $datos) {
            $destino = Destino::create($datos);

            foreach ($creditos->where('destino', $slug)->values() as $orden => $foto) {
                $origen = database_path('seeders/imagenes/'.$foto['archivo']);
                if (! File::exists($origen)) {
                    continue;
                }

                $ruta = 'destinos/'.$foto['archivo'];
                $disco->put($ruta, File::get($origen));

                $destino->imagenes()->create([
                    'ruta' => $ruta,
                    'epigrafe' => $foto['epigrafe'],
                    'credito' => "{$foto['autor']} · {$foto['licencia']} · Wikimedia Commons",
                    'orden' => $orden + 1,
                ]);
            }
        }
    }

    /** Lee database/seeders/imagenes/creditos.json (si no existe, los destinos quedan sin fotos). */
    private function leerCreditos()
    {
        $archivo = database_path('seeders/imagenes/creditos.json');

        if (! File::exists($archivo)) {
            $this->command?->warn('No se encontró creditos.json: los destinos se cargan sin fotos.');

            return collect();
        }

        return collect(json_decode(File::get($archivo), true)['galerias'] ?? []);
    }
}
