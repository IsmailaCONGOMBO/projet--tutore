<?php

namespace App\Services\Plagiat;

use App\Models\Chapitre;
use App\Services\Plagiat\Contracts\TFIDFServiceInterface;
use App\Services\Plagiat\Contracts\PreprocessingServiceInterface;

class TFIDFService implements TFIDFServiceInterface
{
    /**
     * @var PreprocessingServiceInterface
     */
    protected $preprocessingService;

    public function __construct(PreprocessingServiceInterface $preprocessingService)
    {
        $this->preprocessingService = $preprocessingService;
    }

    /**
     * Calcule le Term Frequency (TF)
     *
     * @param array $tokens
     * @return array
     */
    public function computeTF(array $tokens): array
    {
        $tf = [];
        $totalTokens = count($tokens);

        if ($totalTokens === 0) {
            return $tf;
        }

        // Compter les occurrences de chaque token
        $termCounts = array_count_values($tokens);

        // Calculer TF: occurrences / nombre total de tokens
        foreach ($termCounts as $term => $count) {
            $tf[$term] = $count / $totalTokens;
        }

        return $tf;
    }

    /**
     * Calcule l'Inverse Document Frequency (IDF) pour un terme donné
     *
     * @param string $term
     * @param array $corpus Tableau de documents (chaque document est un tableau de tokens)
     * @return float
     */
    public function computeIDF(string $term, array $corpus): float
    {
        $totalDocuments = count($corpus);
        if ($totalDocuments === 0) {
            return 0.0;
        }

        $documentFrequency = 0;
        foreach ($corpus as $document) {
            if (in_array($term, $document)) {
                $documentFrequency++;
            }
        }

        // IDF = log(N / (1 + df))
        // On évite log(0)
        return log($totalDocuments / (1 + $documentFrequency));
    }

    /**
     * Calcule le vecteur TF-IDF complet pour un document
     *
     * @param array $tokens
     * @param array $corpus
     * @return array
     */
    public function computeTFIDF(array $tokens, array $corpus): array
    {
        $tfidfVector = [];
        $tf = $this->computeTF($tokens);

        foreach ($tf as $term => $tfValue) {
            $idfValue = $this->computeIDF($term, $corpus);
            $tfidfVector[$term] = $tfValue * $idfValue;
        }

        return $tfidfVector;
    }

    /**
     * Construit le corpus à partir des chapitres des rapports acceptés.
     * 
     * @return array Tableau de documents archivés avec leurs tokens et informations associées
     */
    public function buildCorpusFromDatabase(): array
    {
        $corpus = [];

        // Supposons que nous avons une table chapitres avec une relation rapport
        // et qu'on ne prend que les rapports au statut 'accepté'.
        // Si la relation ou la structure exacte diffère, il faudra ajuster.
        // On ne prend que les rapports au statut 'ARCHIVE', 'NOTE' ou 'ANALYSE' pour la base de comparaison
        $chapitres = Chapitre::whereHas('rapport', function ($query) {
            $query->whereIn('statut', ['ARCHIVE', 'NOTE', 'ANALYSE']);
        })->get();

        foreach ($chapitres as $chapitre) {
            if (!empty($chapitre->contenu_texte)) {
                $tokens = $this->preprocessingService->tokenizeAndStem($chapitre->contenu_texte);
                $corpus[] = [
                    'id' => $chapitre->id,
                    'rapport_id' => $chapitre->rapport_id,
                    'doc_name' => 'Rapport_' . $chapitre->rapport_id . '_' . $chapitre->label,
                    'tokens' => $tokens
                ];
            }
        }

        return $corpus;
    }
}
