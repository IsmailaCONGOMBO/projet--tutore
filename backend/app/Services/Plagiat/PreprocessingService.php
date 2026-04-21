<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\PreprocessingServiceInterface;
use Wamania\Snowball\Stemmer\French;

class PreprocessingService implements PreprocessingServiceInterface
{
    /**
     * Liste des mots vides (stop words) en français.
     * Environ 150 mots courants.
     *
     * @var array
     */
    protected $stopWords = [
        'a', 'abord', 'absolument', 'afin', 'ah', 'ai', 'aie', 'ailleurs', 'ainsi', 'ait', 
        'allaient', 'allo', 'allons', 'allô', 'alors', 'anterieur', 'anterieure', 'anterieures', 
        'apres', 'après', 'as', 'assez', 'attendu', 'au', 'aucun', 'aucune', 'aujourd', 
        'aujourd\'hui', 'aupres', 'auquel', 'aura', 'auraient', 'aurait', 'auront', 'aussi', 
        'autre', 'autrefois', 'autrement', 'autres', 'autrui', 'aux', 'auxquelles', 'auxquels', 
        'avaient', 'avais', 'avait', 'avant', 'avec', 'avoir', 'avons', 'ayant', 'b', 'bah', 
        'bas', 'basee', 'bat', 'beau', 'beaucoup', 'bien', 'bigre', 'boum', 'bravo', 'brrr', 
        'c', 'car', 'ce', 'ceci', 'cela', 'celle', 'celle-ci', 'celle-la', 'celles', 'celles-ci', 
        'celles-la', 'celui', 'celui-ci', 'celui-la', 'cent', 'cependant', 'certain', 'certaine', 
        'certaines', 'certains', 'certes', 'ces', 'cet', 'cette', 'ceux', 'ceux-ci', 'ceux-la', 
        'chacun', 'chacune', 'chaque', 'cher', 'chers', 'chez', 'chiche', 'chut', 'chère', 'chères', 
        'ci', 'cinq', 'cinquantaine', 'cinquante', 'cinquantième', 'cinquième', 'clac', 'clic', 
        'combien', 'comme', 'comment', 'compris', 'concernant', 'contre', 'couic', 'crac', 'd', 
        'da', 'dans', 'de', 'debout', 'dedans', 'dehors', 'deja', 'delà', 'depuis', 'derriere', 
        'des', 'desormais', 'desquelles', 'desquels', 'dessous', 'dessus', 'deux', 'deuxième', 
        'deuxièmement', 'devant', 'devers', 'devra', 'different', 'differentes', 'differents', 
        'différent', 'différente', 'différentes', 'différents', 'dire', 'directe', 'directement', 
        'dit', 'dite', 'dits', 'divers', 'diverse', 'diverses', 'dix', 'dix-huit', 'dix-neuf', 
        'dix-sept', 'dixième', 'doit', 'doivent', 'donc', 'dont', 'douze', 'douzième', 'dring', 
        'du', 'duquel', 'durant', 'dès', 'désormais', 'e', 'effet', 'egale', 'egalement', 'egales', 
        'eh', 'elle', 'elle-même', 'elles', 'elles-mêmes', 'en', 'encore', 'enfin', 'entre', 
        'envers', 'environ', 'es', 'est', 'et', 'etant', 'etc', 'etre', 'eu', 'euh', 'eux', 
        'eux-mêmes', 'exactement', 'excepté', 'extenso', 'exterieur', 'f', 'fais', 'faisaient', 
        'faisant', 'fait', 'façon', 'feront', 'fi', 'flac', 'floc', 'font', 'g', 'gens', 'h', 'ha', 
        'hein', 'hem', 'hep', 'hi', 'ho', 'holà', 'hop', 'hormis', 'hors', 'hou', 'houp', 'hue', 
        'hui', 'huit', 'huitième', 'hum', 'hurrah', 'hé', 'hélas', 'i', 'il', 'ils', 'importe', 
        'j', 'je', 'jusqu', 'jusque', 'juste', 'k', 'l', 'la', 'laisser', 'laquelle', 'las', 'le', 
        'lequel', 'les', 'lesquelles', 'lesquels', 'leur', 'leurs', 'longtemps', 'lors', 'lorsque', 
        'lui', 'lui-meme', 'lui-même', 'là', 'lès', 'm', 'ma', 'maint', 'maintenant', 'mais', 
        'malgre', 'malgré', 'maximale', 'me', 'meme', 'memes', 'merci', 'mes', 'mien', 'mienne', 
        'miennes', 'miens', 'mille', 'mince', 'minimale', 'moi', 'moi-meme', 'moi-même', 'moindres', 
        'moins', 'mon', 'moyennant', 'multiple', 'multiples', 'même', 'mêmes', 'n', 'na', 'naturel', 
        'naturelle', 'naturelles', 'ne', 'neanmoins', 'necessaire', 'necessairement', 'neuf', 
        'neuvième', 'ni', 'nombreuses', 'nombreux', 'non', 'nos', 'notamment', 'notre', 'nous', 
        'nous-mêmes', 'nouveau', 'nul', 'néanmoins', 'nôtre', 'nôtres', 'o', 'oh', 'ohé', 'ollé', 
        'olé', 'on', 'ont', 'onze', 'onzième', 'ore', 'ou', 'ouf', 'ouias', 'oust', 'ouste', 'outre', 
        'ouvert', 'ouverte', 'ouverts', 'o|', 'où', 'p', 'paf', 'pan', 'par', 'parce', 'parfois', 
        'parle', 'parlent', 'parler', 'parmi', 'parseme', 'partant', 'particulier', 'particulière', 
        'particulièrement', 'pas', 'passé', 'pendant', 'pense', 'permet', 'personne', 'peu', 'peut', 
        'peuvent', 'peux', 'pff', 'pfft', 'pfut', 'pif', 'pire', 'plein', 'plouf', 'plus', 'plusieurs', 
        'plutôt', 'possessif', 'possessifs', 'possible', 'possibles', 'pouah', 'pour', 'pourquoi', 
        'pourrais', 'pourrait', 'pouvait', 'prealable', 'precisement', 'premier', 'première', 
        'premièrement', 'pres', 'probable', 'probante', 'procedant', 'proche', 'près', 'psitt', 'pu', 
        'puis', 'puisque', 'pur', 'pure', 'q', 'qu', 'quand', 'quant', 'quant-à-soi', 'quanta', 
        'quarante', 'quatorze', 'quatre', 'quatre-vingt', 'quatrième', 'quatrièmement', 'que', 
        'quel', 'quelconque', 'quelle', 'quelles', 'quelqu\'un', 'quelque', 'quelques', 'quels', 
        'qui', 'quiconque', 'quinze', 'quoi', 'quoique', 'r', 'revoici', 'revoilà', 'rien', 's', 'sa', 
        'sacrebleu', 'sait', 'sans', 'sapristi', 'sauf', 'se', 'seize', 'selon', 'sept', 'septième', 
        'sera', 'seraient', 'serait', 'seront', 'ses', 'seul', 'seule', 'seulement', 'si', 'sien', 
        'sienne', 'siennes', 'siens', 'sinon', 'six', 'sixième', 'soi', 'soi-même', 'soit', 'soixante', 
        'son', 'sont', 'sous', 'souvent', 'specifique', 'specifiques', 'speculatif', 'stop', 
        'strictement', 'subtiles', 'suffisant', 'suffisante', 'suffit', 'suis', 'suit', 'suivant', 
        'suivante', 'suivantes', 'suivants', 'suivre', 'superpose', 'sur', 'surtout', 't', 'ta', 'tac', 
        'tant', 'tardive', 'te', 'tel', 'telle', 'tellement', 'telles', 'tels', 'tenant', 'tend', 'tenir', 
        'tente', 'tes', 'tic', 'tien', 'tienne', 'tiennes', 'tiens', 'toc', 'toi', 'toi-même', 'ton', 
        'touchant', 'toujours', 'tous', 'tout', 'toute', 'toutefois', 'toutes', 'treize', 'trente', 'tres', 
        'trois', 'troisième', 'troisièmement', 'trop', 'très', 'tsoin', 'tsouin', 'tu', 'té', 'u', 'un', 
        'une', 'unes', 'uniformement', 'unique', 'uniques', 'uns', 'v', 'va', 'vais', 'vas', 'vers', 'via', 
        'vif', 'vifs', 'vingt', 'vivat', 'vive', 'vives', 'vlan', 'voici', 'voilà', 'vont', 'vos', 'votre', 
        'vous', 'vous-mêmes', 'vu', 'vé', 'vôtre', 'vôtres', 'w', 'x', 'y', 'z', 'zut', 'à', 'â', 'ça', 
        'ès', 'étaient', 'étais', 'était', 'étant', 'été', 'être', 'ô'
    ];

    /**
     * @var French
     */
    protected $stemmer;

    public function __construct()
    {
        // Initialiser le stemmer pour le français
        $this->stemmer = new French();
    }

    /**
     * Nettoie, tokenize et stemmise le texte brut.
     *
     * @param string $text
     * @return array
     */
    /**
     * Génère un hash unique du texte nettoyé pour une comparaison ultra-rapide.
     * 
     * @param string $text
     * @return string
     */
    public function generateHash(string $text): string
    {
        $cleaned = $this->preprocessText($text);
        // Supprimer tous les espaces pour un hash robuste à la mise en page
        $compact = str_replace(' ', '', $cleaned);
        return hash('sha256', $compact);
    }

    public function tokenizeAndStem(string $text): array
    {
        // 1. Conversion en minuscules
        $text = mb_strtolower($text, 'UTF-8');

        // 2. Gestion des ligatures et caractères spéciaux avant suppression
        $text = str_replace(['œ', 'æ', '«', '»', '“', '”', '…'], ['oe', 'ae', '"', '"', '"', '"', '...'], $text);

        // 3. Suppression des accents
        $text = $this->removeAccents($text);

        // 4. Suppression de la ponctuation et caractères non-alphanumériques (garder espaces)
        // Utilisation de \p{L} pour garder les lettres de n'importe quelle langue si besoin
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);

        // Réduire les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            return [];
        }

        // Tokenisation (séparation par espace)
        $tokens = explode(' ', $text);

        $cleanedTokens = [];

        foreach ($tokens as $token) {
            // 5. Suppression des mots vides et tokens trop courts
            if (!in_array($token, $this->stopWords) && mb_strlen($token) > 1) {
                // 6. Stemming
                $stemmedToken = $this->stemmer->stem($token);
                if (!empty($stemmedToken)) {
                    $cleanedTokens[] = $stemmedToken;
                }
            }
        }

        return $cleanedTokens;
    }

    /**
     * Retourne le texte nettoyé et stemmisé sous forme de chaîne.
     *
     * @param string $text
     * @return string
     */
    public function preprocessText(string $text): string
    {
        $tokens = $this->tokenizeAndStem($text);
        return implode(' ', $tokens);
    }

    /**
     * Supprime les accents d'une chaîne UTF-8.
     *
     * @param string $str
     * @return string
     */
    protected function removeAccents($str): string
    {
        $unwanted_array = [
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'œ'=>'oe', 'Œ'=>'OE'
        ];
        return strtr($str, $unwanted_array);
    }
}
