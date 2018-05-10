<?php

session_cache_limiter('private_no_expire, must-revalidate');
ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL);
($_GET && $_POST) && td_redirection("./deconnexion.php");
     
// Mise en forme de la Page générique-----
td_html_start('../styles/bookshop.css', 'Recherche');
$connected = isset($_SESSION['idUser']) ? true : false;
td_social_banner($connected, '../', './');

$bd = td_bd_connect();
$error = null;
$quoi = false;
$type='auteur';

//test de la méthode d'appel de la page et génération du code en conséquence

if(isset($_POST['rechercher'])){ // -----------------------------------------Arrivée par méthode Post ----------------
	td_post_parameters('rechercher', 'Rechercher', array('quoi', 'type'), 3);
	$quoi = $_POST['quoi'];
	$type = $_POST['type'];
}
else if($_GET){ // --------------------------------------Arrivée par méthode Get ----------------

	//Mise ne whishlist
	isset($_GET['whish']) && isset($_SESSION['idUser']) && $error = td_add_to_wish(td_control_get($_GET['whish']), $bd);

	//Ajout au Panier
	isset($_GET['cart']) && $error = td_add_to_cart(td_control_get($_GET['cart']), $bd);
	
	//recherche d'auteur
	if(isset($_GET['quoi'])){
		$quoi = td_control_get($_GET['quoi']);
		$type = td_control_get($_GET['type']);
		$get_possible_value = array('whish', 'cart', 'quoi', 'type', 't', 'p');
		td_verify_get_instance($get_possible_value, 2, './');
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

	$wanted =  td_bd_protect($bd, $quoi);
	$reserch = ($type === 'auteur') ? " AND auNom LIKE '%$wanted%')" : " AND liTitre LIKE '%$wanted%')";
	$where = "WHERE liID IN (SELECT liID
							FROM livres, aut_livre, auteurs
							WHERE liID = al_IDLivre
							AND al_IDAuteur = auID {$reserch}";

	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);

	td_pagination_start($bd, $quoi, $sql, 'liID', 'recherche', $type, true); // affichage des livres avec pagination
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
				echo $quoi === false ?  ' placeholder="Ex : Moore"' : ' value="', $quoi, '"';

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