<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application Cuiteur                 *
 *********************************************************/

 // Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

//définition de l'encodage des caractères pour les expressions rationnelles multi-octets
mb_regex_encoding ('UTF-8');

define('IS_DEV', true);//true en phase de développement, false en phase de production

 // Paramètres pour accéder à la base de données
define('BD_SERVER', 'localhost');
define('BD_NAME', 'kruzic_cuiteur');
define('BD_USER', 'kruzic_u');
define('BD_PASS', 'kruzic_p');



// paramètres de l'application
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 30); //longueur du champ dans la base de données
define('LMAX_EMAIL', 80); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 60); //longueur du champ dans la base de données


define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('AGE_MIN', 18);
define('AGE_MAX', 120);


//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param bool       $is_connected Si true, l'utilisateur est connecté et il à accès à l'ensemble des fonctionnalités
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete avec le formulaire)
 */
function gk_aff_entete(bool $is_connected, ?string $titre = null):void{
    echo '<div id="bcContenu">';
        if($is_connected==true){
            echo '<header>',
                    '<a href="deconnexion.php" title="Se déconnecter de cuiteur"></a>',   
                    '<a href="cuiteur.php" title="Ma page d\'accueil"></a>',
                    '<a href="recherche.php" title="Rechercher des personnes à suivre"></a>',
                    '<a href="compte.php" title="Modifier mes informations personnelles"></a>';
        }else{
            echo '<header id="offline">';
        }
    
    $msg="";
    if($_GET!=NULL && isset($_GET['lien']) && isset($_GET['info']) && $_GET['lien']=='repondre' && strlen($msg)<255){
        $msg='@'.$_GET['info'];
    }

    if ($titre === null){
        echo    '<form action="cuiteur.php" method="POST">',
                    '<textarea name="txtMessage">',$msg,'</textarea>',
                    '<input type="submit" name="btnPublier" value="" title="Publier mon message">',
                '</form>';
    }
    else{
        echo    '<h1>', $titre, '</h1>';
    }
    echo    '</header>';    
}

//_______________________________________________________________
/**
 * Génération et affichage du bloc d'informations utilisateur
 *
 * @param bool    $connecte  true si l'utilisateur courant s'est authentifié, false sinon
 */
function gk_aff_infos(bool $connecte = true):void{
    
    echo '<aside>';
    if ($connecte){
        $bd = gk_bd_connect();
        $id=$_SESSION['usID'];

        //Sélectionne les champs de l'utilisateur
        $sql2 ="SELECT *
                FROM users
                WHERE usID='$id' ";
        $res2 = gk_bd_send_request($bd, $sql2);
        $t2 = mysqli_fetch_assoc($res2);

        //Compte le nombre de blablas publiés par l’utilisateur 
        $sql3 ="SELECT COUNT(*) AS NB
                FROM blablas
                WHERE blIDAuteur=$id";
        $res3 = gk_bd_send_request($bd, $sql3);
        $t3 = mysqli_fetch_assoc($res3);

        //Compte le nombre d’abonnements de l’utilisateur
        $sql4 ="SELECT COUNT(*) AS NB
                FROM estabonne
                WHERE eaIDUser=$id";
        $res4 = gk_bd_send_request($bd, $sql4);
        $t4 = mysqli_fetch_assoc($res4);

        //Compte le nombre d’abonnés de l’utilisateur
        $sql5 ="SELECT COUNT(*) AS NB
                FROM estabonne
                WHERE eaIDAbonne=$id";
        $res5 = gk_bd_send_request($bd, $sql5);
        $t5 = mysqli_fetch_assoc($res5);

        $pseudo = $t2['usPseudo'];
        $photo = $t2['usAvecPhoto'];
        $nom = $t2['usNom'];
        $date_annee = gk_date_clair_annee(date("d/m/Y"));
        $nb=4;
        $nb2=2;

        //Sélectionne les utilisateurs suggérés : les utilisateurs auxquels sont abonnés les utilisateurs auxquels est abonné l'utilisateur courant
        $sql = " SELECT DISTINCT usPseudo, usAvecPhoto, usNom, usID, usAvecPhoto
                FROM users INNER JOIN estabonne ON usID=eaIDAbonne
                WHERE eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser='$id')
                AND usID!='$id'
                AND usID NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser='$id')
                GROUP BY usPseudo ORDER BY usPseudo ASC LIMIT 0,$nb2";
        $res = gk_bd_send_request($bd, $sql);
        $t = mysqli_fetch_assoc($res);
        
        
        echo 
            '<h3>Utilisateur</h3>',
                '<ul>',
                    '<li>',
                        '<img src="../', ($photo == 1 ? "upload/$id.jpg" : 'images/anonyme.jpg'), 
                        '" alt="photo de l\'utilisateur">',
                        "<a href=\"utilisateur.php?id_abonne=$id\" title='Voir mes infos'>",$pseudo,"</a>",' ', gk_html_proteger_sortie($nom),
                    '</li>',
                    '<li>',"<a href=\"blablas.php?id_abonne=$id\" title='Voir les blablas'>",$t3['NB']," blablas</a>",'</li>',
                    '<li>',"<a href=\"abonnements.php?id_abonne=$id\" title='Voir les abonnements'>",$t4['NB']," abonnements</a>",'</li>',
                    '<li>',"<a href=\"abonnes.php?id_abonne=$id\" title='Voir les abonnés'>",$t5['NB']," abonnés</a>",'</li>',                 
                '</ul>',
            '<h3>Tendances</h3>',
                gk_aff_tendances($date_annee, $nb, false),
                '<a href="tendances.php">Toutes les tendances</a>',
            '<h3>Suggestions</h3>', 

                '<ul>';
                    mysqli_data_seek($res , 0); 
                    while ($t = mysqli_fetch_assoc($res)){ 
                        $pseudo_sug = $t['usPseudo'];
                        $photo_sug = $t['usAvecPhoto'];
                        $nom_sug = $t['usNom']; 
                        $id_sug = $t['usID'];  
                        echo 
                            '<li>',
                                '<img src="../', ($photo_sug == 1 ? "upload/$id_sug.jpg" : 'images/anonyme.jpg'), 
                                ' " alt="photo de l\'utilisateur">',
                                "<a href=\"utilisateur.php?id_abonne=$id_sug\" title='Voir mes infos'>",$pseudo_sug,"</a>",
                                ' ', gk_html_proteger_sortie($nom_sug),
                            '</li>';    
            }
                    echo '<li><a href="suggestions.php">Plus de suggestions</a></li>',
                '</ul>';

            // libération des ressources
            mysqli_free_result($res);
            mysqli_free_result($res2);
            mysqli_free_result($res3);
            mysqli_free_result($res4);
            mysqli_free_result($res5);
            mysqli_close($bd); 
    }
    echo '</aside>',
         '<main>';  
         
}

//_______________________________________________________________
/**
 * Génération et affichage du pied de page
 *
 */
function gk_aff_pied(): void{
    echo    '</main>',
            '<footer>',
                '<a href="../index.html">A propos</a>',
                '<a href="../index.html">Publicité</a>',
                '<a href="../index.html">Patati</a>',
                '<a href="../index.html">Aide</a>',
                '<a href="../index.html">Patata</a>',
                '<a href="../index.html">Stages</a>',
                '<a href="../index.html">Emplois</a>',
                '<a href="../index.html">Confidentialité</a>',
            '</footer>',
    '</div>';
}

//_______________________________________________________________
/**
 * Ajoute un blabla dans la Base de Données et traite les tags et les mentions
 *
 */
function gk_ajouter_blabla(): void{
    $id = (int)$_SESSION['usID'];
    $bd = gk_bd_connect();

    //Ajoute blabla à la table blablas
    $auteur=$id;
    $date=date('Ymd');
    $heure=date('G:i:s');
    $blabla=gk_bd_proteger_entree($bd, gk_html_proteger_sortie($_POST['txtMessage']));

    $sql = "INSERT INTO blablas(blIDAuteur, blDate, blHeure, blTexte, blIDAutOrig) 
            VALUES ('$auteur', '$date', '$heure', '$blabla', NULL)";
            
    gk_bd_send_request($bd, $sql);

    // Mémorisation de l'ID du blabla
    $id_new_blabla = mysqli_insert_id($bd);


    //Ajoute le blabla à la table tag si besoin
    preg_match_all('/(?P<hashtags>\#\w+)/', $_POST['txtMessage'],$sigle);  //'/(?<!\w)#\w+/'
    if(!empty($sigle['hashtags'])){
        foreach ($sigle['hashtags'] as $valeur) {
            $id_tag=substr($valeur,1);               
            $sql = "INSERT INTO tags(taID, taIDBlabla) 
            VALUES ('$id_tag', '$id_new_blabla')";
            gk_bd_send_request($bd, $sql);
        }
    }


    //Ajoute blabla à la table mention si besoin
    preg_match_all('/(?P<arobase>\@\w+)/', $_POST['txtMessage'],$sigle);
    if(!empty($sigle['arobase'])){
        foreach ($sigle['arobase'] as $valeur) {
            $pseudo=substr($valeur,1);   
            $sql = "SELECT usID FROM users WHERE usPseudo='$pseudo'";
            $res = gk_bd_send_request($bd, $sql);

            //Si l'utilisateur mentionné n'existe pas, on ne l'ajoute pas
            if (mysqli_num_rows($res) != 0) {
                $t = mysqli_fetch_assoc($res);
                $id_mentionne=gk_bd_proteger_entree($bd, gk_html_proteger_sortie($t['usID']));              
                $sql = "INSERT INTO mentions(meIDUser, meIDBlabla) 
                VALUES ('$id_mentionne', '$id_new_blabla')";
                gk_bd_send_request($bd, $sql);
            }   
        }
    }
    // libération des ressources
    mysqli_close($bd);
    header('Location: cuiteur.php');
    exit;


}

//_______________________________________________________________
/**
* Supprime un blabla de la Base de Données et les dépendances associées
*/
function gk_supprimer_blabla(): void{
    $bd = gk_bd_connect();
                
    $id_blabla=$_GET['info'];
    
    //vérifie si le blabla contient des tags et les supprime
    $sql = "DELETE FROM tags 
            WHERE taIDBlabla=$id_blabla";
    gk_bd_send_request($bd, $sql);

    //vérifie si le blabla contient des mentions et les supprime
    $sql = "DELETE FROM mentions 
            WHERE meIDBlabla=$id_blabla";
    gk_bd_send_request($bd, $sql);

    //Supprime le blabla
    $sql = "DELETE FROM blablas 
            WHERE blID=$id_blabla";
    gk_bd_send_request($bd, $sql);

    // libération des ressources
    mysqli_close($bd);

    header('Location: cuiteur.php');
    exit;
}


//_______________________________________________________________
/**
* Recuite un blabla
*/
function gk_recuiter_blabla():void{
    $id_blabla=$_GET['info'];
    $id = (int)$_SESSION['usID']; 

    //Récupère les données du blabla a recuiter
    $bd = gk_bd_connect();
    $sql = "SELECT *  
    FROM blablas
    WHERE   blID = $id_blabla";
    $res = gk_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);

    //Ajoute le nouveau blabla
    $auteur=$id;
    $date=date('Ymd');
    $heure=date('G:i:s');
    $blabla=gk_bd_proteger_entree($bd, $t['blTexte']);

    if(!isset($t['blIDAutOrig'])){
        $auteur_ori=gk_bd_proteger_entree($bd,gk_html_proteger_sortie($t['blIDAuteur']));
    }else{
        $auteur_ori=gk_bd_proteger_entree($bd,gk_html_proteger_sortie($t['blIDAutOrig']));
    }
    $sql = "INSERT INTO blablas(blIDAuteur, blDate, blHeure, blTexte, blIDAutOrig) 
            VALUES ('$auteur', '$date', '$heure', '$blabla', $auteur_ori)";
            
    gk_bd_send_request($bd, $sql);
    
    //Mémorisation de l'ID du blabla
    $id_new_blabla = mysqli_insert_id($bd);
    
    //Ajoute blabla à la table tag si besoin
    preg_match_all('/(?P<hashtags>\#\w+)/', $blabla,$sigle);  //'/(?<!\w)#\w+/'
    if(!empty($sigle['hashtags'])){
        foreach ($sigle['hashtags'] as $valeur) {
            $id_tag=substr($valeur,1);               
            $sql = "INSERT INTO tags(taID, taIDBlabla) 
            VALUES ('$id_tag', '$id_new_blabla')";
            gk_bd_send_request($bd, $sql);
        }
    }

    //Ajoute blabla à la table mention si besoin
    preg_match_all('/(?P<arobase>\@\w+)/', $blabla,$sigle);
    if(!empty($sigle['arobase'])){
        foreach ($sigle['arobase'] as $valeur) {
            $pseudo=substr($valeur,1);   
            $sql = "SELECT usID FROM users WHERE usPseudo='$pseudo'";
            $res = gk_bd_send_request($bd, $sql);

            //Si l'utilisateur mentionné n'existe pas, on ne l'ajoute pas
            if (mysqli_num_rows($res) != 0) {
                $t = mysqli_fetch_assoc($res);
                $id_mentionne=gk_bd_proteger_entree($bd,gk_html_proteger_sortie($t['usID']));              
                $sql = "INSERT INTO mentions(meIDUser, meIDBlabla) 
                VALUES ('$id_mentionne', '$id_new_blabla')";
                gk_bd_send_request($bd, $sql);
            }   
        }
    }

    header('Location: cuiteur.php');
    exit;

    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
}



//_______________________________________________________________
/**
* Affichages des résultats des SELECT des blablas.
*
* La fonction gére la boucle de lecture des résultats et les
* encapsule dans du code HTML envoyé au navigateur 
*
* @param mysqli_result  $r              Objet permettant l'accès aux résultats de la requête SELECT
* @param string         $page           Le nom de la page qui appelle la fonction
* @param int            $nb_blablas     Le nombre de blablas par défaut
*/
function gk_aff_blablas(mysqli_result $r, string $page, int $nb_blablas): void {
    $nb=0;
    $nb_max=$nb_blablas;
    if($_GET!=NULL){
        if(isset($_GET['nombre_blablas'])){
            $nb_max=$_GET['nombre_blablas'];
        }
        //Gestion modification des blablas
        if(isset($_GET['lien']) && isset($_GET['info'])){
            //clic sur supprimer son blabla
            if($_GET['lien']=='supprimer'){
                gk_supprimer_blabla();
                
            //Clic sur répondre au blabla
            }else if($_GET['lien']=='repondre'){
                $pseudo=$_GET['info'];

            //Clic sur recuiter le blabla
            }else if($_GET['lien']=='recuiter'){
                gk_recuiter_blabla();
            }
        }
    }
    //Affichage des blablas
    while (($t = mysqli_fetch_assoc($r)) && ($nb<$nb_max)) {
        //Detecte si c'est un blabla recuité
        if ($t['oriID'] === null){
            $id_orig = gk_html_proteger_sortie($t['autID']);
            $pseudo_orig = gk_html_proteger_sortie($t['autPseudo']);
            $photo = gk_html_proteger_sortie($t['autPhoto']);
            $nom_orig = gk_html_proteger_sortie($t['autNom']);
        }
        else{
            $id_orig = gk_html_proteger_sortie($t['oriID']);
            $pseudo_orig = gk_html_proteger_sortie($t['oriPseudo']);
            $photo = gk_html_proteger_sortie($t['oriPhoto']);
            $nom_orig = gk_html_proteger_sortie($t['oriNom']);
        }

        echo    '<li>', 
                    '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                    '" class="imgAuteur" alt="photo de l\'utilisateur">',
                    "<a href=\"utilisateur.php?id_abonne=$id_orig\" title='Voir mes infos'>",'<strong>',$pseudo_orig,'</strong>',"</a>",
                    ' ', $nom_orig;
                    if($t['oriID'] !== null){
                        echo ', recuité par ', "<a href=\"utilisateur.php?id_abonne=".gk_html_proteger_sortie($t['autID'])."\" title='Voir mes infos'>",'<strong>',gk_html_proteger_sortie($t['autPseudo']),'</strong>',"</a>";
                    }
                  
                  echo  '<br>',gk_html_proteger_sortie($t['blTexte']),
                    '<p class="finMessage">',
                    gk_amj_clair(gk_html_proteger_sortie($t['blDate'])), ' à ', gk_heure_clair(gk_html_proteger_sortie($t['blHeure']));

                $id_utilisateur = (int)$_SESSION['usID']; 
                if($t['autID'] != $id_utilisateur){
                    echo   '<a href="cuiteur.php?info=',gk_html_proteger_sortie($t['autPseudo']),'&lien=repondre">Répondre</a> <a href="cuiteur.php?info=',gk_html_proteger_sortie($t['blID']),'&lien=recuiter">Recuiter</a></p>';
                }else{
                    echo   '<a href="cuiteur.php?info=',gk_html_proteger_sortie($t['blID']),'&lien=supprimer">Supprimer</a></p>';
                }
                
        echo '</li>';
        $nb=$nb+1;
    }
    //Affichage de plus de blablas
    $nb_max=$nb_max+$nb_blablas;
    if($nb_max-$nb_blablas==$nb){
        if($page=="cuiteur.php"){
                echo"<li class='plusBlablas'>",
                "<a href=\"$page?nombre_blablas=$nb_max\"><strong>Plus de blablas</strong></a>",
                "<img src=\"../images/speaker.png\" width=\"75\" height=\"82\" alt=\"Image du speaker 'Plus de blablas'\">",
            "</li>";
        }else if($page=="blablas.php" || $page=="mentions.php"){
            echo"<li class='plusBlablas'>",
            "<a href=\"$page?id_abonne={$_GET['id_abonne']}&nombre_blablas=$nb_max\"><strong>Plus de blablas</strong></a>",
            "<img src=\"../images/speaker.png\" width=\"75\" height=\"82\" alt=\"Image du speaker 'Plus de blablas'\">",
            "</li>";
        }else if($page=="tendances.php"){
            echo"<li class='plusBlablas'>",
            "<a href=\"$page?tag={$_GET['tag']}&nombre_blablas=$nb_max\"><strong>Plus de blablas</strong></a>",
            "<img src=\"../images/speaker.png\" width=\"75\" height=\"82\" alt=\"Image du speaker 'Plus de blablas'\">",
            "</li>";
        }
    }      
}

//_______________________________________________________________
/**
* Affichages des résultats des SELECT des tendances.
*
* La fonction affiche les tendances dans aside ou dans le main en fonction du boolean
*
* @param string  $date       La date à partir de laquelle on souhaite les tendances
* @param int     $nb         Le nombre de tendances à afficher
* @param bool    $tendance   Si tendance égale true affichage pour le main sinon aside
*/
function gk_aff_tendances(string $date, int $nb, bool $tendance): void { 
    $bd = gk_bd_connect();
    $sql = "SELECT taID, COUNT(*) AS NB 
            FROM (tags INNER JOIN blablas ON taIDblabla=blID) 
            WHERE blHeure >= '00:00:00' 
            AND blDate>=  $date
            GROUP BY taID ORDER BY NB DESC LIMIT 0,$nb";
    $res = gk_bd_send_request($bd, $sql);
  
    if(mysqli_fetch_assoc($res)==null){
        echo '<p class="aucune_tendance">Aucune tendance...</p>';
    }else{
        echo '<ol>';
        mysqli_data_seek($res, 0);
        while($t = mysqli_fetch_assoc($res)){
        $tendances=$t['taID'];
        $nombre=$t['NB'];
            echo ($tendance == true ? "<li class='tendance_contenu'><a href=\"tendances.php?tag=$tendances\">$tendances ($nombre)</a></li>" : "<li>#<a href=\"tendances.php?tag=$tendances\">$tendances</a></li>");
        }
        echo '</ol>';
        
    }
    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
}




//_______________________________________________________________
/**
* Affichage du résumé de l'utilisateur.
*
* La fonction affiche le pseudo, le nom, son nombres de blablas, son nombre de mentions, son nombre d'abonnés et son nombre d'abonnements
*
* @param int     $id       L'ID de l'utilisateur
*/
function gk_utilisateur_infos(int $id): void {
    $bd = gk_bd_connect();

    //Sélectionne les champs de l'utilisateur
    $sql2 ="SELECT *
            FROM users
            WHERE usID='$id' ";
    $res2 = gk_bd_send_request($bd, $sql2);
    $t2 = mysqli_fetch_assoc($res2);

    //Compte le nombre de blablas publiés par l’utilisateur 
    $sql3 ="SELECT COUNT(*) AS NB
            FROM blablas
            WHERE blIDAuteur=$id";
    $res3 = gk_bd_send_request($bd, $sql3);
    $t3 = mysqli_fetch_assoc($res3);

    //Compte le nombre d’abonnements de l’utilisateur
    $sql4 ="SELECT COUNT(*) AS NB
            FROM estabonne
            WHERE eaIDUser=$id";
    $res4 = gk_bd_send_request($bd, $sql4);
    $t4 = mysqli_fetch_assoc($res4);

    //Compte le nombre d’abonnés de l’utilisateur
    $sql5 ="SELECT COUNT(*) AS NB
            FROM estabonne
            WHERE eaIDAbonne=$id";
    $res5 = gk_bd_send_request($bd, $sql5);
    $t5 = mysqli_fetch_assoc($res5);

    //Compte le nombre de blablas qui mentionnent l’utilisateur
    $sql6 ="SELECT COUNT(*) AS NB
            FROM mentions
            WHERE meIDUser=$id ";
    $res6 = gk_bd_send_request($bd, $sql6);
    $t6 = mysqli_fetch_assoc($res6);


    //Permet de savoir si l'utilisateur est abonné à un autre
    $sql7 ="SELECT eaIDAbonne, eaIDUser
            FROM estabonne
            WHERE eaIDUser = '{$_SESSION['usID']}'
            AND eaIDAbonne = '$id'";
    $res7 = gk_bd_send_request($bd, $sql7);

    $pseudo = gk_html_proteger_sortie($t2['usPseudo']);
    $photo = gk_html_proteger_sortie($t2['usAvecPhoto']);
    $nom = gk_html_proteger_sortie($t2['usNom']);

    //Affichage du résumé de l'utilisateur
    echo '<p class="utilisateur_blablas"><img src="../', ($photo == 1 ? "upload/$id.jpg" : 'images/anonyme.jpg'), 
        '" class="imgAuteur" alt="photo de l\'utilisateur">',
        "<a href=\"utilisateur.php?id_abonne=$id\" title='Voir mes infos'>",$pseudo,"</a>",
        ' ', gk_html_proteger_sortie($nom),'<br>';
                        
    echo "<a href=\"blablas.php?id_abonne=$id\" title='Voir les blablas'>",gk_html_proteger_sortie($t3['NB'])," blablas</a>"," - <a href=\"mentions.php?id_abonne=$id\" title='Voir les mentions'>",gk_html_proteger_sortie($t6['NB'])," mentions</a>"," - <a href=\"abonnes.php?id_abonne=$id\" title='Voir les abonnés'>",gk_html_proteger_sortie($t5['NB'])," abonnés</a>"," - <a href=\"abonnements.php?id_abonne=$id\" title='Voir les abonnements'>",gk_html_proteger_sortie($t4['NB'])," abonnements</a>";

    //Gestion du bouton d'abonnement et de désabonnement
    if(($_GET!=NULL && isset($_GET['id_abonne'])) && ($_SESSION['usID']!=$_GET['id_abonne']) && !basename('mentions.php')){
        if (mysqli_num_rows($res7) == 0){
            echo '<label class="bouton_abonnement"><input type="checkbox" name="abonner[]" value="',$id,'">S\'abonner</label>';
        }else{
            echo '<label class="bouton_abonnement"><input type="checkbox" name="desabonner[]" value="',$id,'">Se désabonner</label>';
        }
    }
    echo '<br>','</p>';


    // libération des ressources
    mysqli_free_result($res2);
    mysqli_free_result($res3);
    mysqli_free_result($res4);
    mysqli_free_result($res5);
    mysqli_free_result($res6);
    mysqli_free_result($res7);
    mysqli_close($bd);
}

//_______________________________________________________________
/**
* Affichage du résumé des autres utilisateurs.
*
* La fonction affiche le pseudo, le nom, le nombres de blablas, le nombre de mentions, le nombre d'abonnés et le nombre d'abonnements des utilisateurs
*
* @param mysqli_result  $r      Objet permettant l'accès aux résultats de la requête 
* @param int            $id     L'ID de l'utilisateur
*/
function gk_autres_utilisateur_infos(mysqli_result $r, int $id): void {
     
    while ($t = mysqli_fetch_assoc($r)){
       $bd = gk_bd_connect();

       $id_abonnements = gk_bd_proteger_entree($bd, gk_html_proteger_sortie($t['usID']));
       $pseudo_abonnements = gk_bd_proteger_entree($bd, gk_html_proteger_sortie($t['usPseudo']));
       $photo_abonnements = gk_bd_proteger_entree($bd, gk_html_proteger_sortie($t['usAvecPhoto']));
       $nom_abonnements = gk_bd_proteger_entree($bd, gk_html_proteger_sortie($t['usNom']));

       //Compte le nombre de blablas publiés par l’utilisateur 
       $sql3 ="SELECT COUNT(*) AS NB
               FROM blablas
               WHERE blIDAuteur='$id_abonnements'";
       $res3 = gk_bd_send_request($bd, $sql3);
       $t3 = mysqli_fetch_assoc($res3);
       
       //Compte le nombre d’abonnements de l’utilisateur
       $sql4 ="SELECT COUNT(*) AS NB
               FROM estabonne
               WHERE eaIDUser='$id_abonnements'";
       $res4 = gk_bd_send_request($bd, $sql4);
       $t4 = mysqli_fetch_assoc($res4);

       //Compte le nombre d’abonnés de l’utilisateur
       $sql5 ="SELECT COUNT(*) AS NB
               FROM estabonne
               WHERE eaIDAbonne='$id_abonnements'";
       $res5 = gk_bd_send_request($bd, $sql5);
       $t5 = mysqli_fetch_assoc($res5);

       //Compte le nombre de blablas qui mentionnent l’utilisateur
       $sql6 ="SELECT COUNT(*) AS NB
               FROM mentions
               WHERE meIDUser='$id_abonnements' ";
       $res6 = gk_bd_send_request($bd, $sql6);
       $t6 = mysqli_fetch_assoc($res6);
       
       //Permet de savoir si l'utilisateur est abonné à un autre
       $sql7 ="SELECT eaIDAbonne, eaIDUser
               FROM estabonne
               WHERE eaIDUser = '{$_SESSION['usID']}'
               AND eaIDAbonne = '$id_abonnements'";
       $res7 = gk_bd_send_request($bd, $sql7);       
       
       
      //Affichage du résumé de l'utilisateur
       echo    '<li>', 
                   '<img src="../', ($photo_abonnements == 1 ? "upload/".gk_html_proteger_sortie($id_abonnements).".jpg" : 'images/anonyme.jpg'), 
                   '" class="imgAuteur" alt="photo de l\'utilisateur">',
                   "<a href=\"utilisateur.php?id_abonne=".gk_html_proteger_sortie($id_abonnements)."\" title='Voir mes infos'>",gk_html_proteger_sortie($pseudo_abonnements),"</a>",

                   ' ', gk_html_proteger_sortie($nom_abonnements),'<br>';
                   

        echo "<a href=\"blablas.php?id_abonne=".gk_html_proteger_sortie($id_abonnements)."\" title='Voir les blablas'>",gk_html_proteger_sortie($t3['NB'])," blablas</a>"," - <a href=\"mentions.php?id_abonne=".gk_html_proteger_sortie($id_abonnements)."\" title='Voir les mentions'>",gk_html_proteger_sortie($t6['NB'])," mentions</a>"," - <a href=\"abonnes.php?id_abonne=".gk_html_proteger_sortie($id_abonnements)."\" title='Voir les abonnés'>",gk_html_proteger_sortie($t5['NB'])," abonnés</a>"," - <a href=\"abonnements.php?id_abonne=".gk_html_proteger_sortie($id_abonnements)."\" title='Voir les abonnements'>",gk_html_proteger_sortie($t4['NB'])," abonnements</a>",'<br>';
        
        //Gestion du bouton d'abonnement et de désabonnement
        if($_SESSION['usID']!=$id_abonnements){
            if (mysqli_num_rows($res7)==0) {
                echo '<label class="bouton_abonnement"><input type="checkbox" name="abonner[]" value="',gk_html_proteger_sortie($id_abonnements),'">S\'abonner</label>';
            }else{
                echo '<label class="bouton_abonnement"><input type="checkbox" name="desabonner[]" value="',gk_html_proteger_sortie($id_abonnements),'">Se désabonner</label>';
            }
        }
                   
        echo '<br>','<br>','</li>';
    }
    mysqli_free_result($res3);
    mysqli_free_result($res4);
    mysqli_free_result($res5);
    mysqli_free_result($res6);
    mysqli_free_result($res7);
    mysqli_close($bd);

}
//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function gk_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function gk_session_exit(string $page = '../index.php'):void {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}

?>

