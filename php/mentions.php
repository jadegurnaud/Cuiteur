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



$bd = gk_bd_connect();
//Récupération de l'id de l'utilisateur dont on veut les mentions
if($_GET!=NULL && isset($_GET['id_abonne'])){
        $id=$_GET['id_abonne'];
}else{
        $id=$_SESSION['usID'];
}

//Récupération des blablas où il est mentionné
$sql = "SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
        blTexte, blDate, blHeure, blID,
        origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM ((users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
        LEFT OUTER JOIN mentions ON blID = meIDBlabla
        WHERE   meIDUser = $id
        ORDER BY blDate DESC, blHeure DESC";
$res = gk_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);


//Affichage du bandeau supérieur et latérale
$sql2 = "SELECT *
        FROM users
        WHERE usID='$id' ";
$res2 = gk_bd_send_request($bd, $sql2);
$t2 = mysqli_fetch_assoc($res2);

//Affiche le début du code HTML de la page
//Affichage du bandeau supérieur et latérale
gk_aff_debut('Cuiteur | Mentions', '../styles/cuiteur.css');
if($id==$_SESSION['usID']){
    gk_aff_entete(true, 'Vos mentions');
}else{
    gk_aff_entete(true, "Les mentions de ".gk_html_proteger_sortie($t2['usPseudo']));
}
gk_aff_infos();


//Affichage des blablas
echo '<form action="mentions.php" method="GET">';
    gk_utilisateur_infos($id);

    echo '<ul id="bcMessages">';
        if (mysqli_num_rows($res) == 0){
            echo '<li>Aucune mention</li>';
        }
        else{
            mysqli_data_seek($res , 0);
            gk_aff_blablas($res,"mentions.php",4);
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

