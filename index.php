<?php

ob_start(); //démarre la bufferisation
session_start();

require_once 'php/bibli_generale.php';
require_once 'php/bibli_cuiteur.php';


// traitement si soumission du formulaire d'inscription
$er = isset($_POST['btnConnexion']) ? gkl_traitement_connexion() : array(); 
gk_aff_debut('Cuiteur | Blablas', 'styles/cuiteur.css');
gk_aff_entete(false, gk_html_proteger_sortie("Connectez vous"));
gk_aff_infos(false);
gk_aff_connexion($er);


gk_aff_pied();
gk_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Affichage du contenu de la page (Connexion)
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function gk_aff_connexion(array $err): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe 
    if (isset($_POST['btnConnexion'])){
        $values = gk_html_proteger_sortie($_POST);
    }
    else{
        $values['pseudo'] = '';
    }

    
        
    if (count($err) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }
    echo    
            '<p>Pour vous connecter, il faut vous authentifier: </p>',
            '<form method="post" action="index.php">',
                '<table>';

    gk_aff_ligne_input( 'Pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 
                        'placeholder' => ' 4 caractères alphanumériques', 'required' => null));
    gk_aff_ligne_input('Mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '', 'required' => null));

    echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnConnexion" value="Connexion">', 
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';

    echo 
        '<p>Pas encore de compte ? <strong><a href="php/inscription.php">Inscrivez vous</a></strong> sans tarder !<br>Vous hésitez à vous inscrire ? Laissez-vous séduire par une <strong><a href="html/presentation.html">présentation</a></strong> des possibilitées de Cuiteur.</p>';
}



function gkl_traitement_connexion(): array {
    $err = array();
    //verification
    $bd = gk_bd_connect();
    $pseudo= $_POST['pseudo'];
    $sql = "SELECT usPseudo, usPasse, usID
            FROM users
            WHERE usPseudo='$pseudo'";

    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
    if($t==NULL){
        $err[]='Votre pseudo pas correct';
    }else{
    
        //Le mot de passe de l'utilisateur
        $password = $_POST['passe1'];
         
        //Verification du mot de passe
        $passordVerify = password_verify($password,$t['usPasse']);
        if (!$passordVerify) {
            $err[]='Votre mot de passe pas correct';
        }
    
    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($err) > 0) {  
        return $err;    
    }


    // mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
    
    $_SESSION['usID'] = $t['usID'];
    echo $_SESSION['usID'];
    // libération des ressources
    mysqli_free_result($res);					
    mysqli_close($bd);
    
    // redirection vers la page cuiteur.php
    header("Location: php/cuiteur.php"); 
    exit();
}

?>

