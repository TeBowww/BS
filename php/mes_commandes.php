<?php

ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

td_verify_loged(isset($_SESSION['idUser']));
($_GET && $_POST) && td_redirection("./deconnexion.php");

// Mise en forme de la Page générique-----
td_html_start('../styles/bookshop.css', 'mes_commandes');

td_social_banner(true, '../', './');

$bd = td_bd_connect(); //une seule connexion à la base pour le script

td_commandes_content($bd);

mysqli_close($bd);

$current_year = getdate();
td_footer($current_year['year']);
td_html_end();
ob_end_flush();



/*################################################################################################
										Fonctions
################################################################################################*/
/**
 * Recherche en BDD des commandes et lancement de l'impression des commandes
 *
 * @param 	$bd 	Connexion a la BDD
 * @return 	$void
 */
function td_commandes_content($bd){
	echo 
	'<h2>Recapitulatif de mes commandes</h2>';

	$sql = "SELECT coID, coDate, coHeure, liID, liTitre, liPrix, ccQuantite, SUM(liPrix * ccQuantite) as PrixTotal
			FROM livres, compo_commande, commandes
			WHERE coID = ccIDCommande
			AND ccIDLivre = liID
			AND coIDClient =".(int)$_SESSION['idUser'].
			" GROUP BY liID, coID
			ORDER BY coDate DESC, coHEURE DESC";
	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

	$command=array();
	$last_co_id = -1;
	$total = 0;
	while($tableau = mysqli_fetch_assoc($res)){
		if($last_co_id != $tableau['coID'] && $last_co_id != -1){
			$command['prix_total'] = $total;
			td_print_commande($command);
			$total = 0;
			$command = array();
		}

		if($last_co_id === -1 || $last_co_id != $tableau['coID']){
			$command = array('id_co' => $tableau['coID'], 'date' => $tableau['coDate'], 'heure' => $tableau['coHeure'], 'id' => $tableau['liID'], 'prix_total' => 0, 'book' => array());
		}

		$command['book'][] = array('id' => $tableau['liID'], 'quantite' => $tableau['ccQuantite'], 'titre' => $tableau['liTitre'], 'prix' => $tableau['liPrix'], 'total' => $tableau['PrixTotal']);
		$total += $tableau['PrixTotal'];

		if(empty($command)){
			$command = array('id_co' => $tableau['coID'], 'date' => $tableau['coDate'], 'heure' => $tableau['coHeure'], 'id' => $tableau['liID'], 'prix_total' => $total, 'book' => array());
		}
		$last_co_id = $tableau['coID'];

	}

	if($last_co_id == -1){
			echo '<p>Vous n\'avez aucune commande enregistré</p>';
	}
	else{
		td_print_commande($command);
	}
}

/**
 * Affichage des commandes et des livres de l'utilisateur
 *
 * @param 	Array 	$command 	Tableau contenant les commandes et leur contenu
 * @return 	$void
 */
function td_print_commande($command){

	//formatage de la date
	$date_temp = td_entities_protect($command['date']);
	$jours = substr($date_temp, 6, 2);
	$mois = substr($date_temp, 4, 2);
	$annee = substr($date_temp, 0, 4);
	$date = "{$jours}/{$mois}/{$annee}";

	//formatage de l'heure
	$heure_temp = td_entities_protect($command['heure']);
	if(strlen($heure_temp) === 3){
		$h = substr($heure_temp, 0, 1);
		$m = substr($heure_temp, 1, 2);
	}
	else{
		$h = substr($heure_temp, 0, 2);
		$m = substr($heure_temp, 2, 2);
	}
	$time = "{$h}H{$m}";
	
	echo
	'<div class="sub_box">',
	 '<div class="entete">Commande N°',$command['id_co'],' </div>',
		'<table>',
			td_form_ligne('Passée le', $date),
			td_form_ligne('A', $time),
			td_form_ligne('Prix Total', td_entities_protect($command['prix_total']).'€'),
		'</table>',
		'<h2>Détails de la commande</h2>';
			foreach($command['book'] as $livre){
				td_print_cart_book($livre, false);
			}

	echo 
	'</div>';				
}

?>