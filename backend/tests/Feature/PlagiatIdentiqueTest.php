<?php

namespace Tests\Feature;

use App\Models\Chapitre;
use App\Models\Rapport;
use App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface;
use App\Services\Plagiat\Contracts\PreprocessingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlagiatIdentiqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Créer des fichiers bidon pour passer les checks file_exists
        @file_put_contents('fake_path.pdf', 'dummy content');
        @file_put_contents('similar_path.pdf', 'dummy content');
    }

    protected function tearDown(): void
    {
        @unlink('fake_path.pdf');
        @unlink('similar_path.pdf');
        parent::tearDown();
    }

    public function test_same_document_gives_100_percent_plagiat_rate_via_hash()
    {
        // 1. Initialiser les services
        $preprocessor = app(PreprocessingServiceInterface::class);

        // 2. Texte de test
        $texteTest = "Le système de gestion de rapports permet aux étudiants de soumettre leurs travaux en ligne. 
                      L'administration valide les thèmes et les rapports. 
                      Le moteur de détection analyse la similarité entre les documents soumis et le corpus existant.";

        // 3. Archiver un premier rapport
        // On simule un rapport déjà validé en base
        $hash = $preprocessor->generateHash($texteTest);
        
        $user = \App\Models\User::factory()->create();
        $etudiant = \App\Models\Etudiant::create([
            'user_id' => $user->id,
            'nom' => 'Test', 
            'prenom' => 'User', 
            'email' => 'test@example.com', 
            'num_carte' => '12345'
        ]);
        $theme = \App\Models\Theme::create([
            'titre' => 'Theme Test', 
            'description' => 'Desc', 
            'etudiant_id' => $etudiant->id,
            'statut' => 'EN_ATTENTE_CHEF'
        ]);

        $rapportArchive = Rapport::create([
            'etudiant_id' => $etudiant->id,
            'theme_id' => $theme->id,
            'titre' => 'Rapport Original',
            'fichier_path' => 'path/to/original.pdf',
            'fichier_nom_original' => 'original.pdf',
            'fichier_taille' => 1024,
            'hash_document' => $hash,
            'statut' => 'VALIDE_PLAGIAT',
        ]);

        Chapitre::create([
            'rapport_id' => $rapportArchive->id,
            'label' => 'rapport_complet',
            'contenu_texte' => implode(' ', $preprocessor->tokenizeAndStem($texteTest)),
            'hash' => $hash,
            'taux_plagiat' => 0,
            'nb_mots' => count($preprocessor->tokenizeAndStem($texteTest))
        ]);

        // 4. Analyser le MÊME texte
        // On mock l'extracteur pour retourner le même texte
        $this->mock(\App\Services\Plagiat\Contracts\TextExtractionServiceInterface::class, function ($mock) use ($texteTest) {
            $mock->shouldReceive('extractText')->andReturn([
                ['page' => 1, 'text' => $texteTest]
            ]);
        });
        
        $analyzer = app(PlagiatAnalyzerServiceInterface::class);

        // L'analyse doit détecter le hash identique immédiatement
        $result = $analyzer->analyze('fake_path.pdf', isTest: true);

        // 5. Assertions
        $this->assertEquals(100.0, $result['taux_global'], "Un document identique doit donner 100% via le Fast-Match Hash");
        $this->assertEquals('EXACT_MATCH', $result['decision']);
    }

    public function test_same_text_different_hash_gives_high_plagiat_rate_via_tfidf()
    {
        // 1. Initialiser les services
        $preprocessor = app(PreprocessingServiceInterface::class);

        // 2. Texte de test
        $texteTest = "Ceci est un texte de test pour vérifier la similarité TF-IDF sans passer par le hash.";

        // 3. Archiver avec un hash différent
        $user = \App\Models\User::factory()->create();
        $etudiant = \App\Models\Etudiant::create(['user_id' => $user->id, 'nom' => 'T', 'prenom' => 'U', 'email' => 't3@e.com', 'num_carte' => '1']);
        $theme = \App\Models\Theme::create(['titre' => 'T', 'description' => 'D', 'etudiant_id' => $etudiant->id, 'statut' => 'EN_ATTENTE_CHEF']);

        $rapportArchive = Rapport::create([
            'etudiant_id' => $etudiant->id,
            'theme_id' => $theme->id,
            'titre' => 'Original',
            'fichier_path' => 'o.pdf',
            'fichier_nom_original' => 'o.pdf',
            'fichier_taille' => 1,
            'hash_document' => 'WRONG_HASH', // Bypass Fast-Match
            'statut' => 'VALIDE_PLAGIAT',
        ]);

        Chapitre::create([
            'rapport_id' => $rapportArchive->id,
            'label' => 'rapport_complet',
            'contenu_texte' => implode(' ', $preprocessor->tokenizeAndStem($texteTest)),
            'hash' => 'WRONG_HASH',
            'taux_plagiat' => 0,
            'nb_mots' => count($preprocessor->tokenizeAndStem($texteTest))
        ]);

        // 4. Analyser le MÊME texte
        $this->mock(\App\Services\Plagiat\Contracts\TextExtractionServiceInterface::class, function ($mock) use ($texteTest) {
            $mock->shouldReceive('extractText')->andReturn([
                ['page' => 1, 'text' => $texteTest]
            ]);
        });

        $analyzer = app(PlagiatAnalyzerServiceInterface::class);
        $result = $analyzer->analyze('fake.pdf', isTest: true);

        // 5. Assertions
        // Puisque le texte est IDENTIQUE, même sans hash, le TF-IDF doit être proche de 100%
        $this->assertGreaterThanOrEqual(95.0, $result['taux_global'], "Le même texte sans match de hash doit donner >= 95% via TF-IDF");
        $this->assertEquals('EXACT_MATCH', $result['decision']);
    }
}
