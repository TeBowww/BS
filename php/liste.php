<?php
session_cache_limiter('private_no_expire, must-revalidate');
ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); 

$connected = isset($_SESSION['idUser']) ? true : false;
($_GET && $_POST) && td_redirection("./deconnexion.php");
$_GET && !isset($_GET['qui']) && !isset($_GET['del']) && !isset($_GET['quoi']) && !isset($_GET['cart']) && !isset($_GET['wish']) && td_redirection("./deconnexion.php");

td_html_start('../styles/bookshop.css', 'Liste de Shouaits');
td_social_banner($connected, '../', './');

$error = null;
$bd = td_bd_connect();

isset($_GET['del']) && td_delete_from_list($bd, td_control_get($_GET['del']));
isset($_GET['cart']) && $error = td_add_to_cart(td_control_get($_GET['cart']), $bd);
$quoi = $connected ? $_SESSION['idUser'] : '';
$qui=false;


if(isset($_POST['rechercher'])){
	td_post_parameters('rechercher', 'Rechercher', array('quoi', 'qui'), 3);
	$qui = td_entities_protect($_POST['qui']);
	$quoi = td_entities_protect($_POST['quoi']);
}

isset($_GET['quoi'])  && td_verify_mail_id($bd, td_control_get($_GET['quoi'])) && $quoi = td_entities_protect($_GET['quoi']);

if($connected == true && isset($_GET['whish'])){
	$error = td_add_to_wish($_GET['whish'], $bd);
}

if($connected){
	td_list_content($bd, $quoi, $error, $quoi == $_SESSION['idUser']);
}
else{
	td_list_content($bd, $quoi, $error, false);
}

td_bar_search($bd,$qui,$quoi);
$qui && td_wish_search($bd,$qui);

mysqli_close($bd);

$current_year = getdate();
td_footer($current_year['year']);
td_html_end();
ob_end_flush();



/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage du contenu de la whishlist de lutilisateur choisis (soi ou autre)
 *
 * @param 			$bd 	 		Connexion à la BDD
 * @param 	Mixed	$quoi 			Utilisateur propriétaire de la whishlit à afficher
 * @param 	array 	$error 	 		Tableau des erreurs / succès à afficher (lors de la mise au panier ou supression d'un element)
 * @param 	Bollean $is_my_whish	Information sur l'appartenance de la liste affiché à l'utilisateur connecté ou non
 *
 * @return 	void
 */
function td_list_content($bd, $quoi, $error, $is_my_wish){
	td_add_result($error);

	if(isset($_SESSION['idUser'])  && $is_my_wish){
		echo 
			'<h2>Ma liste de shouait</h2>';
		$where = "WHERE liID IN  (SELECT listIDLivre
						  FROM listes
						  WHERE listIDClient =".td_bd_protect($bd,$quoi).")";
	}
	else{
		echo 
			'<h2>Liste de souhait de l\'adresse : ', $quoi, '</h2>';
			$where = "WHERE liID IN (SELECT listIDLivre
						  FROM listes,clients
						  WHERE cliID=listIDClient
 						  AND cliEmail='".td_bd_protect($bd,$quoi)."')";
	}

	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);
	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	td_pagination_start($bd, $quoi, $sql, 'liID' , 'list', null, !$is_my_wish);
}


/**
 * Supression d'un élément de la liste de shouait de l'utilisateur
 *
 * @param 			$bd 	 		Connexion à la BDD
 * @param 	Int 	$delete 		id de l'élément à supprimer de la liste de shouait
 *
 * @return 	void
 */
function td_delete_from_list($bd, $delete){
	$wanted = td_bd_protect($bd, $delete);

	$sql = "DELETE FROM listes
			WHERE listIDClient=". $_SESSION['idUser'].
			" AND listIDLivre=". $wanted;
	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
}

/**
 * Verification que l'email existe
 *
 * @param 			$bd 	 	Connexion à la BDD
 * @param 	String 	$quoi 		email à chercher
 *
 * @return 	boolean 			existence de l'email en BDD
 */
function td_verify_mail_id($bd, $quoi){
	if(isset($_SESSION['idUser'])){
		if($quoi != $_SESSION['idUser']){
			$sql = "SELECT cliID
					FROM clients
					WHERE cliID = ".$_SESSION['idUser']." 
					AND cliEmail = '".td_bd_protect($bd, $quoi)."'";
			$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
			while(mysqli_fetch_assoc($res)){
				return false;
			}
			return true;
		}
		return false; 
	}
	return true;	
}

/**
 * Création d'une barre de recherche d'email utilisateurs
 *
 * @param 			$bd 	 	Connexion à la BDD
 * @param 	String 	$qui 		email de l'utilisateur cherché
 * @param 	Int 	$quoi 		numero de l'utilisateur
 *
 * @return 	void
 */
function td_bar_search($bd,$qui,$quoi){
	$searched = $qui == '' ? '' : 'autofocus';
 echo
    '<h2>Recherche de Liste de souhait </h2>',
   '<h3>Rechercher par partie d\'une adresse email</h3>',

			'<form method="post" action="liste.php">',
			'<p>',
				'<label for="Recherche">Rechercher</label> : <input type="text" name="qui" spellcheck="false" ',$searched,' id="Recherche"';
				if($qui === false)
					echo ' placeholder="Ex : dupond@mail.fr"';
				else
					echo ' value="', $qui, '"';
			echo
				' required/>',
				'	<input type="hidden" name="quoi" value="',$quoi,'">',
				'	<input class="btn" type="submit" name="rechercher" value="Rechercher">',
			'</p>',
			'</form>';
}

/**
 * recherche de l'utilisateur et affichage de sa whishlist
 *
 * @param 			$bd 	 	Connexion à la BDD
 * @param 	String 	$qui 		email de l'utilisateur cherché
 *
 * @return 	void
 */
function td_wish_search($bd,$qui){

	// préparation de la requête
	$wanted =  td_bd_protect($bd, $qui);
		// Création et envoie de la recherche -----
		$sql = "SELECT cliEmail
				FROM clients
				WHERE cliEmail = '$wanted'";

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	td_data_traitement_wish($res);
}

/**
 * Affichage de l'email recherche si il existe sous forme de lien vers sa whishlist
 *
 * @param 	sql 	$res 		Données de requête sql
 *
 * @return 	void
 */
function td_data_traitement_wish($res){
        $last_id= -1;
        $buffer=array();
        while($tableau = mysqli_fetch_assoc($res)){
             echo 
		        '<div class="wish_search bcSell">',
		        	'<a href="liste.php?quoi=', $tableau['cliEmail'], '">', $tableau['cliEmail'], '</a> <br>',
		        '</div>';

            $last_id = $tableau['cliEmail'];
        }

        if($last_id == -1){
                echo '<p>Aucun Email ne correspond à votre recherche</p>';
        }
}
?>