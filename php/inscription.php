<?php

ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

// Mise en forme de la Page générique-----
td_html_start('../styles/bookshop.css', 'Inscription');

//S l'utilisateur est déjà connecté : redirection vers l'index
td_verify_unloged(isset($_SESSION['idUser']));
($_GET && $_POST) && td_redirection("./deconnexion.php");

$connected = false;
td_social_banner($connected, '../', './');
$error = array();


//Il y a deja eu soumission d'un formulaire d'inscription
if(isset($_POST['btn_soumission_inscription'])){

	td_post_parameters('btn_soumission_inscription', 'je m inscrit !', array('email', 'passwd', 'names','naissance_j', 'naissance_m', 'naissance_a', 'pass_repet' ), 9);

	$bd = td_bd_connect(); //une seule connexion à la base pour le script
	$error = td_verify_inscription_data($bd);

	//Réaffichage de la page avec les erreurs survenues
	if(!empty($error)){
		td_form_content($error);
		mysqli_close($bd);
	}
	else{	//Inscription del'utilisateur dans la base et on le redirection vers la page consulté avant l'inscription
		td_valide_inscription($bd, $_POST['email'], $_POST['names'], $_POST['passwd'], $_POST['naissance_m'], $_POST['naissance_j'], $_POST['naissance_a']);

		//recuperation de l'id crée
		
		mysqli_close($bd);
		if(isset($_POST['page_pre'])) {
			td_redirection($_POST['page_pre']);
		}
		else{
			td_redirection("../index.php");
		}
	}
	
}
else{
	td_form_content($error);
}


$current_year = getdate();
td_footer($current_year['year']);
td_html_end();
ob_end_flush();



/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage de la page et des eventuelles erreurs
 *
 * @param 	array 	$error		tableau des erreurs comises lors de la soumission du formulaire (champ manquants, éronnés)
 *
 * @return 	void
 */
function td_form_content($error){

	echo '<h1>Inscription à Bookshop</h1>';

	td_error_formulaire($error);

	echo 
		'<p>Pour vous inscrire merci de renseigner les informations suivantes</p>',
		'<form id="fomulaire_ins" method="post" action="inscription.php">';

		//definition de la page à afficher à l'issus de la connexion
		if(isset($_POST['page_pre'])) 
			echo td_button(TD_Z_HIDDEN, 'source', $_POST['page_pre']);
		else if(isset($_SERVER['HTTP_REFERER']))
			echo td_button(TD_Z_HIDDEN, 'source', $_SERVER['HTTP_REFERER']);


	echo
		'<table>',
			td_form_ligne("email", td_form_input(TD_Z_TEXT, "email", "email@something.ext")),
			td_form_ligne("Choissiez un mot de passe", td_form_input(TD_Z_PASSWORD, "passwd", "")),
			td_form_ligne("Répetez le mot de passe", td_form_input(TD_Z_PASSWORD, "pass_repet", "")),
			td_form_ligne("Nom et Prenom", td_form_input(TD_Z_TEXT, "names", "")),
			td_form_ligne("Date naissance", td_form_date("naissance")),
			'<tr><td colspan="2" >', td_button(TD_Z_SUBMIT, "je m inscrit !", "btn_soumission_inscription"),'</td></tr>',
		'</table>',
		'</form>';
}

/**
 * Verification des champs du formulaire d'inscription
 *
 * @param 	array 	$bd		connextion à la base de donnée
 *
 * @return 	array 	$error 	Tableau des erreurs relevées
 */
function td_verify_inscription_data($bd){
	$error = array();

	$error_mail = td_verify_email($bd, $_POST['email']);
	if($error_mail)
		$error[] = $error_mail;	

	$error_pass = td_verify_passwd($_POST['passwd'], $_POST['pass_repet']);
	if($error_pass)
		$error[] = $error_pass;	

	$error_date = td_verify_date($_POST['naissance_m'], $_POST['naissance_j'], $_POST['naissance_a']);
	if($error_date)
		$error[] = $error_date;	

	$error_name = td_verify_name($_POST['names']);
	if($error_name)
		$error[] = $error_name;	

	return $error;
}

/**
 * Verification de la validité du champ Date
 *
 * @param 	array 	$month		Mois de naissance entré par l'utilisateur
 * @param 	array 	$day		Jour de naissance entré par l'utilisateur
 * @param 	array 	$year		Année de naissance entré par l'utilisateur
 *
 * @return 	String 	$chaine 	Erreur relevée
 */
function td_verify_date($month, $day, $year){
	if( mb_strlen($month, 'utf-8') === 1){
		$month = "0".$month;
	}
	if( mb_strlen($day, 'utf-8') === 1){
		$day = "0".$day;
	}

	$current_year = td_get_current_date();
	$user_year = intval($year.$month.$day);
	if(checkdate(intval($month), intval($day), intval($year)) === false)
		return "- La date doit être une date valide";
	if(($current_year - $user_year) <= 180000)
		return "- Vous devez être majeur pour vous inscrire";
}

/**
 * Inscription dans la base de donnée
 *
 * @param 	array 	$bd			connexion à la base de donnée
 * @param 	array 	$mail 		email de l'utilisateur
 * @param 	array 	$nom 		nom et prénom de l'utilisateur
 * @param 	array 	$mdp 		mot de passe de l'utilisateur
 * @param 	array 	$month 		mois de naissance de l'utilisateur
 * @param 	array 	$day 		jour de naissance de l'utilisateur
 * @param 	array 	$year 		année de naissance de l'utilisateur
 *
 * @return 	void
 */
function td_valide_inscription($bd, $mail, $nom, $mdp, $month, $day, $year){
	if( mb_strlen($month, 'utf-8') === 1){
		$month = "0".$month;
	}
	if( mb_strlen($day, 'utf-8') === 1){
		$day = "0".$day;
	}

	$p_mail =  td_bd_protect($bd, $mail);
	$p_nom = td_bd_protect($bd, $nom);
	$p_mdp = td_bd_protect($bd, $mdp);
	$p_month = td_bd_protect($bd, $month);
	$p_day = td_bd_protect($bd, $day);
	$p_year = td_bd_protect($bd, $year);
	$p_naissance = intval($p_year.$p_month.$p_day);
	

	// Création et envoie de la recherche -----
	
	$sql = "INSERT INTO clients(cliNomPrenom, cliEmail, cliPassword, cliDateNaissance, cliAdresse, cliCP, cliPays, cliVille)
			VALUES (\"".$p_nom."\",\"".$p_mail."\",\"".md5($p_mdp)."\",".$p_naissance.", \"\", 0, \"France\", \"\")";

							
	mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

	$cliID = mysqli_insert_id($bd);
	$_SESSION['idUser'] = $cliID;
}

?>