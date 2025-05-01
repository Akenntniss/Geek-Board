<?php
/**
 * FPDF - Version simplifiée
 * Pour générer des PDF dans PHP
 */

if(!class_exists('FPDF')) {
    class FPDF {
        public $page;           // page courante
        public $n;              // nombre d'objets
        public $offsets;        // tableau des offsets des objets
        public $buffer;         // buffer contenant le document PDF
        public $pages;          // tableau contenant les pages
        public $state;          // état du document
        public $compress;       // compression activée ou non
        public $DefOrientation; // orientation par défaut
        public $CurOrientation; // orientation courante
        public $OrientationChanges; // tableau des changements d'orientation
        public $k;              // échelle (points -> user unit)
        public $fwPt, $fhPt;    // dimensions de la page en points
        public $fw, $fh;        // dimensions de la page en user unit
        public $wPt, $hPt;      // dimensions courantes de la page en points
        public $w, $h;          // dimensions courantes de la page en user unit
        public $lMargin;        // marge à gauche
        public $tMargin;        // marge en haut
        public $rMargin;        // marge à droite
        public $bMargin;        // marge en bas
        public $cMargin;        // marge pour les cellules
        public $x, $y;          // position courante
        public $lasth;          // hauteur de la dernière cellule imprimée
        public $LineWidth;      // épaisseur du trait
        public $fonts;          // tableau des polices
        public $FontFiles;      // tableau des fichiers de police
        public $diffs;          // tableau des différences d'encodage
        public $FontFamily;     // famille de police courante
        public $FontStyle;      // style de police courant
        public $underline;      // souligné ou non
        public $CurrentFont;    // police courante
        public $FontSizePt;     // taille de police courante en points
        public $FontSize;       // taille de police courante en user unit
        public $DrawColor;      // couleur de trait
        public $FillColor;      // couleur de remplissage
        public $TextColor;      // couleur de texte
        public $ColorFlag;      // indicateur pour les couleurs
        public $ws;             // espacement des mots
        public $images;         // tableau des images
        public $PageLinks;      // tableau des liens
        public $links;          // tableau des liens internes
        public $AutoPageBreak;  // saut de page automatique
        public $PageBreakTrigger; // seuil de déclenchement du saut de page
        public $InHeader;       // flag si dans l'en-tête
        public $InFooter;       // flag si dans le pied de page
        public $ZoomMode;       // mode de zoom
        public $LayoutMode;     // mode de mise en page
        public $title;          // titre
        public $subject;        // sujet
        public $author;         // auteur
        public $keywords;       // mots-clés
        public $creator;        // créateur
        public $AliasNbPages;   // alias pour le nombre de pages
        public $PDFVersion;     // version PDF

        /**
         * Constructeur
         * @param string $orientation Orientation par défaut
         * @param string $unit Unité de mesure utilisateur
         * @param mixed $size Format de la page
         */
        public function __construct($orientation='P', $unit='mm', $size='A4') {
            // Initialisation
            $this->page = 0;
            $this->n = 2;
            $this->buffer = '';
            $this->pages = array();
            $this->OrientationChanges = array();
            $this->state = 0;
            $this->fonts = array();
            $this->FontFiles = array();
            $this->diffs = array();
            $this->images = array();
            $this->links = array();
            $this->InHeader = false;
            $this->InFooter = false;
            $this->lasth = 0;
            $this->FontFamily = '';
            $this->FontStyle = '';
            $this->FontSizePt = 12;
            $this->underline = false;
            $this->DrawColor = '0 G';
            $this->FillColor = '0 g';
            $this->TextColor = '0 g';
            $this->ColorFlag = false;
            $this->ws = 0;
            
            // Échelle
            if($unit=='pt')
                $this->k = 1;
            elseif($unit=='mm')
                $this->k = 72/25.4;
            elseif($unit=='cm')
                $this->k = 72/2.54;
            elseif($unit=='in')
                $this->k = 72;
            else
                $this->Error('Unité incorrecte: '.$unit);
            
            // Format de la page
            if(is_string($size)) {
                $size = strtolower($size);
                if($size=='a3')
                    $size = array(841.89,1190.55);
                elseif($size=='a4')
                    $size = array(595.28,841.89);
                elseif($size=='a5')
                    $size = array(420.94,595.28);
                elseif($size=='letter')
                    $size = array(612,792);
                elseif($size=='legal')
                    $size = array(612,1008);
                else
                    $this->Error('Format de page inconnu: '.$size);
                $this->fwPt = $size[0];
                $this->fhPt = $size[1];
            } else {
                $this->fwPt = $size[0]*$this->k;
                $this->fhPt = $size[1]*$this->k;
            }
            
            $this->fw = $this->fwPt/$this->k;
            $this->fh = $this->fhPt/$this->k;
            
            // Orientation
            $orientation = strtolower($orientation);
            if($orientation=='p' || $orientation=='portrait') {
                $this->DefOrientation = 'P';
                $this->wPt = $this->fwPt;
                $this->hPt = $this->fhPt;
            } elseif($orientation=='l' || $orientation=='landscape') {
                $this->DefOrientation = 'L';
                $this->wPt = $this->fhPt;
                $this->hPt = $this->fwPt;
            } else
                $this->Error('Orientation incorrecte: '.$orientation);
            
            $this->CurOrientation = $this->DefOrientation;
            $this->w = $this->wPt/$this->k;
            $this->h = $this->hPt/$this->k;
            
            // Marges (1 cm)
            $margin = 28.35/$this->k;
            $this->SetMargins($margin, $margin);
            
            // Saut de page automatique
            $this->SetAutoPageBreak(true, 2*$margin);
            
            // Compression
            $this->SetCompression(true);
            
            // Mise en page par défaut
            $this->SetDisplayMode('default');
            
            // Définir l'alias pour le nombre total de pages
            $this->AliasNbPages();
        }

        /**
         * Afficher une erreur et arrêter le script
         * @param string $msg Le message d'erreur
         */
        public function Error($msg) {
            die('<b>Erreur FPDF:</b> ' . $msg);
        }
        
        /**
         * Commencer le document
         */
        public function Open() {
            $this->state = 1;
        }

        /**
         * Définir les marges gauche, droite et haute
         * @param float $left Marge gauche
         * @param float $top Marge haute
         * @param float $right Marge droite (si non spécifiée, égale à la gauche)
         */
        public function SetMargins($left, $top, $right=null) {
            $this->lMargin = $left;
            $this->tMargin = $top;
            if($right===null)
                $this->rMargin = $left;
            else
                $this->rMargin = $right;
        }

        /**
         * Activer ou désactiver le saut de page automatique
         * @param boolean $auto Activer ou désactiver
         * @param float $margin Marge basse
         */
        public function SetAutoPageBreak($auto, $margin=0) {
            $this->AutoPageBreak = $auto;
            $this->bMargin = $margin;
            $this->PageBreakTrigger = $this->h-$margin;
        }

        /**
         * Activer ou désactiver la compression
         * @param boolean $compress Activer ou désactiver
         */
        public function SetCompression($compress) {
            $this->compress = $compress;
        }

        /**
         * Définir le mode d'affichage
         * @param mixed $zoom Mode de zoom
         * @param string $layout Mode de mise en page
         */
        public function SetDisplayMode($zoom, $layout='default') {
            $this->ZoomMode = $zoom;
            $this->LayoutMode = $layout;
        }

        /**
         * Définir un alias pour le nombre total de pages
         * @param string $alias L'alias
         */
        public function AliasNbPages($alias='{nb}') {
            $this->AliasNbPages = $alias;
        }

        /**
         * Ajouter une page
         * @param string $orientation Orientation
         * @param mixed $size Format
         */
        public function AddPage($orientation='', $size='') {
            // Commencer une nouvelle page
            if($this->state==0)
                $this->Open();
            
            // Sauvegarder la taille des pages
            if($orientation=='')
                $orientation = $this->CurOrientation;
            
            $this->CurOrientation = $orientation;
            $this->page++;
            $this->pages[$this->page] = '';
            
            // Définir les dimensions
            $this->wPt = $this->fwPt;
            $this->hPt = $this->fhPt;
            $this->w = $this->fw;
            $this->h = $this->fh;
            
            // Initialiser la page
            $this->x = $this->lMargin;
            $this->y = $this->tMargin;
            
            // En-tête
            $this->InHeader = true;
            $this->Header();
            $this->InHeader = false;
        }

        /**
         * En-tête de page - à surcharger dans les classes dérivées
         */
        public function Header() {
            // À implémenter dans les classes dérivées
        }

        /**
         * Pied de page - à surcharger dans les classes dérivées
         */
        public function Footer() {
            // À implémenter dans les classes dérivées
        }

        /**
         * Définir la position courante
         * @param float $x Abscisse
         * @param float $y Ordonnée
         */
        public function SetY($y) {
            $this->y = $y;
        }

        /**
         * Définir la police
         * @param string $family Famille de police
         * @param string $style Style (B, I, U ou combinaison)
         * @param float $size Taille en points
         */
        public function SetFont($family, $style='', $size=0) {
            $family = strtolower($family);
            $this->FontFamily = $family;
            $this->FontStyle = $style;
            $this->FontSizePt = $size;
            $this->FontSize = $size/$this->k;
        }

        /**
         * Cellule
         * @param float $w Largeur
         * @param float $h Hauteur
         * @param string $txt Texte
         * @param mixed $border Bordure (0: aucune, 1: toutes, LTRB: côtés)
         * @param int $ln Position après la cellule (0: à droite, 1: au début de la ligne suivante, 2: dessous)
         * @param string $align Alignement (L: gauche, C: centre, R: droite)
         * @param boolean $fill Remplissage ou non
         * @param string $link URL ou identifiant retourné par AddLink()
         */
        public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
            // Texte
            if($txt!=='')
                $this->Write($h, $txt);
            
            // Position
            if($ln==1)
            {
                // Aller à la ligne suivante
                $this->x = $this->lMargin;
                $this->y += $h;
            }
            elseif($ln==2)
            {
                // Aller en dessous
                $this->y += $h;
            }
        }

        /**
         * Écrire du texte
         * @param float $h Hauteur de ligne
         * @param string $txt Texte
         * @param mixed $link URL ou identifiant retourné par AddLink()
         */
        public function Write($h, $txt, $link='') {
            $this->lasth = $h;
        }

        /**
         * Créer une ligne
         * @param float $x1 Abscisse du premier point
         * @param float $y1 Ordonnée du premier point
         * @param float $x2 Abscisse du second point
         * @param float $y2 Ordonnée du second point
         */
        public function Line($x1, $y1, $x2, $y2) {
            // Méthode vide pour cette version simplifiée
        }

        /**
         * Retour à la ligne
         * @param float $h Hauteur
         */
        public function Ln($h=null) {
            if($h===null)
                $h = $this->lasth;
            $this->y += $h;
            $this->x = $this->lMargin;
        }

        /**
         * Obtenir la position Y actuelle
         * @return float
         */
        public function GetY() {
            return $this->y;
        }

        /**
         * Ajouter une image
         * @param string $file Chemin du fichier
         * @param float $x Abscisse
         * @param float $y Ordonnée
         * @param float $w Largeur
         * @param float $h Hauteur
         * @param string $type Type d'image
         * @param mixed $link Lien
         */
        public function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {
            // Méthode vide pour cette version simplifiée
        }

        /**
         * Générer le PDF
         * @param string $name Nom du fichier
         * @param string $dest Destination (I: navigateur, D: téléchargement, F: fichier local, S: chaîne)
         * @return string
         */
        public function Output($name='', $dest='I') {
            // Créer un PDF simple avec un texte
            $pdf = "%PDF-1.7\n";
            $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
            $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
            $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
            $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";
            $pdf .= "5 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 12 Tf\n70 700 Td\n(Etiquette PDF) Tj\nET\nendstream\nendobj\n";
            $pdf .= "xref\n0 6\n0000000000 65535 f\n0000000010 00000 n\n0000000056 00000 n\n0000000111 00000 n\n0000000212 00000 n\n0000000293 00000 n\n";
            $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n384\n%%EOF";
            
            if($dest=='I') {
                // Envoyer au navigateur
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $pdf;
            } elseif($dest=='D') {
                // Téléchargement
                header('Content-Type: application/x-download');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $pdf;
            } elseif($dest=='F') {
                // Sauvegarder dans un fichier local
                file_put_contents($name, $pdf);
            } elseif($dest=='S') {
                // Retourner comme une chaîne
                return $pdf;
            }
            return '';
        }
    }
} 