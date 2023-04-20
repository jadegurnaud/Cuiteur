<?php

ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';


// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (!gk_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

//Affiche le début du code HTML de la page
//Affichage du bandeau supérieur et latérale
gk_aff_debut('Cuiteur | Recherche', '../styles/cuiteur.css');
gk_aff_entete(true, 'Rechercher des utilisateurs');
gk_aff_infos(true);


$bd = gk_bd_connect();
// traitement si soumission des nouveaux abonnements/desabonnements
@$abonner=$_GET['abonner'];
@$desabonner=$_GET['desabonner'];
@$valider=$_GET['btnSAbonne'];
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
    header('Location: cuiteur.php');
    exit();

}

// réaffichage de la recherche après soumission
if (isset($_GET['recherche'])){
    $value = gk_html_proteger_sortie($_GET['recherche']);
}
else{
    $value = '';
}

//Affichage de la barre de recherche
echo '<form action="recherche.php" method="GET">',
        '<input type="text" name="recherche" value="',$value,'"/>',
        '<input type="submit" name="btnRechercher" value="Rechercher"/>',
    '</form>';


//Vérification de la saisie et recherche des utilisateurs dans la Base de Données
@$noTags = strip_tags($_GET['recherche']);
if(isset($_GET['recherche']) && !empty($_GET['recherche']) && ($noTags==$_GET['recherche'])){
    echo    '<h3 class="titreCompte">Résultats de la recherche</h3>';

    $sous_chaine=gk_bd_proteger_entree($bd, gk_html_proteger_sortie($_GET['recherche']));
    //Recherche de l'utilisateur dans la base de données
    $sql = "SELECT usID, usPseudo, usAvecPhoto, usNom 
            FROM users
            WHERE usPseudo LIKE '%$sous_chaine%' 
            OR usNom LIKE '%$sous_chaine%'";

    $res = gk_bd_send_request($bd, $sql);
    if (mysqli_num_rows($res) == 0){
        echo "<p>Aucun utilisateur ne correspond à votre recherche.</p>";
    }else{
        echo '<form>', '<ul>';
        gk_autres_utilisateur_infos($res, $_SESSION['usID']);    
        echo '</ul><p class="btnValider" ><input type="submit" name="btnSAbonne" value="Valider"></p>',
        '</form>';
    }
    // libération des ressources
    mysqli_free_result($res);
}

// libération des ressources
mysqli_close($bd);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();
ob_end_flush();




?>

