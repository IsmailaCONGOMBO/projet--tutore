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

        return [
            'taux' => round($taux, 2),
            'passages' => $suspectPassages
        ];
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
