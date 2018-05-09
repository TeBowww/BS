<?php


/*################################################################################################
										Creation page
################################################################################################*/
ob_start('ob_gzhandler');
session_start();

require_once 'php/bibli_generale.php';
require_once 'php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

td_html_start('./styles/bookshop.css', 'BookShop | Bienvenue');
$connected = isset($_SESSION['idUser']) ? true : false;
td_social_banner($connected, './', 'php/');

$bd = td_bd_connect();

$error = null;
if($connected == true && isset($_GET['whish'])){
	$error = td_add_to_wish($_GET['whish'], $bd);
}
if(isset($_GET['cart'])){
	$error = td_add_to_cart($_GET['cart'], $bd);
}

td_contents_index($error, $bd);

mysqli_close($bd);

$current_year = getdate();
td_footer($current_year['year']);

td_html_end();

ob_end_flush();


/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage du contenu global du site
 *
 * @param 
 * @return 	void
 */
function td_contents_index($error, $bd){

	td_add_result($error);

	echo
	'<h1>Bienvenue sur BookShop !</h1>',
			
			'<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
			
			'<p>Nouveau venu sur BookShop ? Consultez notre <a href="./html/presentation.html">page de présentation</a> !';

	

	echo '<h2>Dernières nouveautés </h2>',
		'<p>Voici les 4 derniers articles ajoutés dans notre boutique en ligne :</p>',
		'<div class="bloc_book">';

		td_get_new($bd);

	echo
		'</div>',
		'<h2>Top des ventes</h2>',
			
		'<p>Voici les 4 articles les plus vendus :</p>',
	'<div class ="bloc_book">';

	td_get_mv($bd);	

	echo '</div>';
}

/**
 * Affichage le tableau de livre
 *
 * @param 
 * @return 	$tab 	tableau avec les meilleurs ventes 
 */
function td_get_mv($bd){
	if(!isset($_SESSION['idUser'])){
		$sql = "SELECT DISTINCT liTitre, auNom, auPrenom, liID
				FROM livres INNER JOIN  aut_livre ON liID = al_IDLivre
							INNER JOIN editeurs ON liIDEditeur = edID
							INNER JOIN auteurs ON al_IDAuteur = auID
							INNER JOIN (SELECT ccIDLivre, SUM(ccQuantite) as tot
										FROM compo_commande
										GROUP BY ccIDLivre
										ORDER BY  tot) AS T ON ccIDLivre = liID ORDER BY tot DESC";
	}
	else{
			$sql = "SELECT DISTINCT liTitre, auNom, auPrenom, liID, listIDLivre
				FROM livres INNER JOIN  aut_livre ON liID = al_IDLivre
							INNER JOIN editeurs ON liIDEditeur = edID
							INNER JOIN auteurs ON al_IDAuteur = auID
							LEFT OUTER JOIN listes ON liID = listIDLivre AND listIDClient = ".$_SESSION['idUser']."
							INNER JOIN (SELECT ccIDLivre, SUM(ccQuantite) as tot
										FROM compo_commande
										GROUP BY ccIDLivre
										ORDER BY  tot) AS T ON ccIDLivre = liID ORDER BY tot DESC";
	}

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

	td_data_traitement_vertical($res, './', true);

	// Liberation des ressources ----------------
	mysqli_free_result($res);
}

/**
 * Affichage le tableau de livre
 *
 * @param 
 * @return 	$tab 	tableau avec les quatre derniers livres
 */
function td_get_new($bd){
	$where = " ORDER BY liID DESC";
	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);
	

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

	td_data_traitement_vertical($res, './', true);

	// Liberation des ressources ----------------
	mysqli_free_result($res);
}

?>