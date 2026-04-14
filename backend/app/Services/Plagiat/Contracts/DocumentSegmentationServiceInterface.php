<?php

namespace App\Services\Plagiat\Contracts;

interface DocumentSegmentationServiceInterface
{
    /**
     * Découpe le texte brut en chapitres si les marqueurs sont trouvés.
     * Sinon, retourne le texte complet sous un seul segment.
     *
     * @param string $text Le texte brut complet à segmenter
     * @return array Tableau de segments : [['label' => 'chapitre_1', 'text' => '...'], ...]
     */
    public function segment(string $text): array;
}
