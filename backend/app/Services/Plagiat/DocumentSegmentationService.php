<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\DocumentSegmentationServiceInterface;
use Illuminate\Support\Facades\Log;

class DocumentSegmentationService implements DocumentSegmentationServiceInterface
{
    /**
     * @inheritDoc
     */
    public function segment(string $text): array
    {
        // Expressions régulières pour détecter les chapitres
        // Insensible à la casse (i) et multi-lignes pour attraper les débuts de ligne éventuels
        $patterns = [
            '/(chapitre\s*[0-9ivx]+)/i',
            '/(partie\s*[0-9ivx]+)/i',
            '/(chapter\s*[0-9ivx]+)/i',
            '/(section\s*[0-9ivx]+)/i'
        ];

        $matches = [];
        $offsets = [];

        // Chercher toutes les occurrences de marqueurs
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $foundMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($foundMatches[0] as $match) {
                    $offsets[$match[1]] = trim($match[0]);
                }
            }
        }

        // Trier par position croissante dans le texte
        ksort($offsets);

        // Si on a 2 ou 3 marqueurs (et pas plus de 3 pour respecter l'organisation en 3 chapitres)
        $markerCount = count($offsets);
        if ($markerCount >= 2) {
            Log::info("DocumentSegmentationService: $markerCount marqueurs de chapitres trouvés, segmentation en cours.");
            
            // Prendre maximum 3 chapitres comme stipulé
            $offsets = array_slice($offsets, 0, 3, true);
            $positions = array_keys($offsets);
            $labels = array_values($offsets);
            
            $segments = [];
            $chapitreIndex = 1;

            for ($i = 0; $i < count($positions); $i++) {
                $start = $positions[$i];
                $end = isset($positions[$i + 1]) ? $positions[$i + 1] : strlen($text);
                
                $segmentText = substr($text, $start, $end - $start);
                
                $segments[] = [
                    'label' => 'chapitre_' . $chapitreIndex,
                    'text' => trim($segmentText)
                ];
                $chapitreIndex++;
            }

            return $segments;
        }

        // Si 0 ou 1 marqueur trouvé, on ne force pas la segmentation
        Log::info("DocumentSegmentationService: Moins de 2 marqueurs trouvés ($markerCount), pas de segmentation forcée.");
        return [
            [
                'label' => 'rapport_complet',
                'text' => trim($text)
            ]
        ];
    }
}
