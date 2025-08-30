<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provider;
use App\Models\Service;

class ProvidersAndServicesSeeder extends Seeder
{
    public function run(): void
    {
        $barrios = [
            ['Amaguaña', -0.353300, -78.478100],
            ['Conocoto', -0.294800, -78.475800],
            ['Sangolquí', -0.316700, -78.452200],
            ['Alangasí', -0.302900, -78.423600],
            ['San Rafael', -0.319000, -78.460000],
            ['La Merced', -0.343400, -78.389400],
        ];

        $proveedores = [
            ['TecnoCam CCTV',         'Seguridad'],
            ['ElectroAndes',          'Electricidad'],
            ['Cerrajería Express',    'Cerrajería'],
            ['Plomería Valle',        'Plomería'],
            ['MultiServicios López',  'Mantenimiento'],
            ['Redes & Internet EC',   'Redes'],
            ['Pinturas Pro',          'Pintura'],
            ['Herrería & Soldadura',  'Soldadura'],
            ['Aire Acondicionado EC', 'Climatización'],
            ['Computec Soporte',      'Soporte TI'],
            ['Gasfitería QuitoSur',   'Plomería'],
            ['Cerrajeros 24/7',       'Cerrajería'],
            ['Energía Solar Andina',  'Electricidad'],
            ['Seguridad Hogar Plus',  'Seguridad'],
            ['CCTV & Domótica',       'Seguridad'],
        ];

        $descripciones = [
            'Instalación de cámaras Hikvision/DAHUA, cableado, DVR/NVR, app móvil.',
            'Cableado residencial/industrial, tableros, iluminación LED, normalización.',
            'Apertura de puertas, cambio de chapas, cierres metálicos, emergencia 24/7.',
            'Reparación de fugas, cambio de grifería, calefones, destape de tuberías.',
            'Mantenimiento general, verificación de instalaciones, mano de obra calificada.',
            'Tendido de UTP, configuración de routers, mejoramiento de señal WiFi.',
            'Pintura interior/exterior, alisado, estuco, impermeabilización.',
            'Soldadura estructural, puertas, ventanas, rejas, trabajos a medida.',
            'Instalación y mantenimiento de minisplit, recarga de gas, limpieza.',
            'Formateo, optimización, instalación de software, antivirus, respaldos.',
            'Instalación de lavamanos, sanitarios, bombas de agua.',
            'Cierres enrollables, candados de seguridad, copia de llaves.',
            'Paneles solares, inversores, cableado, ahorro energético.',
            'Alarmas, sensores de movimiento, cerraduras inteligentes.',
            'Automatización básica, control por voz, integración con CCTV.',
        ];

        $precios = [20, 35, 50, 80, 120, 150, 200];
        $creados = 0;

        foreach ($proveedores as $pv) {
            $b = $barrios[array_rand($barrios)];
            $prov = Provider::create([
                'nombre' => $pv[0],
                'barrio' => $b[0],
                'lat'    => $b[1],
                'lon'    => $b[2],
                'rating_promedio' => round(mt_rand(40, 50) / 10, 1), // 4.0–5.0
            ]);

            $num = rand(1,2);
            for ($i=0; $i<$num; $i++) {
                Service::create([
                    'provider_id' => $prov->id,
                    'categoria'   => $pv[1],
                    'titulo'      => $pv[1].' - '.$pv[0],
                    'descripcion' => $descripciones[array_rand($descripciones)],
                    'precio_desde'=> $precios[array_rand($precios)],
                    'activo'      => true,
                ]);
                $creados++;
                if ($creados >= 24) break 2;
            }
        }
    }
}
