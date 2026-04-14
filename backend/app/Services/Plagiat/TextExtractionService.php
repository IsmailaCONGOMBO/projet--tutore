<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\TextExtractionServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class TextExtractionService implements TextExtractionServiceInterface
{
    /**
     * @var Parser
     */
    protected $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Extrait le texte d'un fichier PDF, page par page.
     *
     * @param string $pdfPath
     * @return array
     * @throws Exception
     */
    public function extractText(string $pdfPath): array
    {
        if (!file_exists($pdfPath)) {
            Log::error("TextExtractionService: Fichier PDF introuvable au chemin $pdfPath");
            throw new Exception("Le fichier PDF n'existe pas : " . $pdfPath);
        }

        try {
            // Analyser le PDF
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            $extractedData = [];

            foreach ($pages as $index => $page) {
                // $index est 0-basé, donc page 1 = index + 1
                $pageNumber = $index + 1;
                $text = $page->getText();

                $extractedData[] = [
                    'page' => $pageNumber,
                    'text' => $text,
                ];
            }

            Log::info("TextExtractionService: Extraction réussie pour $pdfPath, " . count($extractedData) . " pages extraites.");
            return $extractedData;

        } catch (Exception $e) {
            Log::error("TextExtractionService: Erreur lors de l'extraction du PDF $pdfPath - " . $e->getMessage());
            throw new Exception("Erreur lors de la lecture du PDF : " . $e->getMessage());
        }
    }
}
