<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPlagiatEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plagiat:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste les performances du moteur de détection de plagiat';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("--- DÉBUT DU TEST DU MOTEUR DE PLAGIAT ---");

        $analyzer = app(\App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface::class);
        $preprocessor = app(\App\Services\Plagiat\Contracts\PreprocessingServiceInterface::class);

        $textOriginal = "Le développement d'un système de détection de plagiat requiert une expertise en traitement du langage naturel (NLP). 
        Ce projet utilise Laravel pour le backend et Angular pour le frontend. 
        Les algorithmes de TF-IDF et de similarité cosinus permettent de mesurer la proximité lexicale entre deux documents.";

        $textIdentique = $textOriginal;

        $textModifie = "Développer un outil pour détecter le plagiat nécessite des compétences en NLP. 
        Cette application est bâtie avec Laravel en backend et Angular côté client. 
        On utilise TF-IDF et le cosinus pour comparer la similarité entre les fichiers.";

        $textDifferent = "La cuisine française est réputée dans le monde entier pour sa finesse et ses saveurs variées. 
        La baguette et le fromage sont des symboles gastronomiques incontournables. 
        On y trouve aussi de grands vins dans les régions de Bordeaux et de Bourgogne.";

        // Créer des fichiers temporaires pour le test
        $storage = storage_path('app/temp_tests');
        if (!file_exists($storage)) mkdir($storage, 0777, true);

        // Puisqu'on ne peut pas facilement générer des PDF à la volée sans Snappy/DomPDF,
        // on va juste mocker l'extraction de texte ou injecter les services.
        // Mais pour un test réaliste, on peut utiliser des fichiers texte.
        // Comme le service analyzer utilise PDF, on va plutôt tester les services de base.

        $this->testSimilarity("IDENTIQUE", $textOriginal, $textIdentique);
        $this->testSimilarity("MODIFIÉ (Paraphrase)", $textOriginal, $textModifie);
        $this->testSimilarity("DIFFÉRENT", $textOriginal, $textDifferent);

        $this->info("--- FIN DU TEST ---");
    }

    private function testSimilarity($label, $ref, $target)
    {
        $preprocessor = app(\App\Services\Plagiat\Contracts\PreprocessingServiceInterface::class);
        $tfidf = app(\App\Services\Plagiat\Contracts\TFIDFServiceInterface::class);
        $similarity = app(\App\Services\Plagiat\Contracts\SimilarityServiceInterface::class);
        $paraphrase = app(\App\Services\Plagiat\Contracts\ParaphraseDetectionServiceInterface::class);

        $tokensRef = $preprocessor->tokenizeAndStem($ref);
        $tokensTarget = $preprocessor->tokenizeAndStem($target);
        $corpus = [$tokensRef, $tokensTarget];

        $vecRef = $tfidf->computeTFIDF($tokensRef, $corpus);
        $vecTarget = $tfidf->computeTFIDF($tokensTarget, $corpus);

        $cos = $similarity->cosineSimilarity($vecRef, $vecTarget);
        $jac = $similarity->jaccardSimilarity($tokensRef, $tokensTarget);
        $ngr = $paraphrase->ngramSimilarity($tokensRef, $tokensTarget, 3);

        $final = (0.6 * $cos) + (0.1 * $jac) + (0.3 * $ngr);
        $score = round($final * 100, 2);

        $status = "OK";
        if ($label === "IDENTIQUE" && $score < 98) $status = "FAIL";
        if ($label === "DIFFÉRENT" && $score > 20) $status = "FAIL";

        $this->line("[$label] Score: $score% | Status: $status (Cos:$cos, Jac:$jac, Ngr:$ngr)");
    }
}
