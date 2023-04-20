<?php

ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! gk_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

//Récupération de l'id de l'utilisateur dont on veut voir le profil
if($_GET!=NULL && isset($_GET['id_abonne'])){
	$id=$_GET['id_abonne'];
}else{
	$id=$_SESSION['usID'];
}


// Traitement si soumission du nouvel abonnement/desabonnement
@$abo=$_POST['BtnAbo'];
@$des=$_POST['BtnDes'];
if(isset($abo)){
	$bd = gk_bd_connect();
	$date_abonnement = date('Ymd'); 
	$sql = "INSERT INTO estabonne(eaIDUser, eaIDAbonne, eaDate) 
			VALUES ('{$_SESSION['usID']}', '{$_POST['id']}','$date_abonnement')";
	gk_bd_send_request($bd, $sql);
	mysqli_close($bd);
	header('Location: cuiteur.php');
	exit;

}else if(isset($des)){
	$bd = gk_bd_connect();
	$date_abonnement = date('Ymd'); 
	$sql = "DELETE FROM estabonne 
			WHERE eaIDUser={$_SESSION['usID']}
			AND eaIDAbonne='{$_POST['id']}'";
	gk_bd_send_request($bd, $sql);
	mysqli_close($bd);
	header('Location: cuiteur.php');
	exit;
}

//Affiche le début du code HTML de la page
gk_aff_debut('Cuiteur | Utilisateur', '../styles/cuiteur.css');

//Affichage du contenu de la page utilisateur
gkl_aff_utilisateur($id);

//Affichage du bas de page
gk_aff_pied();
gk_aff_fin();

ob_end_flush();

// ----------  Fonctions locales du script ----------- //
/**
 * Affichage du contenu de la page utilisateur
 *
 * @param   int   $id    l'id de l'utilisateur dont on veut voir le profil
 */
function gkl_aff_utilisateur(int $id): void {

	$bd = gk_bd_connect();
	
	//Sélectionne les champs de l'utilisateur
    $sql = "SELECT *
        	FROM users
        	WHERE usID='$id' ";
	$res = gk_bd_send_request($bd, $sql);
	$t = mysqli_fetch_assoc($res);

	//Permet de savoir si l'utilisateur est abonné à un autre
	$sql7 ="SELECT eaIDAbonne, eaIDUser
            FROM estabonne
            WHERE eaIDUser = '{$_SESSION['usID']}'
            AND eaIDAbonne = '$id'";
    $res7 = gk_bd_send_request($bd, $sql7);
	

	//Affichage du bandeau supérieur et latérale
    if($id==$_SESSION['usID']){
		gk_aff_entete(true, "Votre profil");
	}else{
		gk_aff_entete(true, "Le profil de ". gk_html_proteger_sortie($t['usPseudo']));
	}
	gk_aff_infos(true);

	//Affichage du résumé de l'utilisateur
	gk_utilisateur_infos($id);


	//Affichage des informations relatives à l'utilisateur
    echo '<table>',
			'<tr>', 
	            '<td><label>', 'Date de naissance : ', '</label></td>',
	            '<td><label>', !empty($t['usDateNaissance']) ? gk_amj_clair(gk_html_proteger_sortie($t['usDateNaissance'])) : 'Non renseigné(e)', '</label></td>',
			'</tr>',
			'<tr>', 
				'<td><label>', 'Date d\'inscription : ', '</label></td>',
				'<td><label>', !empty($t['usDateInscription']) ? gk_amj_clair(gk_html_proteger_sortie($t['usDateInscription'])) : 'Non renseigné(e)', '</label></td>',
			'</tr>',
			'<tr>', 
				'<td><label>', 'Ville de résidence : ', '</label></td>',
				'<td><label>', !empty($t['usVille']) ? gk_html_proteger_sortie($t['usVille']) : 'Non renseigné(e)', '</label></td>',
			'</tr>',
			'<tr>', 
				'<td><label>', 'Mini-bio : ', '</label></td>',
				'<td><label>', !empty($t['usBio']) ? gk_html_proteger_sortie($t['usBio']) : 'Non renseigné(e)', '</label></td>',
			'</tr>',
			'<tr>', 
				'<td><label>', 'Site web : ', '</label></td>',
				'<td><label>', !empty($t['usWeb']) ? gk_html_proteger_sortie($t['usWeb']) : 'Non renseigné(e)', '</label></td>',
			'</tr>';

	 if($_SESSION['usID']!=$id){		
    	echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<form action="utilisateur.php" method="POST">
								<input type="hidden" name="id" value="',$id,'"> 
								<input type="submit" name=',(mysqli_num_rows($res7) == 0 ? "BtnAbo" : 'BtnDes'),' value=',(mysqli_num_rows($res7) == 0 ? "S'abonner" : 'Se_désabonner'),'>',
							'</form>', 
                        '</td>',
                    '</tr>';
                
    }
    echo'</table>';


// libération des ressources
mysqli_free_result($res);
mysqli_free_result($res7);
mysqli_close($bd);
}


?>
