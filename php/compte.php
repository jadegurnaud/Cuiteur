 <?php

ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';


/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (!gk_est_authentifie()){
    header('Location: ../index.php');
    exit;
}
// traitement si soumission des différents formulaires
$er_perso = isset($_POST['btnValiderInfo']) ? gkl_traitement_info_perso() : array();
$er_uti = isset($_POST['btnValiderUtil']) ? gkl_traitement_info_utilisateur() : array();
$er_para = isset($_POST['btnValiderPara']) ? gkl_traitement_parametres() : array();


/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/
gk_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');
gk_aff_entete(true, gk_html_proteger_sortie("Paramètres de mon compte"));
gk_aff_infos(true);

echo '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>';
gkl_aff_info_perso($er_perso);
gkl_aff_info_utilisateur($er_uti);
gkl_aff_parametres($er_para);

gk_aff_pied();
gk_aff_fin();
    
ob_end_flush();

/**
 *  Traitement des informations personnelles 
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function gkl_traitement_info_perso(): array {
    $err = array();

    // vérification des noms et prenoms
    if (empty($_POST['nom'])) {
        $err[] = 'Le nom et le prénom doivent être renseignés.'; 
    }
    else {
        if (mb_strlen($_POST['nom'], 'UTF-8') > LMAX_NOMPRENOM){
            $err[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
        }
        $noTags = strip_tags($_POST['nom']);
        if ($noTags != $_POST['nom']){
            $err[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
        }
        else {
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST['nom'])){
                $err[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
            }
        }
    }

    // vérification de la date de naissance
    if (empty($_POST['naissance'])){
        $err[] = 'La date de naissance doit être renseignée.'; 
    }
    else{
        if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
            $err[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
        }
        else{
            list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
            if (!checkdate($mois, $jour, $annee)) {
                $err[] = 'La date de naissance n\'est pas valide.'; 
            }
            else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MIN) > time()) {
                $err[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
            }
            else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MAX + 1) < time()) {
                $err[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
            }
        }
    }

    // vérification de la ville
    $noTags = strip_tags($_POST['ville']);
    if ($noTags != $_POST['ville']){
        $err[] = 'La ville ne peut pas contenir de code HTML.';
    }
     // vérification de la mini-bio
    $noTags = strip_tags($_POST['bio']);
    if ($noTags != $_POST['bio']){
        $err[] = 'La mini-bio ne peut pas contenir de code HTML.';
    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs  
    if (count($err) > 0) {  
        return $err;    
    }


    //Récupération des données de l'utilisateur afin de comparer avec les données saisies
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);


    //Modification du nom
    if($_POST['nom']!=$t['usNom']){
        $nom = gk_bd_proteger_entree($bd,gk_html_proteger_sortie($_POST['nom']));
        $sql = "UPDATE users
            SET usNom = '$nom'
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }
    //Modification de la date de naissance
    $aaaammjj = $annee*10000  + $mois*100 + $jour;
    if($_POST['naissance']!=$t['usDateNaissance']){
        $sql = "UPDATE users
            SET usDateNaissance = $aaaammjj
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }
    //Modification de la ville
    if($_POST['ville']!=$t['usVille']){
        $ville = gk_bd_proteger_entree( $bd,gk_html_proteger_sortie($_POST['ville']));
        $sql = "UPDATE users
            SET usVille = '$ville'
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }
    //Modification de la bio
    if($_POST['bio']!=$t['usBio']){
        $bio = gk_bd_proteger_entree( $bd,gk_html_proteger_sortie($_POST['bio']));
        $sql = "UPDATE users
            SET usBio = \"$bio\"
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }    
    mysqli_close($bd);
    return $err; 
}




// ----------  Fonctions locales du script ----------- //
/**
 * Affichage des informations du compte Cuiteur
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function gkl_aff_info_utilisateur(array $err): void {    
    
    //Récupération des données de l'utilisateur dans la base de données
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
   
    $email=gk_html_proteger_sortie($t['usMail']);   
    $siteWeb=gk_html_proteger_sortie($t['usWeb']);


    echo '<h3 class="titreCompte">Informations sur votre compte Cuiteur</h3>',
        '<form class="compte" action="compte.php" method="post">';
        
    //Affichage d'un message de réussite ou d'erreurs
    if (isset($_POST['btnValiderUtil'])){
        if (count($err) > 0) {
            echo '<p class="error">Les erreurs suivantes ont été détectées :';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }else{
            echo '<p class="succes">La mise à jour des informations sur votre compte a bien été effectuée.</p>';
        }
    }

    //Affichage du formulaire
    echo '<table>';
    gk_aff_ligne_input( 'Adresse mail ', array('type' => 'email', 'name' => 'email', 'value' => $email, 
                    'placeholder' => '', 'required' => null));
    gk_aff_ligne_input('Site web', array('type' => 'text', 'name' => 'siteWeb', 'value' => $siteWeb, 
                    'placeholder' => ''));
    echo 
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnValiderUtil" value="Valider">', 
                    '</td>',
                '</tr>',
            '</table>',
        '</form>';
    mysqli_free_result($res);
    mysqli_close($bd);
}


/**
 *  Traitement des informations du compte Cuiteur
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function gkl_traitement_info_utilisateur(): array {
    $err = array();

    // vérification du format de l'adresse email
    if (empty($_POST['email'])){
        $err[] = 'L\'adresse mail ne doit pas être vide.'; 
    }
    else {
        if (mb_strlen($_POST['email'], 'UTF-8') > LMAX_EMAIL){
            $err[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        if(! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $err[] = 'L\'adresse mail n\'est pas valide.';
        }
    }

    // vérification du format du site web
    if(!filter_var($_POST['siteWeb'],FILTER_VALIDATE_URL) && !empty($_POST['siteWeb'])){
        $err[] = 'Le site web n\'est pas valide.';
    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs  
    if (count($err) > 0) {  
        return $err;    
    }



    //Récupération des données de l'utilisateur afin de comparer avec les données saisies
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);


    //Modification du de l'email
    if($_POST['email']!=$t['usMail']){
        $email=gk_bd_proteger_entree( $bd,gk_html_proteger_sortie($_POST['email']));
        $sql = "UPDATE users
            SET usMail = '$email'
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }
    //Modification du site web
    if($_POST['siteWeb']!=$t['usWeb']){
        $site = gk_bd_proteger_entree( $bd,gk_html_proteger_sortie($_POST['siteWeb']));
        $sql = "UPDATE users
            SET usWeb = '$site'
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }
    mysqli_free_result($res);
    mysqli_close($bd);
    return $err; 
}

// ----------  Fonctions locales du script ----------- //
/**
 * Affichage des informations personnelles
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function gkl_aff_info_perso(array $err): void {    
    //Récupération des données de l'utilisateur dans la base de données
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
   
    $nom=gk_html_proteger_sortie($t['usNom']);
    $naissance=gk_amj_standard(gk_html_proteger_sortie($t['usDateNaissance']));
    $ville=gk_html_proteger_sortie($t['usVille']);
    $bio=gk_html_proteger_sortie($t['usBio']);


    echo '<h3 class="titreCompte">Informations personnelles</h3>',
        '<form class="compte" action="compte.php" method="post" >';

    //Affichage d'un message de réussite ou d'erreurs
    if (isset($_POST['btnValiderInfo'])){
        if (count($err) > 0) {
            echo '<p class="error">Les erreurs suivantes ont été détectées :';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }else{
            echo '<p class="succes">La mise à jour des informations sur votre compte a bien été effectuée.</p>';
        }
    }

    //Affichage du formulaire     
    echo '<table>';
    gk_aff_ligne_input( 'Nom ', array('type' => 'text', 'name' => 'nom', 'value' => $nom, 
                        'placeholder' => '', 'required' => null));
    gk_aff_ligne_input('Date de naissance ', array('type' => 'date', 'name' => 'naissance', 'value' => $naissance, 
                        'required' => null));
    gk_aff_ligne_input('Ville ', array('type' => 'text', 'name' => 'ville', 'value' => $ville, 
                        'placeholder' => ''));
    //Affichage de la minibio
    echo    '<tr>', 
                '<td><label for="textbio">Mini-bio</label></td>',
                '<td><textarea id="textbio" name="bio" rows="8" cols="45">',$bio,'</textarea></td>',
            '</tr>';

    echo   '<tr>',
                    '<td colspan="2">',
                    '<input type="submit" name="btnValiderInfo" value="Valider">', 
                '</td>',
            '</tr>',
        '</table>',            
    '</form>';
    mysqli_close($bd);
}

// ----------  Fonctions locales du script ----------- //
/**
 * Affichage des paramètres du compte Cuiteur
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function gkl_aff_parametres(array $err): void {    
    
    //Récupération des données de l'utilisateur dans la base de données
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
   
    $avecPhoto=gk_html_proteger_sortie($t['usAvecPhoto']);   
    $siteWeb=gk_html_proteger_sortie($t['usWeb']);


    echo '<h3 class="titreCompte">Paramètres de votre compte Cuiteur</h3>',
        '<form class="compte" action="compte.php" method="post">';

    //Affichage d'un message de réussite ou d'erreurs
    if (isset($_POST['btnValiderPara'])){
        if (count($err) > 0) {
            echo '<p class="error">Les erreurs suivantes ont été détectées :';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }else{
            echo '<p class="succes">La mise à jour des informations sur votre compte a bien été effectuée.</p>';
        }
    }

    //Affichage du formulaire
    echo    '<table>';
    gk_aff_ligne_input( 'Changer le mot de passe : ', array('type' => 'password', 'name' => 'passe1', 'value' => '', 
                    'placeholder' => ''));
    gk_aff_ligne_input('Répétez le mot de passe : ', array('type' => 'password', 'name' => 'passe2', 'value' => '', 
                    'placeholder' => ''));
    //Affichage de l'image
    echo    '<tr>', 
            '<td>','Votre photo actuelle', '</td>',
            '<td><img src="../', (gk_html_proteger_sortie($t['usAvecPhoto']) == 1 ? "upload/".gk_html_proteger_sortie($t['usID']).".jpg" : 'images/anonyme.jpg'), 
            ' " alt="photo"><p>Taille 20ko maximum</p><p>Image JPG carré (mini 50x50px)</p><input type = "file" name = "UploadFile">';
    echo '</td></tr>';
    //Affichage des boutons radio
    echo '<tr>', 
            '<td>','Utilisez votre photo','</td>',
            '<td><input type="radio" name="radNiveau" value="1"', ($avecPhoto==0? "":"checked"),'> non <input type="radio" name="radNiveau" value="0"', ($avecPhoto==0? "checked":""),'> oui','</td>',
        '</tr>';
    echo 
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnValiderPara" value="Valider">', 
                    '</td>',
                '</tr>',
            '</table>',
        '</form>';
     
    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
}


/**
 *  Traitement des paramètres du compte Cuiteur 
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function gkl_traitement_parametres(): array {
    $err = array();

    // vérification des mots de passe
    if ($_POST['passe1'] !== $_POST['passe2']) {
        $err[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($_POST['passe1'], 'UTF-8');
    if (($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD) && !empty($_POST['passe1'])){
        $err[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs  
    if (count($err) > 0) {  
        return $err;    
    }


    //Récupération des données de l'utilisateur afin de comparer avec les données saisies
    $bd = gk_bd_connect();
    $sql = "SELECT *
        FROM users
        WHERE usID={$_SESSION['usID']}";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);

    //Modification du mot de passe
    if(!empty($_POST['passe1'])){
        $passe1 = password_hash(gk_html_proteger_sortie($_POST['passe1']), PASSWORD_DEFAULT);
        $passe1 = gk_bd_proteger_entree($bd, $passe1);
        if($_POST['passe1']!=$t['usPasse']){
            $sql = "UPDATE users
                SET usPasse = '$passe1'
                WHERE usID = {$_SESSION['usID']}";
            gk_bd_send_request($bd, $sql);
        }
    }
    
    //Modification de l'affichage de la photo
    if($_POST['radNiveau']!=$t['usAvecPhoto']){
        $rad_photo=gk_bd_proteger_entree( $bd,gk_html_proteger_sortie($_POST['radNiveau']));
        $sql = "UPDATE users
            SET usAvecPhoto = '$rad_photo'
            WHERE usID = {$_SESSION['usID']}";
        gk_bd_send_request($bd, $sql);
    }

    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
    return $err; 
}

?>
