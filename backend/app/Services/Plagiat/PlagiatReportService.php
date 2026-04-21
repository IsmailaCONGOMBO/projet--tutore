<?php

namespace App\Services\Plagiat;

use App\Models\AnalysePlagiat;
use App\Models\Chapitre;
use App\Models\Rapport;
use App\Services\Plagiat\Contracts\PlagiatReportServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlagiatReportService implements PlagiatReportServiceInterface
{
    /**
     * @inheritDoc
     */
    public function saveAnalysis(int $rapportId, array $analysisResult): AnalysePlagiat
    {
        Log::info("PlagiatReportService: Sauvegarde de l'analyse pour le rapport $rapportId.");

        return DB::transaction(function () use ($rapportId, $analysisResult) {
            $analyse = new AnalysePlagiat();
            $analyse->rapport_id = $rapportId;
            $analyse->taux_global = $analysisResult['taux_global'];
            $analyse->decision = $analysisResult['decision'];
            $analyse->payload_json = json_encode($analysisResult);

            // Initialiser les taux par défaut
            $analyse->taux_chapitre1 = null;
            $analyse->taux_chapitre2 = null;
            $analyse->taux_chapitre3 = null;
            $analyse->taux_rapport_complet = null;

            foreach ($analysisResult['segments'] as $segment) {
                // Mettre à jour l'entité AnalysePlagiat avec les champs spécifiques
                if ($segment['label'] === 'chapitre_1') {
                    $analyse->taux_chapitre1 = $segment['taux'];
                } elseif ($segment['label'] === 'chapitre_2') {
                    $analyse->taux_chapitre2 = $segment['taux'];
                } elseif ($segment['label'] === 'chapitre_3') {
                    $analyse->taux_chapitre3 = $segment['taux'];
                } elseif ($segment['label'] === 'rapport_complet') {
                    $analyse->taux_rapport_complet = $segment['taux'];
                }

                // Trouver ou recréer le chapitre associé en base
                $chapitre = Chapitre::where('rapport_id', $rapportId)
                    ->where('label', $segment['label'])
                    ->first();

                if (!$chapitre) {
                    $numero = null;
                    if (preg_match('/chapitre_(\d+)/', $segment['label'], $matches)) {
                        $numero = (int) $matches[1];
                    }

                    $chapitre = new Chapitre();
                    $chapitre->rapport_id = $rapportId;
                    $chapitre->label = $segment['label'];
                    $chapitre->numero = $numero;
                }

                // Sauvegarder le texte brut pour les comparaisons futures (Indexation)
                $chapitre->contenu_texte = $segment['text'] ?? null;
                $chapitre->hash = $segment['hash'] ?? null;
                $chapitre->taux_plagiat = $segment['taux'];
                $chapitre->nb_mots = $segment['nb_mots'];
                $chapitre->doc_similaire = $segment['doc_similaire'];
                $chapitre->save();
            }

            $analyse->save();

            // Mettre à jour le statut du rapport selon la décision
            $rapport = Rapport::find($rapportId);
            if ($rapport) {
                // Utilisation du champ 'statut' tel que défini dans le schéma de la base de données
                $rapport->statut = $analysisResult['decision'] === 'accepte' ? 'VALIDE' : 'REJETE';
                $rapport->save();
            }

            return $analyse;
        });
    }

    /**
     * @inheritDoc
     */
    public function generateHumanReadableReport(array $analysisResult): string
    {
        $tauxGlobal = $analysisResult['taux_global'];
        $decision = $analysisResult['decision'];
        $color = $decision === 'accepte' ? '#28a745' : '#dc3545';
        $statusText = $decision === 'accepte' ? 'Accepté' : 'Rejeté';

        $html = "<div style=\"font-family: Arial, sans-serif; line-height: 1.6;\">\n";
        $html .= "<h2>Rapport d'Analyse de Plagiat</h2>\n";
        
        $html .= "<h3>Taux Global : <span style=\"color: $color;\">$tauxGlobal %</span> - Statut : <strong>$statusText</strong></h3>\n";

        if ($decision === 'rejete') {
            $html .= "<p style=\"color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 5px;\">\n";
            $html .= "Le taux de plagiat dépasse le seuil toléré de 20%. Ce rapport doit être révisé.\n";
            $html .= "</p>\n";
        }

        $html .= "<h3>Détail par Segment</h3>\n";
        $html .= "<table border=\"1\" cellpadding=\"8\" cellspacing=\"0\" style=\"width: 100%; border-collapse: collapse;\">\n";
        $html .= "  <tr style=\"background-color: #f8f9fa;\">\n";
        $html .= "    <th>Segment</th>\n";
        $html .= "    <th>Mots</th>\n";
        $html .= "    <th>Taux</th>\n";
        $html .= "    <th>Source Similaire</th>\n";
        $html .= "  </tr>\n";

        foreach ($analysisResult['segments'] as $segment) {
            $label = ucfirst(str_replace('_', ' ', $segment['label']));
            $mots = $segment['nb_mots'];
            $taux = $segment['taux'] . '%';
            $doc = $segment['doc_similaire'] ? htmlspecialchars($segment['doc_similaire']) : 'Aucune';

            $html .= "  <tr>\n";
            $html .= "    <td>$label</td>\n";
            $html .= "    <td>$mots</td>\n";
            $html .= "    <td>$taux</td>\n";
            $html .= "    <td>$doc</td>\n";
            $html .= "  </tr>\n";
        }

        $html .= "</table>\n";

        $html .= "<h4>Détails des scores de similarité (par segment) :</h4>\n";
        $html .= "<ul>\n";
        foreach ($analysisResult['segments'] as $segment) {
            if ($segment['nb_mots'] > 0 && $segment['doc_similaire']) {
                $label = ucfirst(str_replace('_', ' ', $segment['label']));
                $cos = $segment['scores_detail']['cosinus'];
                $jac = $segment['scores_detail']['jaccard'];
                $ngr = $segment['scores_detail']['ngram'];
                $html .= "<li><strong>$label</strong> - Cosinus: $cos | Jaccard: $jac | N-gram: $ngr</li>\n";
            }
        }
        $html .= "</ul>\n";

        $html .= "</div>\n";

        return $html;
    }
}
