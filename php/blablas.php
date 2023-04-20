<?php

ob_start(); 
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (!gk_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

//Récupération de l'id de l'utilisateur dont on veut les blablas
if($_GET!=NULL && isset($_GET['id_abonne'])){
    $id=$_GET['id_abonne'];
}else{
    $id=$_SESSION['usID'];
}

//Affichage du bandeau supérieur et latérale
$bd = gk_bd_connect();
$sql2 = "SELECT usPseudo
        FROM users
        WHERE usID='$id' ";
$res2 = gk_bd_send_request($bd, $sql2);
$t2 = mysqli_fetch_assoc($res2);    


gk_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');
if($id==$_SESSION['usID']){
    gk_aff_entete(true, "Vos blablas");
}else{
    gk_aff_entete(true, "Les blablas de ".gk_html_proteger_sortie($t2['usPseudo']));
}
gk_aff_infos();


//Affichage du résumé de l'utilisateur 
echo '<form action="blablas.php" method="GET">';
gk_utilisateur_infos($id);


//Affichage des blablas publiés ou recuités par l'utilisateur
$sql = "SELECT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
        blTexte, blDate, blHeure,blID, origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM ((users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
        WHERE   auteur.usID = '$id'";
$res = gk_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);

$nb_blablas=4;
echo '<ul id="bcMessages">';
if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
}
else{
    mysqli_data_seek($res , 0);
    gk_aff_blablas($res,"blablas.php",$nb_blablas);

}
echo '</ul></form>';


// libération des ressources
mysqli_free_result($res);
mysqli_free_result($res2);
mysqli_close($bd);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();

ob_end_flush();

?>

