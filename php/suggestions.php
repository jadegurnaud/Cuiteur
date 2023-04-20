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


//Ouverture de la base de données
$bd = gk_bd_connect();

// traitement si soumission des nouveaux abonnements
@$abonner=$_GET['abonner'];
@$valider=$_GET['btnValiderSuggestions'];
if(isset($valider)){
    $date_abonnement = date('Ymd');
    if(isset($abonner)){
        foreach($abonner as $a){
            $sql = "INSERT INTO estabonne(eaIDUser, eaIDAbonne, eaDate) 
                    VALUES ('{$_SESSION['usID']}', '$a','$date_abonnement')";
            gk_bd_send_request($bd, $sql);
        }
    }
    mysqli_close($bd);
    header('Location: cuiteur.php');
    exit;
}


$id=$_SESSION['usID'];

//Récupère les utilisateurs suggérés : les utilisateurs auxquels sont abonnés les utilisateurs auxquels est abonné l'utilisateur courant
$sql = "SELECT DISTINCT usPseudo, usAvecPhoto, usNom, usID
        FROM users INNER JOIN estabonne ON usID=eaIDAbonne
        WHERE eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser='$id')
        AND usID!='$id'
        AND usID NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser='$id')
        GROUP BY usPseudo ORDER BY usPseudo ";

$res = gk_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);

//Affiche le début du code HTML de la page
//Affichage du bandeau supérieur et latérale
gk_aff_debut('Cuiteur | Suggesions', '../styles/cuiteur.css');
gk_aff_entete(true, "Suggestions");
gk_aff_infos();
   

//Affichage des utilisateurs suggérés
echo '<form action="suggestions.php" method="GET">';
echo '<ul id="bcMessages">';
if (mysqli_num_rows($res) == 0){
    echo '<li>Aucune suggestions</li>';
}
else{
    mysqli_data_seek($res , 0); //pour relire la ligne 0
    gk_autres_utilisateur_infos($res, $id);    
}
echo '</ul>';

echo '<input type="submit" name="btnValiderSuggestions" value="Valider">', 
    '</form>';


// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();
ob_end_flush();
?>

