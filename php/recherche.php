<?php

session_cache_limiter('private_no_expire, must-revalidate');
ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)
($_GET && $_POST) && td_redirection("./deconnexion.php");
     

// Mise en forme de la Page générique-----
td_html_start('../styles/bookshop.css', 'Recherche');
$bd = td_bd_connect();
$error = null;
$connected = isset($_SESSION['idUser']) ? true : false;
td_social_banner($connected, '../', './');

$quoi = false;
$type='auteur';

//test de la méthode d'appel et génération du code en conséquence
if(isset($_POST['rechercher'])){ // -----------------------------------------Arrivée par méthode Post ----------------
	td_post_parameters('rechercher', 'Rechercher', array('quoi', 'type'), 3); //contrôle anti hacking
	$quoi = $_POST['quoi'];
	$type = $_POST['type'];
}
else if(td_verify_get_presence()){ // --------------------------------------Arrivée par méthode Get ----------------

	//Mise ne whishlist
	isset($_GET['whish']) && isset($_SESSION['idUser']) && $error = td_add_to_wish(td_control_get($_GET['whish']), $bd);

	//Ajout au Panier
	isset($_GET['cart']) && $error = td_add_to_cart(td_control_get($_GET['cart']), $bd);
	
	if(isset($_GET['quoi'])){
		$quoi = td_control_get($_GET['quoi']);
		$type = td_control_get($_GET['type']);
		td_verify_search_author($type);
	}
}

td_reserch_bar($quoi, $type, $error);

if($quoi)
	td_search($quoi, $type, $bd);

mysqli_close($bd);

$current_year = getdate();
td_footer($current_year['year']);

td_html_end();
ob_end_flush();

/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Création de la requête sql et traitement du résultat de la recherche
 *
 * @param 	String 	$quoi 		(sous)chaine de charactère a rechercher
 * @param 	String 	$type		Type de recherche (auteur ou titre de livre)
 * @return 	void
 */
function td_search($quoi, $type, $bd){

	// préparation de la requête
	$wanted =  td_bd_protect($bd, $quoi);

	//Génération de la requete sql
	$reserch = ($type === 'auteur') ? " AND auNom LIKE '%$wanted%')" : " AND liTitre LIKE '%$wanted%')";

	$where = "WHERE liID IN (SELECT liID
							FROM livres, aut_livre, auteurs
							WHERE liID = al_IDLivre
							AND al_IDAuteur = auID {$reserch}";
	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);

	td_pagination_start($bd, $quoi, $sql, 'liID', 'recherche', $type, true);
}


/**
 * Verification du bon nombre de paramètres et de la présence d'auteur -> utilisé pour recherche en cliquant sur un nom d'auteur
 * Autrement, redirection vers l'index 
 *
 * @param 	String 	$type		Type de recherche à vérifier
 * @return 	void
 */
function td_verify_search_author($type){
	if((count($_GET) < 2 &&  count($_GET) > 6 )){
		td_redirection("../index.php");	
	}
}

/**
 * Etablissement de la barre de recherche
 * 
 * @param 	String 	$quoi	Paramètre précédamment appelé
 * @return 	void
 */
function td_reserch_bar($quoi, $type, $error){

	td_add_result($error);
	
	//maintient du dernier type selectionné, auteur par défault
	$selected_aut = $type === 'auteur' ? 'selected' : '';
	$selected_livre = $type === 'liTitre' ? 'selected' : '';
	
	echo "<h3>Rechercher par partie d'un nom d'auteur ou d'un titre</h3>",

			'<form method="post" action="recherche.php">',
			'<p>',
				'<label for="Recherche">Rechercher</label> : <input type="text" name="quoi" spellcheck="false" autofocus id="Recherche"';
				if($quoi === false)
					echo ' placeholder="Ex : Moore"';
				else
					echo ' value="', $quoi, '"';
			echo
				' required/>',
				' dans  ',
				'<select name="type">',
					'<option ', $selected_aut, ' value="auteur">Auteurs</option>',
					'<option ', $selected_livre, ' value="liTitre">Titre Livres</option>',
				'</select>',
				'	<input class="btn" type="submit" name="rechercher" value="Rechercher">',
			'</p>',
			'</form>';
}

?> 