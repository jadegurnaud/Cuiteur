<?php

/*************************************************************************************
 *        Bibliothèque de fonctions génériques          
 * 
 * Les régles de nommage sont les suivantes.
 * Les fonctions commencent par le préfixe em (cf e-ric m-erlet) 
 * pour les différencier
 * des fonctions php.
 *
 * Généralement on trouve ensuite un terme définisant le "domaine" de la fonction :
 *  _aff_   la fonction affiche du code html / texte destiné au navigateur
 *  _html_  la fonction renvoie du code html / texte
 *  _bd_    la fonction gère la base de données
 *
 *************************************************************************************/
 
//____________________________________________________________________________
/**
 * Arrêt du script si erreur de base de données 
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 *      - lors de la phase de connexion au serveur MySQL
 *      - ou lorsque l'envoi d'une requête échoue
 *
 * @param array    $err    Informations utiles pour le débogage
 */
function gk_bd_erreur_exit(array $err):void {
    ob_end_clean(); // Suppression de tout ce qui a pu être déja généré

    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
            '<title>Erreur',  
            IS_DEV ? ' base de données': '', '</title>',
            '</head><body>';
    if (IS_DEV){
        // Affichage de toutes les infos contenues dans $err
        echo    '<h4>', $err['titre'], '</h4>',
                '<pre>', 
                    '<strong>Erreur mysqli</strong> : ',  $err['code'], "\n",
                    utf8_encode($err['message']), "\n";
                    //$err['message'] est une chaîne encodée en ISO-8859-1
        if (isset($err['autres'])){
            echo "\n";
            foreach($err['autres'] as $cle => $valeur){
                echo    '<strong>', $cle, '</strong> :', "\n", $valeur, "\n";
            }
        }
        echo    "\n",'<strong>Pile des appels de fonctions :</strong>', "\n", $err['appels'], 
                '</pre>';
    }
    else {
        echo 'Une erreur s\'est produite';
    }
    
    echo    '</body></html>';
    
    if (! IS_DEV){
        // Mémorisation des erreurs dans un fichier de log
        $fichier = fopen('error.log', 'a');
        if($fichier){
            fwrite($fichier, '['.date('d/m/Y').' '.date('H:i:s')."]\n");
            fwrite($fichier, $err['titre']."\n");
            fwrite($fichier, "Erreur mysqli : {$err['code']}\n");
            fwrite($fichier, utf8_encode($err['message'])."\n");
            if (isset($err['autres'])){
                foreach($err['autres'] as $cle => $valeur){
                    fwrite($fichier,"{$cle} :\n{$valeur}\n");
                }
            }
            fwrite($fichier,"Pile des appels de fonctions :\n");
            fwrite($fichier, "{$err['appels']}\n\n");
            fclose($fichier);
        }
    }
    exit(1);        // ==> ARRET DU SCRIPT
}
 
//____________________________________________________________________________
/** 
 *  Ouverture de la connexion à la base de données en gérant les erreurs.
 *
 *  En cas d'erreur de connexion, une page "propre" avec un message d'erreur
 *  adéquat est affiché ET le script est arrêté.
 *
 *  @return mysqli  objet connecteur à la base de données
 */
function gk_bd_connect(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try{
        $conn = mysqli_connect('localhost', 'kruzic_u', 'kruzic_p', 'kruzic_cuiteur');
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de connexion';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString(); //Pile d'appels
        $err['autres'] = array('Paramètres' =>   'BD_SERVER : '. BD_SERVER
                                                    ."\n".'BD_USER : '. BD_USER
                                                    ."\n".'BD_PASS : '. BD_PASS
                                                    ."\n".'BD_NAME : '. BD_NAME);
        gk_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
    try{
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8');
        return $conn;     // ===> Sortie connexion OK
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur lors de la définition du charset';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        gk_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
}

//____________________________________________________________________________
/**
 * Envoie une requête SQL au serveur de BdD en gérant les erreurs.
 *
 * En cas d'erreur, une page propre avec un message d'erreur est affichée et le
 * script est arrêté. Si l'envoi de la requête réussit, cette fonction renvoie :
 *      - un objet de type mysqli_result dans le cas d'une requête SELECT
 *      - true dans le cas d'une requête INSERT, DELETE ou UPDATE
 *
 * @param   mysqli              $bd     Objet connecteur sur la base de données
 * @param   string              $sql    Requête SQL
 * @return  mysqli_result|bool          Résultat de la requête
 */
function gk_bd_send_request(mysqli $bd, string $sql): mysqli_result|bool {
    try{
        return mysqli_query($bd, $sql);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de requête';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        $err['autres'] = array('Requête' => $sql);
        gk_bd_erreur_exit($err);    // ==> ARRET DU SCRIPT
    }
}

//____________________________________________________________________________
/**
 *  Fonction affichant le début du code HTML d'une page.
 *
 *  @param  string  $titre  Titre de la page
 *  @param  ?string $css    Chemin relatif vers la feuille de style CSS.
 */
function gk_aff_debut(string $titre, ?string $css = null):void {
    $css = ($css) ? "<link rel='stylesheet' type='text/css' href='$css'>" : '';
    echo 
        '<!DOCTYPE html>',
        '<html lang="fr">',
            '<head>',
                '<title>', $titre, '</title>', 
                '<meta charset="UTF-8">',
                $css,
                '<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">',
            '</head>',
            '<body>';
}

//____________________________________________________________________________
/**
 *  Fonction affichant la fin du code HTML d'une page.
 */
function gk_aff_fin():void {
    echo '</body></html>';
}

//_______________________________________________________________
/**
* Transformation d'un date amj en clair.
*
* Aucune vérification n'est faite sur la validité de la date car
* on considère que c'est bien une date valide sous la forme aaaammjj
*
* @param  int       $amj    La date sous la forme aaaammjj
* @return string            La date sous la forme jj mois aaaa (1 janvier 2000)
*/
function gk_amj_clair(int $amj):string {
    $mois = array('', ' janvier ', ' février ', ' mars ', ' avril ', ' mai ', ' juin',
                ' juillet ', ' aôut ', ' septembre ', ' octobre ', ' novembre ', ' décembre ');
                
    $jj = (int)substr($amj, -2);
    $mm = (int)substr($amj, -4, 2);

    return $jj.$mois[$mm].substr($amj, 0, -4); //fonctionne même si l'année est inférieure à 1000
}

//_______________________________________________________________
/**
* Transformation d'une heure HH:MM:SS en clair.
*
*
* @param    string  $heure  L'heure sous la forme HH:MM:SS
* @return   string          L'heure sous la forme HHhMMmn (9h08mn)
*/
function gk_heure_clair(string $heure):string {
    
    $h = (int)substr($heure, 0, 2);
    $m = substr($heure, 3, 2);
    if (! gk_est_entier($m)){ //$heure est une chaîne provenant de la BdD, donc méfiance
        $m = '00';
    }
    return "{$h}h{$m}mn";
}


//_______________________________________________________________
/**
* Transformation d'une date jj:mm:aaaa en clair.
*
*
* @param    string  $date  La date sous la forme jj/mm/aaaa
* @return   string          La date sous la forme aaaammjj
*/
function gk_date_clair_string(string $date):string {
    
    $j = (int)substr($date, 0, 2);
    $m = (int)substr($date, 3, 2);
    $a = (int)substr($date, 6, 4);
    if($j<10){
        $j='0'.$j;

    }
    if($m<10){
        $m='0'.$m;

    }
    return "{$a}{$m}{$j}";
}
//_______________________________________________________________
/**
* Transformation d'une date du jour en date du lundi de la semaine.
*
*
* @param    string  $date  La date sous la forme j/mm/aaaa
* @return   string          La date sous la forme aaaammjj
*/
function gk_date_clair_semaine(string $date):string {
    $j = (int)substr($date, 0, 1);
    $m = (int)substr($date, 2, 2);
    $a = (int)substr($date, 5, 4);
    if($j<10){
        $j='0'.$j;

    }
    if($m<10){
        $m='0'.$m;

    }
    
    return "{$a}{$m}{$j}";
}
//_______________________________________________________________
/**
* Transformation d'une date du jour en date du premier jour du mois.
*
*
* @param    string  $date  La date sous la forme jj/mm/aaaa
* @return   string          La date sous la forme aaaammjj
*/
function gk_date_clair_mois(string $date):string {
    
    $j = (int)substr($date, 0, 2);
    $m = (int)substr($date, 3, 2);
    $a = (int)substr($date, 6, 4);
    if($j!=1){
        $j=01;
    }
    if($j<10){
        $j='0'.$j;

    }
    if($m<10){
        $m='0'.$m;

    }
    
    return "{$a}{$m}{$j}";
}
//_______________________________________________________________
/**
* Transformation d'une date du jour en date du premier jour de l'année.
*
*
* @param    string  $date  La date sous la forme jj/mm/aaaa
* @return   string          La date sous la forme aaaammjj
*/
function gk_date_clair_annee(string $date):string {
    
    $j = (int)substr($date, 0, 2);
    $m = (int)substr($date, 3, 2);
    $a = (int)substr($date, 6, 4);
    if($j!=1 || $m!=1){
        $j=01;
        $m=01;

    }
    if($j<10){
        $j='0'.$j;

    }
    if($m<10){
        $m='0'.$m;

    }
    
    return "{$a}{$m}{$j}";
}

//_______________________________________________________________
/**
* Transformation d'un date amj en clair slash.
* 
* @param    int     amj  La date sous la forme aaaammjj
* @return   string      La date sous la forme aaaa-mm-jj
*
*/
function gk_amj_standard(int $amj):string {
    return date_format(date_create($amj),'Y-m-d');
}

//_______________________________________________________________
/**
* Renvoie le code HTML d'un élément a
*
* supporte un seul couple 'nom=valeur' dans la queryString
*
*
* @param  string    $url                url du lien
* @param  string    $support_lien       support du lien
* @param  ?string   $query_string_nom   nom du couple 'nom=valeur'
* @param  ?string   $query_string_val   valeur du couple 'nom=valeur'
* @param  ?string   $title              info bulle
* @return string                        Le code HTML du lien
*/
function gk_html_a(string $url, string $support_lien, ?string $query_string_nom = null, ?string $query_string_val = null, ?string $title = null):string{
    $title = $title ? " title='$title'" : '';
    $query_string = $query_string_nom ? "?{$query_string_nom}=".urlencode($query_string_val) : '';

    return "<a href='{$url}{$query_string}'{$title}>{$support_lien}</a>";
}

//_______________________________________________________________
/**
 * Teste si une valeur est une valeur entière
 *
 * @param   mixed    $x  valeur à tester
 * @return  bool     TRUE si entier, FALSE sinon
 */
function gk_est_entier(mixed $x):bool {
    return is_numeric($x) && ($x == (int) $x);
}

//_______________________________________________________________
/** 
 *  Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *      - de saisies de l'utilisateur (formulaires)
 *      - de la bdD
 *  Permet de se protéger contre les attaques XSS (Cross site scripting)
 *  Convertit tous les caractères éligibles en entités HTML, notamment :
 *      - les caractères ayant une signification spéciales en HTML (<, >, ", ', ...)
 *      - les caractères accentués
 * 
 *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
 *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées. 
 *
 *  @param  array|string  $content   la chaine à protéger ou un tableau contenant des chaines à protéger 
 *  @return array|string             la chaîne protégée ou le tableau
 */
function gk_html_proteger_sortie(array|string $content): array|string {
    if (is_array($content)) {
        foreach ($content as &$value) {
            if (is_array($value) || is_string($value)){
                $value = gk_html_proteger_sortie($value);
            }
        }
        unset ($value); // à ne pas oublier (de façon générale)
        return $content;
    }
    if (is_string($content)){
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
    return $content;
}

/**
*  Protection des entrées (chaînes envoyées au serveur MySQL)
* 
* Avant insertion dans une requête SQL, certains caractères spéciaux doivent être échappés (", ', ...).
* Toutes les chaines de caractères provenant de saisies de l'utilisateur doivent être protégées 
* en utilisant la fonction mysqli_real_escape_string() (si elle est disponible)
* Cette dernière fonction :
* - protège les caractères spéciaux d'une chaîne (en particulier les guillemets)
* - permet de se protéger contre les attaques de type injections SQL. 
*
*  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
*  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées.  
*   
*  @param    objet          $bd         l'objet représentant la connexion au serveur MySQL
*  @param    array|string   $content    la chaine à protéger ou un tableau contenant des chaines à protéger 
*  @return   array|string               la chaîne protégée ou le tableau
*/  
function gk_bd_proteger_entree(mysqli $bd, array|string $content): array|string {
    if (is_array($content)) {
        foreach ($content as &$value) {
            if (is_array($value) || is_string($value)){
                $value = gk_bd_proteger_entree($bd,$value);
            }
        }
        unset ($value); // à ne pas oublier (de façon générale)
        return $content;
    }
    if (is_string($content)){
        if (function_exists('mysqli_real_escape_string')) {
            return mysqli_real_escape_string($bd, $content);
        }
        if (function_exists('mysqli_escape_string')) {
            return mysqli_escape_string($bd, $content);
        }
        return addslashes($content);
        
    }
    return $content;
}

//___________________________________________________________________
/**
* Contrôle des clés présentes dans les tableaux $_GET ou $_POST - piratage ?
* 
*
* Soit $x l'ensemble des clés contenues dans $_GET ou $_POST 
* L'ensemble des clés obligatoires doit être inclus dans $x.
* De même $x doit être inclus dans l'ensemble des clés autorisées, formé par l'union de l'ensemble 
* des clés facultatives et de l'ensemble des clés obligatoires.
* Si ces 2 conditions sont vraies, la fonction renvoie true, sinon, elle renvoie false.
* Dit autrement, la fonction renvoie false si une clé obligatoire est absente ou 
* si une clé non autorisée est présente; elle renvoie true si "tout va bien"
* 
* @param string    $tab_global 'post' ou 'get'
* @param array     $cles_obligatoires tableau contenant les clés qui doivent obligatoirement être présentes
* @param array     $cles_facultatives tableau contenant les clés facultatives
* @return bool     true si les paramètres sont corrects, false sinon
*/
function gk_parametres_controle(string $tab_global, array $cles_obligatoires, array $cles_facultatives = array()): bool{
    $x = strtolower($tab_global) == 'post' ? $_POST : $_GET;

    $x = array_keys($x);
    // $cles_obligatoires doit être inclus dans $x
    if (count(array_diff($cles_obligatoires, $x)) > 0){
        return false;
    }
    // $x doit être inclus dans $cles_obligatoires Union $cles_facultatives
    if (count(array_diff($x, array_merge($cles_obligatoires,$cles_facultatives))) > 0){
        return false;
    }
    
    return true;
}

//___________________________________________________________________
/**
 * Affiche une ligne d'un tableau permettant la saisie d'un champ input de type 'text', 'password', 'date' ou 'email'
 *
 * La ligne est constituée de 2 cellules :
 * - la 1ère cellule contient un label permettant un "contrôle étiqueté" de l'input 
 * - la 2ème cellule contient l'input
 *
 * @param string    $libelle        Le label associé à l'input
 * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
 * @param string    $prefix_id      Le préfixe utilisé pour l'id de l'input, ce qui donne un id égal à {$prefix_id}{$attributs['name']}
 */
function gk_aff_ligne_input(string $libelle, array $attributs = array(), string $prefix_id = 'text'): void{
    echo    '<tr>', 
                '<td><label for="', $prefix_id, $attributs['name'], '">', $libelle, '</label></td>',
                '<td><input id="', $prefix_id, $attributs['name'], '"'; 
                
    foreach ($attributs as $cle => $value){
        echo ' ', $cle, ($value !== null ? "='{$value}'" : '');
    }
    echo '></td></tr>';
}
    

?>

