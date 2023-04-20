<?php

ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';


//Si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (!gk_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

//Affiche le début du code HTML de la page
gk_aff_debut('Cuiteur | Tendances', '../styles/cuiteur.css');


//S'il n'y a pas la clé tag dans le tableau GET on affiche la page principale des tendances
if($_GET==null || !isset($_GET['tag'])){
	//Affichage du bandeau supérieur et latérale
	gk_aff_entete(true, "_");
	gk_aff_infos(true);

	//Affichage des tendances
	$date_jour = gk_date_clair_string(date("d/m/Y"));
	$date_semaine = gk_date_clair_semaine(date('N/m/Y'));
	$date_mois = gk_date_clair_mois(date("d/m/Y"));
	$date_annee = gk_date_clair_annee(date("d/m/Y"));
	$nb=10;

	echo  '<h3 class="tendance">Top 10 du jour</h3>';
	gk_aff_tendances($date_jour, $nb, true);

    echo '<h3 class="tendance">Top 10 de la semaine</h3>';
    gk_aff_tendances($date_semaine, $nb, true);

    echo '<h3 class="tendance">Top 10 du mois</h3>';
	gk_aff_tendances($date_mois, $nb, true);

    echo'<h3 class="tendance">Top 10 de l\'année</h3>';
	gk_aff_tendances($date_annee, $nb, true);

//Sinon on affiche la liste de blablas contenant le tag
}else{
	$bd = gk_bd_connect();
	$tag = gk_bd_proteger_entree($bd, $_GET['tag']); 
		
	//Récupération des blablas contenant le tag
	$sql = "SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
	blTexte, blDate, blHeure, blID,
	origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
			FROM (((users AS auteur
			INNER JOIN blablas ON blIDAuteur = usID)
			LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
			LEFT OUTER JOIN estabonne ON auteur.usID = eaIDAbonne)
			LEFT OUTER JOIN mentions ON blID = meIDBlabla
			LEFT OUTER JOIN tags ON blID = taIDblabla
			WHERE   tags.taID = \"$tag\"
			ORDER BY blDate DESC, blHeure DESC";
	$res = gk_bd_send_request($bd, $sql);
	
	//Affichage du bandeau supérieur et latérale
	gk_aff_entete(true, "info");
	gk_aff_infos(true);

	//Affichage des blablas
	echo '<ul>';
	gk_aff_blablas($res,"tendances.php",4);
	echo '</ul>';

	// libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
}
//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();
ob_end_flush();

?>
