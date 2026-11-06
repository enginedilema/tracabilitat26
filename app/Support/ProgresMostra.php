<?php

namespace App\Support;

use App\Models\Mostra;
use App\Models\MostraProcessament;

/**
 * Calcula el progrés a partir de les confirmacions guardades a la base de dades.
 * No desa percentatges: evita discrepàncies amb el procés real.
 */
final class ProgresMostra
{
    /**
     * @return array{fases: array<int, array{nom: string, completada: bool}>, completades: int, total: int, percentatge: int}
     */
    public static function calcular(Mostra $mostra, ?MostraProcessament $proces): array
    {
        $fases = [
            self::fase('Recepció', (bool) $mostra->recepcio_confirmada),
            self::fase('Descripció macroscòpica', (bool) $proces?->descripcio_confirmada),
        ];

        if ($mostra->tipus_mostra === 'citologia') {
            $fases[] = self::fase('Procediments de citologia', (bool) $proces?->procediment_confirmat);
            $fases[] = self::fase('Tincions', (bool) $proces?->tincions_confirmades);
            $fases[] = self::fase('Muntatge', (bool) $proces?->muntatge_confirmat);
            $fases[] = self::fase('Lliurament', (bool) $proces?->lliurament_confirmat);
        } else {
            $fases[] = self::fase('Tallat macroscòpic', (bool) $proces?->tallat_macroscopic_confirmat);
            $fases[] = self::fase('Sol·licitud de tincions', (bool) $proces?->tincions_confirmades);
            $fases[] = self::fase('Processador de teixits', (bool) $proces?->processador_teixits_confirmat);
            $fases[] = self::fase('Inclusió en parafina', (bool) $proces?->inclusio_parafina_confirmada);
            $fases[] = self::fase('Confecció i tallat de blocs', (bool) $proces?->confeccio_blocs_confirmada);
            $fases[] = self::fase('Lliurament dels talls', (bool) $proces?->lliurament_talls_confirmat);
            $fases[] = self::fase('Tinció realitzada', (bool) $proces?->tincio_confirmada);
            $fases[] = self::fase('Muntatge', (bool) $proces?->muntatge_confirmat);
            $fases[] = self::fase('Lliurament al patòleg', (bool) $proces?->lliurament_patoleg_confirmat);
        }

        $fases[] = self::fase('Diagnòstic', (bool) $proces?->diagnostic_confirmat);
        $fases[] = self::fase('Arxiu', (bool) $proces?->arxiu_confirmat);

        $completades = count(array_filter($fases, static fn (array $fase): bool => $fase['completada']));
        $total = count($fases);

        return [
            'fases' => $fases,
            'completades' => $completades,
            'total' => $total,
            'percentatge' => (int) round(100 * $completades / $total),
        ];
    }

    /** @return array{nom: string, completada: bool} */
    private static function fase(string $nom, bool $completada): array
    {
        return ['nom' => $nom, 'completada' => $completada];
    }
}
