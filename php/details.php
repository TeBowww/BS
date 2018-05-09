<?php
ob_start('ob_gzhandler'); 
session_start(); 

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

$error = null;

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)
$connected = isset($_SESSION['idUser']) ? true : false;
($_GET && $_POST || $_POST) && td_redirection("./deconnexion.php");
     
$valueArticle = '';
$bd = td_bd_connect();
if($connected === true && isset($_GET['whish'])){
	$error = td_add_to_wish($_GET['whish'], $bd);
}
if(isset($_GET['cart'])){
	$error = td_add_to_cart($_GET['cart'], $bd);
}
td_verify_parameters($_GET['article'], true, './') || td_redirection("./deconnexion.php");
$valueArticle = td_control_get ($_GET['article']);


td_html_start('../styles/bookshop.css', 'Details');
td_social_banner($connected, '../', './');

tdl_contenu($bd, $valueArticle, $error);

$current_year = getdate();
td_footer($current_year['year']);


td_html_end();
ob_end_flush();


/*################################################################################################
										Fonctions
################################################################################################*/

/**
 *	Contenu de la page : formulaire de recherche + résultats éventuels 
 *
 * @param   string    $valueArticle livre a afficher
 * @global  array     $_POST
 * @global  array     $_GET
 */
function tdl_contenu($bd, $valueArticle, $error) {

	td_add_result($error);
	echo '<h2>Information sur le livre :</h2>';
	
	$q = td_bd_protect($bd, $valueArticle); 

	$where = "WHERE liID='$q'";
	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd,$sql);

	$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'ISBN13' => $t['liISBN13'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom'], 'bio' => $t['auBio'], 'pays' => $t['auPays'])),
							'annee' => $t['liAnnee'],
							'langue' => $t['liLangue'],
							'cat' => $t['liCat'],
						);
			$to_whish = isset($_SESSION['idUser']) ? $t['listIDLivre'] : false;
		}
		else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom'], 'bio' => $t['auBio'], 'pays' => $t['auPays']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
    
	if ($lastID != -1) {
		td_afficher_detail($livre, '../', $to_whish);	
	}
	else{
		echo '<p>Aucun livre n\'a été trouvé. Le livre n\'existe pas ou n\'existe plus sur le site</p>';
	}
}

/**
 *	Affichage des information du livre
 *
 * @param  $livre Donnée du livre a afficher, $prefix prefix a utiliser pour les fonction $to_wish Si l'utilsateur est connecter et n'a pas le livre dans sa liste de souhait
 *	affiche l'optiond 'ajout a la liste de souhait
 *
 * @return Affichage des detail d'un livre
 */
function td_afficher_detail($livre, $prefix, $to_whish) {
	echo '<div class="details">';
	echo '<a class="addToCartDetail" href="?cart=', $livre['id'], '&article=', $livre['id'], '" title="Ajouter au panier"> </a>';
	if(isset($_SESSION['idUser']) && $to_whish != true){
		echo '<a class="addToWishlistDetail" href="?whish=', $livre['id'], '&article=', $livre['id'], '" title="Ajouter à la liste de cadeaux"></a>';
	}
	echo '<a href="', $prefix, 'php/details.php?article=', $livre['id'], '" title="Voir détails"><img src="', $prefix, 'images/livres/', $livre['id'], '_mini.jpg" alt="', 
			  td_entities_protect($livre['titre']),'"></a>';
		  echo	'<b>', td_entities_protect($livre['titre']), '</b> <br>',
			  '<b>Ecrit par : </b>';
	  
	  $i = 0;
	  td_print_author($livre['auteurs'], './');
		  echo	'<br><b>Resume :</b> ', td_entities_protect($livre['resume']) ,'<br>',
				  '<br><b>Categorie :</b> ', td_entities_protect($livre['cat']), '<br>',
				  '<b>Prix : </b>', $livre['prix'], ' &euro;<br>',
				  '<b>Pages : </b>', $livre['pages'], '<br>',
				  '<b>ISBN13 : </b>', td_entities_protect($livre['ISBN13']), ' <br>',
				  '<b>Année de publication : </b>', $livre['annee'], ' <br>',
				  '<b>Langue : </b>', td_entities_protect($livre['langue']), ' <br><br>',
				  '<b>Editeur :</b> <a class="lienExterne" href="http://', td_entities_protect($livre['edWeb']), '" target="_blank">', td_entities_protect($livre['edNom']), '</a>',
				  '</div>',
				  '<h2>Information auteur : </h2>';
				  echo '<div class="details">';
				  
			foreach ($livre['auteurs'] as $auteur) {
			  $supportLien ="{$auteur['prenom']} {$auteur['nom']}";
			  if ($i > 0) {
				  echo '<br><br> ';
		  }
		  $i++;
		  
		  echo '<a href="', $prefix, 'php/recherche.php?type=auteur&quoi=', urlencode($auteur['nom']), '">',td_entities_protect($supportLien), '</a> , Née en ', $auteur['pays'] ,'<br>',
		  'Bio : ', $auteur['bio'] ,'<br><br>';
	  }
	  echo '</div>';
}

?>
