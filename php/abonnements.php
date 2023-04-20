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

// traitement si soumission des nouveaux abonnements/desabonnements
@$abonner=$_GET['abonner'];
@$desabonner=$_GET['desabonner'];
@$valider=$_GET['btnValiderAbonnement'];
if(isset($valider)){
    $date_abonnement = date('Ymd');
    if(isset($abonner)){
        foreach($abonner as $a){
            $sql = "INSERT INTO estabonne(eaIDUser, eaIDAbonne, eaDate) 
                    VALUES ('{$_SESSION['usID']}', '$a','$date_abonnement')";
            gk_bd_send_request($bd, $sql);
        }
    }
    if(isset($desabonner)){
        foreach($desabonner as $d){
            $sql = "DELETE FROM estabonne 
                    WHERE eaIDUser={$_SESSION['usID']}
                    AND eaIDAbonne=$d";
            gk_bd_send_request($bd, $sql);
		}
    }
    mysqli_close($bd);
		header('Location: cuiteur.php');
    exit;
}

//Récupération de l'id de l'utilisateur dont on veut les abonnements
if($_GET!=NULL && isset($_GET['id_abonne'])){
        $id=$_GET['id_abonne'];
}else{
        $id=$_SESSION['usID'];
}


//Affichage du bandeau supérieur et latérale
$sql2 = "SELECT usPseudo
        FROM users
        WHERE usID='$id'";
$res2 = gk_bd_send_request($bd, $sql2);
$t2 = mysqli_fetch_assoc($res2);


gk_aff_debut('Cuiteur | Abonnements', '../styles/cuiteur.css');
if($id==$_SESSION['usID']){
    gk_aff_entete(true, "Vos abonnements");
}else{
    gk_aff_entete(true, "Les abonnements de ".gk_html_proteger_sortie($t2['usPseudo']));
}
gk_aff_infos();

//Affichage du résumé de l'utilisateur 
echo '<form action="abonnements.php" method="GET">';
gk_utilisateur_infos($id);


//Affichage de la liste d'utilisateurs auxquels l'utilisateur est abonné
$sql = "SELECT usID, usPseudo, usNom, usAvecPhoto
        FROM (users INNER JOIN estabonne ON usID=eaIDAbonne)
        WHERE estabonne.eaIDuser='$id'
        ORDER BY usPseudo";
$res = gk_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);

echo '<ul id="bcMessages">';
if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil d\'abonnements est vide</li>';
}
else{
    mysqli_data_seek($res , 0);
    gk_autres_utilisateur_infos($res, $id);
}
echo '</ul>';
echo '<input type="submit" name="btnValiderAbonnement" value="Valider">', 
        '</form>';



// libération des ressources
mysqli_free_result($res);
mysqli_free_result($res2);
mysqli_close($bd);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();

ob_end_flush();
?>
