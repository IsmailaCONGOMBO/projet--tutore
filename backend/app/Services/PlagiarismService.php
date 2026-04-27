<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlagiarismService
{
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extrait le texte d'un fichier PDF.
     */
    public function extractText($filePath)
    {
        try {
            $pdfContent = Storage::disk('public')->path($filePath);
            $pdf = $this->parser->parseFile($pdfContent);
            return $pdf->getText();
        } catch (\Exception $e) {
            \Log::error("Erreur d'extraction PDF : " . $e->getMessage());
            return "";
        }
    }

    /**
     * Compare deux textes et retourne les passages suspects et un taux global.
     */
    public function compareTexts($newText, $oldText)
    {
        // Nettoyage sommaire
        $newSentences = $this->splitIntoSentences($newText);
        $oldTextClean = $this->cleanText($oldText);

        $suspectPassages = [];
        $matchedWordsCount = 0;
        $totalWordsCount = str_word_count($newText);

        foreach ($newSentences as $sentence) {
            $cleanSentence = $this->cleanText($sentence);
            if (strlen($cleanSentence) < 20) continue; // Ignorer les phrases trop courtes

            // Recherche exacte ou très proche (simplifiée pour le prototype)
            if (stripos($oldTextClean, $cleanSentence) !== false) {
                $suspectPassages[] = [
                    'texte' => $sentence,
                    'similarite' => 100 // Dans ce prototype, on cherche l'exactitude
                ];
                $matchedWordsCount += str_word_count($sentence);
            }
        }

        $taux = $totalWordsCount > 0 ? ($matchedWordsCount / $totalWordsCount) * 100 : 0;

    /**
     * Compare deux documents par hachage (SHA-256) après normalisation.
     * Détecte si deux documents sont strictement identiques (hors espaces/casse).
     */
    public function compareHash($text1, $text2)
    {
        $hash1 = $this->calculateHash($text1);
        $hash2 = $this->calculateHash($text2);

        return $hash1 === $hash2;
    }

    /**
     * Calcule le hash SHA-256 d'un texte après un nettoyage ultra-robuste.
     * Ignore la casse, les accents, la ponctuation et TOUS les espaces.
     */
    public function calculateHash($text)
    {
        // 1. Passage en minuscule
        $text = mb_strtolower($text, 'UTF-8');
        
        // 2. Suppression des accents
        $text = $this->removeAccents($text);
        
        // 3. Suppression de TOUT ce qui n'est pas alphanumérique [a-z0-9]
        // Cela élimine ponctuation, espaces, sauts de ligne, symboles...
        $text = preg_replace('/[^a-z0-9]/', '', $text);

        return hash('sha256', $text);
    }

    /**
     * Supprime les accents d'une chaîne.
     */
    protected function removeAccents($str)
    {
        $unwanted_array = [
            'š'=>'s', 'ž'=>'z', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 
            'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'œ'=>'oe'
        ];
        return strtr($str, $unwanted_array);
    }
}

    private function splitIntoSentences($text)
    {
        // Découpage par ponctuation de fin de phrase
        return preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function cleanText($text)
    {
        // Supprimer les espaces multiples, les retours à la ligne, et mettre en minuscule
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(mb_strtolower($text));
    }
}
