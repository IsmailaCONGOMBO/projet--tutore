<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Plagiat\PreprocessingService;
use App\Services\Plagiat\TFIDFService;
use App\Services\Plagiat\SimilarityService;
use App\Services\Plagiat\ParaphraseDetectionService;

class PlagiatSimilarityTest extends TestCase
{
    protected $preprocessor;
    protected $tfidf;
    protected $similarity;
    protected $paraphrase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preprocessor = new PreprocessingService();
        $this->tfidf = new TFIDFService($this->preprocessor);
        $this->similarity = new SimilarityService();
        $this->paraphrase = new ParaphraseDetectionService($this->similarity);
    }

    /** @test */
    public function test_identical_text_gives_100_percent_similarity()
    {
        $text = "Le système de détection de plagiat est un outil essentiel pour garantir l'intégrité académique dans les établissements d'enseignement supérieur.";
        
        $tokens = $this->preprocessor->tokenizeAndStem($text);
        
        // Vocabulaire commun (identique ici)
        $vocabulary = array_unique($tokens);
        $corpus = [$tokens]; // Le doc est son propre corpus
        
        $vecA = $this->tfidf->computeTFIDFWithVocabulary($tokens, $vocabulary, $corpus);
        $vecB = $this->tfidf->computeTFIDFWithVocabulary($tokens, $vocabulary, $corpus);
        
        $cosine = $this->similarity->cosineSimilarity($vecA, $vecB);
        $jaccard = $this->similarity->jaccardSimilarity($tokens, $tokens);
        $ngram = $this->paraphrase->ngramSimilarity($tokens, $tokens, 3);
        
        $globalScore = (0.60 * $cosine) + (0.10 * $jaccard) + (0.30 * $ngram);
        
        $this->assertEquals(1.0, $cosine, "Cosinus similarity must be 1.0 for identical text");
        $this->assertEquals(1.0, $jaccard, "Jaccard similarity must be 1.0 for identical text");
        $this->assertEquals(1.0, $ngram, "N-gram similarity must be 1.0 for identical text");
        $this->assertEquals(1.0, $globalScore, "Global score must be 1.0 for identical text");
    }

    /** @test */
    public function test_hash_is_identical_for_same_text()
    {
        $text = "Ceci est un test de hachage robuste.";
        $hash1 = $this->preprocessor->generateHash($text);
        $hash2 = $this->preprocessor->generateHash($text);
        
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA-256
    }

    /** @test */
    public function test_completely_different_texts_give_low_similarity()
    {
        $textA = "Le chat dort sur le canapé bleu dans le salon.";
        $textB = "La programmation en PHP avec le framework Laravel est puissante pour le backend.";
        
        $tokensA = $this->preprocessor->tokenizeAndStem($textA);
        $tokensB = $this->preprocessor->tokenizeAndStem($textB);
        
        $vocabulary = array_unique(array_merge($tokensA, $tokensB));
        $corpus = [$tokensA, $tokensB];
        
        $vecA = $this->tfidf->computeTFIDFWithVocabulary($tokensA, $vocabulary, $corpus);
        $vecB = $this->tfidf->computeTFIDFWithVocabulary($tokensB, $vocabulary, $corpus);
        
        $cosine = $this->similarity->cosineSimilarity($vecA, $vecB);
        
        $this->assertLessThan(0.2, $cosine, "Cosine similarity should be low for different topics");
    }

    /** @test */
    public function test_slight_modification_keeps_high_similarity()
    {
        $textA = "L'intelligence artificielle transforme rapidement notre façon de travailler et de communiquer au quotidien.";
        $textB = "L'IA transforme très vite notre manière de bosser et de discuter chaque jour.";
        
        $tokensA = $this->preprocessor->tokenizeAndStem($textA);
        $tokensB = $this->preprocessor->tokenizeAndStem($textB);
        
        $vocabulary = array_unique(array_merge($tokensA, $tokensB));
        $corpus = [$tokensA, $tokensB];
        
        $vecA = $this->tfidf->computeTFIDFWithVocabulary($tokensA, $vocabulary, $corpus);
        $vecB = $this->tfidf->computeTFIDFWithVocabulary($tokensB, $vocabulary, $corpus);
        
        $cosine = $this->similarity->cosineSimilarity($vecA, $vecB);
        
        $this->assertGreaterThan(0.4, $cosine, "Similarity should remain significant even with paraphrasing");
    }
}
