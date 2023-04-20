<?php

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (!gk_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

//Affichage du bandeau supérieur et latérale
gk_aff_debut('Cuiteur | Cuiteur', '../styles/cuiteur.css');
gk_aff_entete(true);
gk_aff_infos();

/*Récupération des blablas postés par l'utilisateur courant, 
postés par des utilisateurs auxquels l'utilisateur courant est abonné, 
et ceux qui mentionnent l'utilisateur courant.*/
$bd = gk_bd_connect();
$id = (int)$_SESSION['usID']; 
$sql = "SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
        blTexte, blDate, blHeure, blID,
        origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM (((users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
        LEFT OUTER JOIN estabonne ON auteur.usID = eaIDAbonne)
        LEFT OUTER JOIN mentions ON blID = meIDBlabla
        WHERE   auteur.usID = $id
        OR      eaIDUser = $id
        OR      meIDUser = $id
        ORDER BY blDate DESC, blHeure DESC";
$res = gk_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);

//Affichage des blablas récupéré dans la base de donnée
$nb_blablas=4;
echo '<ul id="bcMessages">';
if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
}
else{
    mysqli_data_seek($res , 0); 
    gk_aff_blablas($res,"cuiteur.php",4);
}
echo '</ul>';


//Traitement du formulaire permettant de poster un nouveau blabla
if (isset($_POST['btnPublier'])){
    $erreurs = array();
    if (empty($_POST['txtMessage'])) {
        $erreurs[] = 'Le message ne doit pas être vide.'; 
    }
    $noTags = strip_tags($_POST['txtMessage']);
    if ($noTags != $_POST['txtMessage']){
        $erreurs[] = 'Le message ne peut pas contenir de code HTML.';
    }
    if(strlen($noTags)>255){
        $erreurs[] = 'Le message est trop long.';
    }
    if(count($erreurs)==0){
        gk_ajouter_blabla();
    }
}


//Libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>

