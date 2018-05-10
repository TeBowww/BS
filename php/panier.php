<?php

ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); 

$connected = isset($_SESSION['idUser']) ? true : false;
($_GET && $_POST) && td_redirection("./deconnexion.php");

td_html_start('../styles/bookshop.css', 'Panier');
td_social_banner($connected, '../', './');

if(!isset($_SESSION['panier'])){ //--------------Création d'un pannier si nécéssaire
	td_print_empty_cart();
}
else if(isset($_POST['btn_valid_cart'])){ // ----Traitement de la validation du panier
	if(!$connected){
		td_unloged_content();
	} 
	else{
		td_post_parameters('btn_valid_cart', 'Valider Le Panier', array(), 1); //vérification anti hack
		isset($_SESSION['idUser']) || td_redirection("./deconnexion.php");
		td_insert_new_command();
		td_clean_cart();
		td_new_command_content();
	}
}
else{
	$get_possible_value = array('del', 'upcart', 'downcart');
	isset($_GET['del']) && td_verify_get_instance($get_possible_value, 1, './') && td_delete_from_cart(td_control_get($_GET['del']));
	isset($_GET['upcart']) && td_verify_get_instance($get_possible_value, 1, './') && td_modify_quantite(+1, td_control_get($_GET['upcart']));
	isset($_GET['downcart']) && td_verify_get_instance($get_possible_value, 1, './') && td_modify_quantite(-1, td_control_get($_GET['downcart']));
	td_print_cart_content();
} 

$current_year = getdate();
td_footer($current_year['year']);
td_html_end();
ob_end_flush();

/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage du contenu pour un utilisateur non connecté essayant de valider un panier
 *
 * @return 	void
 */
function td_unloged_content(){
	echo '<p class="bloc_error">Pour valider votre panier vous devez être connecté.</p>',
		'<p>Creer un compte ou connectez vous <a href="login.php">ici </a></p>';
}

/**
 * Affichage du contenu d'un panier vide
 *
 * @return 	void
 */
function td_print_empty_cart(){
	echo '<p class="bloc_error">Le panier est vide</p>';
}

/**
 * Affichage du contenu d'un panier non vide'
 *
 * @return 	void
 */
function td_print_cart_content(){

	$nbArticles = count($_SESSION['panier']['liID']);
	echo '<div class="sub_box">',
			 '<div class="entete"> Mon Pannier </div>';

	for ($i=0 ;$i < $nbArticles ; $i++){ // ----------- Affichage des livres contenu dans le panier -- //

		if($_SESSION['panier']['liID'][$i] === null){
			continue;
		}
		$to_supp = "<a href=\"?del={$_SESSION['panier']['liID'][$i]}\"  title=\"delete\">Supprimer</a>";

		$book=array('id' => $_SESSION['panier']['liID'][$i], 'quantite' =>$_SESSION['panier']['QtLivre'][$i], 'titre' => $_SESSION['panier']['liTitre'][$i], 'prix' => $_SESSION['panier']['prix'][$i], 'total' =>  $_SESSION['panier']['prix'][$i] * $_SESSION['panier']['QtLivre'][$i]);

		echo '<hr color="gray" width="70%">';
		td_print_cart_book($book, true);		
	}								// ---------------------------------------------------------//

	echo 	'<h2>Total des achats</h2>',
			 'Prix total : ', td_prix_total(), ' Euros ',

			 '<form method="post" action="panier.php" >',
			  		td_button(TD_Z_SUBMIT, 'Valider Le Panier', 'btn_valid_cart', ''),
			  '</form>',

		'</div>';	
}

/**
 * Calcul du prix total d'un panier
 *
 * @return Float	$total 	prix total du panier
 */
function td_prix_total(){
   $total_book = isset($_SESSION['panier']) ? count($_SESSION['panier']['liID']) : 0;
   $total=0;
   for($i = 0; $i < $total_book; $i++)
   {
      $total += $_SESSION['panier']['QtLivre'][$i] * $_SESSION['panier']['prix'][$i];
   }
   return $total;
}

/**
 * Insertion en BDD du contenu d'une commande
 *
 * @return 	$void
 */
function td_insert_new_command(){
	$total_book = isset($_SESSION['panier']) ? count($_SESSION['panier']['liID']) : 0;
	$bd = td_bd_connect();
	td_insert_command($bd);
	$command_id = mysqli_insert_id($bd);

	$sql = 'INSERT INTO compo_commande (ccIDCommande, ccIDLivre, ccQuantite) VALUES';
	for($i = 0; $i < $total_book; $i++){
		td_verify_book_existence($_SESSION['panier']['liID'][$i], $bd);
		$sql .= td_insert_compo_command($bd, $command_id, $i);
		$sql .= $i != $total_book -1 ? ',' : ';';
	}
	mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	mysqli_close($bd);	
}

/**
 * Insertion en BDD de la commande
 *
 * @param 	$bd 	Connexion a la BDD
 * @return 	$void
 */
function td_insert_command($bd){
	date_default_timezone_set('Europe/Paris');
	$date = td_get_current_date();
	$heure = date("Hi");
	$sql = "INSERT INTO commandes (coIDClient, coDate, coHeure)
			VALUES(".(int)$_SESSION['idUser'].", ". td_bd_protect($bd, $date).", ".td_bd_protect($bd, $heure).");";

	mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
}

/**
 * Création de la requête sql d'insertion d'une ligne de compo_command
 *
 * @param 			$bd 	 		Connexion à la BDD
 * @param 	Int		$id_command 	Numero de la commande
 * @param 	Int		$i 	 			Element courrant du panier à traiter
 * @return 	String 	 				partie sql de la reqête pour la compo en cours
 */
function td_insert_compo_command($bd, $id_command, $i){
	return "(".$id_command.", ". td_bd_protect($bd, $_SESSION['panier']['liID'][$i]).", ". td_bd_protect($bd, $_SESSION['panier']['QtLivre'][$i]).")";
}

/**
 * Effacement du panier en cours
 *
 * @return void
 */
function td_clean_cart(){
	unset($_SESSION['panier']);
}


/**
 * Affichage d'un message de réussite de l'ajout de la commande et d'un lien vers la page mes_commandes.php
 *
 * @return void
 */
function td_new_command_content(){
	echo '<div class="bloc_success">',
			'La commande a été réalisé avec succes. <br>',
			'Retrouvez les informations de cette commande dans la rubrique <a href="mes_commandes.php">mes commandes</a>',
		'</div>';
}

/**
 * Supression d'un élément au panier
 *
 * @param 	Integer 	$delete 	id de l'élément à supprimer
 * @return 	void
 */
function td_delete_from_cart($delete){

																// ----- Recherche de la position de l'objet à supprimer

	$indice = array_search($delete,$_SESSION['panier']['liID']); 		

																// ------ Supression des champs associés à l'objet
	unset($_SESSION['panier']['liID'][$indice]);
	$_SESSION['panier']['liID'] = array_values($_SESSION['panier']['liID']);

	unset($_SESSION['panier']['QtLivre'][$indice]);
	$_SESSION['panier']['QtLivre'] = array_values($_SESSION['panier']['QtLivre']);

	unset($_SESSION['panier']['prix'][$indice]);
	$_SESSION['panier']['prix'] = array_values($_SESSION['panier']['prix']);

	unset($_SESSION['panier']['liTitre'][$indice]);
	$_SESSION['panier']['liTitre'] = array_values($_SESSION['panier']['liTitre']);

																// ----- Si le pannier est vide, on le supprime
	if(empty($_SESSION['panier']['liID'])){
		td_supprime_panier();
		td_redirection("#");
	}
}

/**
 * Modification de la quantité avec la valeur choisie / si la quantité devient nulle, supression du panier de l'élément
 *
 * @param 	Integer		$value 	Quantité à modifier
 * @param 	Integer 	$liID 	id de l'élément à modifier
 * @return 	void
 */
function td_modify_quantite($value, $liID){
	$positionProduit = array_search($liID, $_SESSION['panier']['liID']);

	if ($positionProduit !== false)
      {
         $_SESSION['panier']['QtLivre'][$positionProduit] += $value ;

         $_SESSION['panier']['QtLivre'][$positionProduit] <= 0 ? td_delete_from_cart($liID) : ''; // Suppression de l'objet si la quantité est nulle
      }
    else{
   		td_add_result(array('error' => 'Erreur, nomero de livre non correct'));
    }
}

?>