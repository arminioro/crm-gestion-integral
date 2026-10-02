<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    //
    public function clientesPorZona()
    {
        // 1. Consulta con Query Builder
        $zonas = DB::table('clients') // Busca y selecciona la tabla clients en la BD
            ->select(
                'zona geografica as zona_geografica', //seleccionar
                DB::raw('COUNT(*) as total') //contar
            ) //Selecciona la columna zona geografica y cuenta el total de clientes por zona
            ->groupBy('zona geografica') //agrupar los registros por zona geografica
            ->orderByDesc('zona geografica') //ordenar los resultados de forma descendente por zona geografica
            ->get(); //obtener los resultados de la consulta
        // 2. Total general
        $totalGeneral = $zonas->sum('total'); //suma el total de clientes por zona
        // 3. Agregar porcentaje
        $zonasConPorcentaje = $zonas->map(function ($zona) use ($totalGeneral) { //se recorre cada zona y se calcula el porcentaje de clientes por zona
            $zona->porcentaje = $totalGeneral > 0
                ? round(($zona->total / $totalGeneral) * 100, 2) //calcula el porcentaje de clientes por zona y lo redondea a 2 decimales
                : 0;
            return $zona; //regresa la zona con el porcentaje calculado
        });

        //reto 1 - filtrar las zonas que tengan un porcentaje mayor a 15%
        $zonasConPorcentaje = $zonasConPorcentaje->filter(fn($z) => $z->porcentaje > 15); //filtra las zonas que tengan un porcentaje mayor a 15%

        // 4. Datos para gráfico
        $labels = $zonasConPorcentaje->pluck('zona geografica')->toArray(); //se obtiene un arreglo con los nombres de las zonas geográficas
        $data = $zonasConPorcentaje->pluck('total')->toArray(); //se obtiene un arreglo con el total de clientes por zona geográfica
        return view('reports.zonas', compact(
            'zonasConPorcentaje',
            'totalGeneral',
            'labels',
            'data'
        )); //se pasa la información a la vista reports.zonas
    }

    public function interaccionesPorAsesor()
    {
        $asesores = DB::table('users_simple') // ← HUECO A
            ->select(
                'users_simple.id',
                'users_simple.name',
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'llamada' THEN 1 END) AS llamadas"), // ← HUECO B
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'visita' THEN 1 END) AS visitas"), // ← HUECO C
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'whatsapp' THEN 1 END) AS whatsapp"), // ← HUECO D
                DB::raw('COUNT(interactions.id) AS total') // ← HUECO E
            )
            ->LeftJoin('clients', 'clients.user_id', '=', 'users_simple.id') //← HUECO F
            ->LeftJoin(
                'interactions',
                'interactions.client_id',
                '=',
                'clients.id'
            ) // ← HUECO G
            ->groupBy('users_simple.id', 'users_simple.name') // ← HUECO H, I
            ->orderByDesc('users_simple.name') // ← HUECO J
            ->get();
        $labels = $asesores->pluck('name')->toArray(); // ← HUECO K
        $llamadas = $asesores->pluck('llamadas')->toArray(); // ← HUECO L
        $visitas = $asesores->pluck('visitas')->toArray(); // ← HUECO M
        $whatsapp = $asesores->pluck('whatsapp')->toArray(); // ← HUECO N

        //reto 2 - obtener el total de interacciones por día de la semana  
        $interaccionesPorDia = DB::table('interactions')
            ->select(
                DB::raw("DAYNAME(fecha_seguimiento) as dia_ingles"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('dia_ingles')
            ->get();

        //mapeo para traducir los dias a español
        $diasEspanol = [
            'Monday'    => 'Lunes',
            'Tuesday'   => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday'  => 'Jueves',
            'Friday'    => 'Viernes',
            'Saturday'  => 'Sábado',
            'Sunday'    => 'Domingo'
        ];

        $labelsDias = $interaccionesPorDia->map(fn($item) => $diasEspanol[$item->dia_ingles] ?? $item->dia_ingles)->toArray();
        $dataDias   = $interaccionesPorDia->pluck('total')->toArray();

        //pasamos labelsDias y dataDias a la vista mediante compact
        return view('reports.interacciones', compact(
            'asesores',
            'labels',
            'llamadas',
            'visitas',
            'whatsapp',
            'labelsDias',
            'dataDias'
        ));
    }
}
