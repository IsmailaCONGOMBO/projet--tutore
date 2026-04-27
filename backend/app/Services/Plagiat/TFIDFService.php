<?php

namespace App\Services\Plagiat;

use App\Models\Chapitre;
use App\Services\Plagiat\Contracts\TFIDFServiceInterface;
use App\Services\Plagiat\Contracts\PreprocessingServiceInterface;
use Illuminate\Support\Facades\Log;

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

        $termCounts = array_count_values($tokens);

        foreach ($termCounts as $term => $count) {
            $tf[$term] = $count / $totalTokens;
        }

        return $tf;
    }

    /**
     * Calcule l'Inverse Document Frequency (IDF) pour un terme donné.
     * Formule lissée standard : log((1 + N) / (1 + df)) + 1
     *
     * @param string $term
     * @param array  $corpus Tableau de documents (chaque document est un tableau de tokens)
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

        return log((1 + $totalDocuments) / (1 + $documentFrequency)) + 1;
    }

    /**
     * Calcule le vecteur TF-IDF en utilisant uniquement les termes du document.
     * NOTE : pour une comparaison correcte, préférer computeTFIDFWithVocabulary().
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
     * ✅ EXPERT NLP : Calcule le vecteur TF-IDF sur un vocabulaire FIXE COMMUN.
     *
     * @param array $tokens         Tokens du document à vectoriser
     * @param array $vocabulary     Vocabulaire global (tous les termes du corpus)
     * @param array $globalIDF      Tableau associatif [terme => idf] pré-calculé
     * @return array                Vecteur TF-IDF [terme => valeur]
     */
    public function computeTFIDFWithVocabulary(array $tokens, array $vocabulary, array $globalIDF): array
    {
        $tfidfVector = [];
        $tf = $this->computeTF($tokens);

        foreach ($vocabulary as $term) {
            $tfValue  = isset($tf[$term]) ? $tf[$term] : 0.0;
            $idfValue = isset($globalIDF[$term]) ? $globalIDF[$term] : 1.0;
            $tfidfVector[$term] = $tfValue * $idfValue;
        }

        return $tfidfVector;
    }

    /**
     * ✅ EXPERT NLP : Construit le vocabulaire global (union de tous les termes).
     *
     * @param array $corpus         Corpus d'archives
     * @param array $newDocTokens   Tokens du nouveau document
     * @return array
     */
    public function buildGlobalVocabulary(array $corpus, array $newDocTokens): array
    {
        $allTokens = $newDocTokens;
        foreach ($corpus as $doc) {
            $allTokens = array_merge($allTokens, $doc['tokens']);
        }
        return array_unique($allTokens);
    }

    /**
     * ✅ EXPERT NLP : Calcule l'IDF pour chaque terme du vocabulaire une seule fois.
     *
     * @param array $vocabulary
     * @param array $corpus
     * @param array $newDocTokens
     * @return array [terme => idf]
     */
    public function computeGlobalIDF(array $vocabulary, array $corpus, array $newDocTokens): array
    {
        $idfMap = [];
        $allDocs = array_column($corpus, 'tokens');
        $allDocs[] = $newDocTokens;
        $totalDocs = count($allDocs);

        // Pré-calculer les fréquences de documents (DF)
        $dfMap = [];
        foreach ($allDocs as $doc) {
            $uniqueTerms = array_unique($doc);
            foreach ($uniqueTerms as $term) {
                if (!isset($dfMap[$term])) $dfMap[$term] = 0;
                $dfMap[$term]++;
            }
        }

        foreach ($vocabulary as $term) {
            $df = isset($dfMap[$term]) ? $dfMap[$term] : 0;
            $idfMap[$term] = log((1 + $totalDocs) / (1 + $df)) + 1;
        }

        return $idfMap;
    }

    /**
     * ✅ FIX CRITIQUE : Construit le corpus depuis la base en GROUPANT par rapport_id.
     *
     * Problème original : le corpus était construit chapitre par chapitre, donc on
     * comparait un "rapport_complet" contre un chapitre = 1/3 du texte → score max ~33%.
     *
     * Correction : on regroupe tous les chapitres d'un même rapport pour reconstituer
     * le rapport entier. On ajoute aussi les entrées par chapitre pour la granularité fine.
     *
     * @param int|null $excludeRapportId  ID du rapport courant à exclure du corpus
     * @return array
     */
    public function buildCorpusFromDatabase(?int $excludeRapportId = null): array
    {
        $corpus = [];

        // ✅ Statuts synchronisés : On inclut TOUT pour maximiser la détection
        // Même les rapports en attente ou en cours d'analyse
        $validStatuts = [
            'EN_ATTENTE_ANALYSE_CHEF',
            'EN_ANALYSE',
            'VALIDE_PLAGIAT',
            'REJETE_PLAGIAT',
            'ASSIGNE_ENSEIGNANT',
            'NOTE_SOUMISE',
            'NOTE_VALIDEE_ADMIN',
            'NOTE_REJETEE_ADMIN',
            'VALIDE_FINAL',
            'REJETE_FINAL',
            'VALIDE',
            'REJETE',
            'ARCHIVE',
            'NOTE',
            'ANALYSE',
        ];

        $chapitres = Chapitre::whereHas('rapport', function ($query) use ($excludeRapportId, $validStatuts) {
            $query->whereIn('statut', $validStatuts);
            if ($excludeRapportId) {
                $query->where('id', '!=', $excludeRapportId);
            }
        })->get();

        if ($chapitres->isEmpty()) {
            Log::info('TFIDFService::buildCorpusFromDatabase — Corpus vide (aucun rapport dans les statuts valides).');
            return [];
        }

        // ✅ Regrouper les chapitres par rapport_id pour créer une entrée RAPPORT COMPLET
        $byRapport = $chapitres->groupBy('rapport_id');

        foreach ($byRapport as $rapportId => $chapitresRapport) {
            // --- Entrée RAPPORT COMPLET (concaténation de tous les chapitres) ---
            $fullText   = '';
            $fullTokens = [];
            $firstHash  = null; // hash du 1er chapitre disponible

            foreach ($chapitresRapport as $chapitre) {
                if (!empty($chapitre->contenu_texte)) {
                    // ✅ OPTION A : Les tokens sont déjà stockés préprocessés
                    // On explode simplement pour gagner du temps et garantir la cohérence
                    $chTokens = explode(' ', $chapitre->contenu_texte);
                    $chTokens = array_filter($chTokens, fn($t) => !empty($t)); // Nettoyage de sécurité
                    
                    $fullTokens = array_merge($fullTokens, $chTokens);

                    // ✅ RÉPARATION RÉTROACTIVE DU HASH (si manquant)
                    if (empty($chapitre->hash)) {
                        // On ne peut pas regénérer le hash parfaitement depuis les tokens si on a perdu le texte brut,
                        // mais on peut utiliser les tokens concaténés comme fallback.
                        $chapitre->hash = hash('sha256', implode('', $chTokens));
                        $chapitre->save();
                    }

                    // Stocker le hash de chaque chapitre individuellement
                    $corpus[] = [
                        'id'         => $chapitre->id,
                        'rapport_id' => $rapportId,
                        'doc_name'   => 'Rapport_' . $rapportId . '_' . $chapitre->label,
                        'tokens'     => $chTokens,
                        'hash'       => $chapitre->hash ?? null,
                        'granularity'=> 'chapitre',
                        'label'      => $chapitre->label,
                    ];
                }
            }

            if (!empty($fullTokens)) {
                // ✅ RÉCUPÉRATION DU HASH RÉEL DU RAPPORT (Standardisé)
                // On récupère le hash stocké dans la table rapports pour que le Fast-Match fonctionne
                $rapport = \App\Models\Rapport::find($rapportId);
                $fullHash = $rapport ? $rapport->hash_document : hash('sha256', implode('', $fullTokens));

                // ✅ Entrée RAPPORT COMPLET pour comparer rapport vs rapport
                $corpus[] = [
                    'id'         => 'rapport_' . $rapportId,
                    'rapport_id' => $rapportId,
                    'doc_name'   => 'Rapport_' . $rapportId . '_complet',
                    'tokens'     => $fullTokens,
                    'hash'       => $fullHash,
                    'granularity'=> 'rapport',
                    'label'      => 'rapport_complet',
                ];
            }
        }

        Log::info('TFIDFService::buildCorpusFromDatabase — Corpus construit : ' . count($corpus) . ' entrées (chapitres + rapports complets).');

        return $corpus;
    }
}
