<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    //
    public function clientesPorZona()
    {
        // 1. Consulta con Query Builder
        $zonas = DB::table('clients') // ← HUECO A
            ->select(
                'zona_geografica', // ← HUECO B
                DB::raw('COUNT(*) as total') // ← HUECO C
            )
            ->groupBy('zona_geografica') // ← HUECO D
            ->orderByDesc('zona_geografica') // ← HUECO E
            ->______(); // ← HUECO F
        // 2. Total general
        $totalGeneral = $zonas->______('total'); // ← HUECO G
        // 3. Agregar porcentaje
        $zonasConPorcentaje = $zonas->______(function ($zona) use ($totalGeneral) {
            // ← HUECO H
            $zona->porcentaje = $totalGeneral > 0
                ? round((______ / $totalGeneral) * 100, 2) // ← HUECO I
                : 0;
            return ______; // ← HUECO J
        });
        // 4. Datos para gráfico
        $labels = $zonasConPorcentaje->______('zona_geografica')->toArray(); // ← HUECO K
        $data = $zonasConPorcentaje->______('total')->toArray(); // ← HUECO L
        return view('reports.zonas', compact(
            'zonasConPorcentaje',
            'totalGeneral',
            'labels',
            'data'
        ));
    }

}
