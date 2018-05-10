<?php

ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL);
td_verify_loged(isset($_SESSION['idUser']));
($_GET && $_POST) && td_redirection("./deconnexion.php");


td_html_start('../styles/bookshop.css', 'Mon Compte');
td_social_banner(true, '../', './');

$bd = td_bd_connect();
$error = array();

//------------------------------------------------------Arrivée par soumission du formulaire de changement d'infos livraison---
if(isset($_POST['bounton_envoi_compte_infos'])){
	td_post_parameters('bounton_envoi_compte_infos','Modifier', array('adresse', 'cp', 'pays', 'ville'), 5);
	td_treatment(array(),$bd, $_POST['adresse'], $_POST['ville'], $_POST['cp'], $_POST['pays'], null, null);
}
else if(isset($_POST['bounton_modif_mail'])){ // -------Arrivée par soumission du formulaire de modification d'email -----------
	td_post_parameters('bounton_modif_mail','Modifier', array('email'), 2);
	$protected_mail = $_POST['email'];
	$error_mail = td_verify_email($bd, $protected_mail);
	if($error_mail)
		$error = array('error' => $error_mail);

	td_treatment($error, $bd, null, null, null, null, $protected_mail, null);
}
else if(isset($_POST['bounton_modif_passwd'])){ // -------Arrivée par soumission du formulaire de modification du mdp ----------
	td_post_parameters('bounton_modif_passwd','Modifier', array('passwd', 'repeatPasswd'), 4);

	$error_passwd =  td_verify_passwd($_POST['passwd'], $_POST['repeatPasswd']);
	if($error_passwd)
		$error = array('error' => $error_passwd);

	td_treatment($error, $bd, null, null, null, null, null, $_POST['passwd']);
}
else{ // ------------------------------------------------Arrivée sans soumission d'un formulaire --------------------------------
	td_account_content(null, $bd);
	
}
mysqli_close($bd);
$current_year = getdate();
td_footer($current_year['year']);
td_html_end();
ob_end_flush();



/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage du contenu de la page
 *
 * @param 	$error Message d'affichage si existe, $bd la base de donnée a utilisé
 * @param 			$bd 	 		Connexion à la BDD
 *
 * @return 	void
 */
function td_account_content($error, $bd){

	td_add_result($error);

	$sql = "SELECT cliEmail, cliNomPrenom, cliAdresse, cliVille, cliCP, cliPays
			FROM clients
			WHERE cliID =".(int)$_SESSION['idUser'];

		$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
		$tableau = mysqli_fetch_assoc($res);

	echo // Tableau des Informations personelles
		 '<div class="sub_box">',
			'<div class="entete"> Vos informations personnelles ? </div>',
				'<form class="email_box" method="post" action="compte.php">',
					'<table>',	
						 td_form_ligne('Email', td_form_input(TD_Z_TEXT, 'email', $tableau['cliEmail']),  td_button(TD_Z_SUBMIT, "Modifier", "bounton_modif_mail")), 
						
					'</table>',
				'</form>',
				'<form method="post" action="compte.php">',
					'<table>',
						td_form_ligne('nouveau mot de passe', td_form_input(TD_Z_PASSWORD, 'passwd',''), ' '),
						td_form_ligne('retapez le mot de passe', td_form_input(TD_Z_PASSWORD, 'repeatPasswd',''), td_button(TD_Z_SUBMIT, "Modifier", "bounton_modif_passwd")),
				 		td_form_ligne('Nom Prenom', td_form_input(TD_Z_TEXT, 'Nom', $tableau['cliNomPrenom'], 'readonly'), ' '),
					'</table>',
				'</form>',
		'</div>';

	
	echo  //Tableau des informations de livraison
	 '<div class="sub_box">',
		 '<div class="entete">Adresse de livraison</div>',
			'<form method="post" action="compte.php">',
				'<table>',
					td_form_ligne('Adresse', td_form_input(TD_Z_TEXT, 'adresse', $tableau['cliAdresse'])),
					td_form_ligne('ville',  td_form_input(TD_Z_TEXT, 'ville', $tableau['cliVille'])),
					td_form_ligne('CodePostal',  td_form_input(TD_Z_TEXT, 'cp', $tableau['cliCP'])),
					td_form_ligne('Pays',  td_form_input(TD_Z_TEXT, 'pays', $tableau['cliPays'])),
					'<tr><td colspan="2" >', td_button(TD_Z_SUBMIT, "Modifier", "bounton_envoi_compte_infos", "form_btn"),'</td></tr>',
				'</table>',
			'</form>',
		'</div>';

		mysqli_free_result($res);

	echo 
	'<div class="sub_box">',
		 '<div class="entete">Historique des commandes</div>',
			'<p>	Retrouvez les informations de vos commande dans la rubrique <a href="mes_commandes.php">mes commandes</a></p>',
	'</div>';
}

/**
 * affiche les erreurs si des erreurs ont été relevées ou applique les modification en BDD
 *
 * @param 	Array	$error 			Message d'affichage si existe, $bd la base de donnée a utilisé
 * @param 			$bd 	 		Connexion à la BDD
 * @param 	String	$adress 	 	Adresse de l'utilisateur
 * @param 	String	$adress 	 	Adresse de l'utilisateur
 * @param 	String	$ville 	 		Ville de l'utilisateur
 * @param 	Integer	$cp 	 		Code postal de l'utilisateur
 * @param 	String	$email 	 		email de l'utilisateur
 * @param 	String	$passwd 	 	nouveau mot de passe de l'utilisateur
 *
 * @return 	void
 */
function td_treatment($error, $bd, $adresse, $ville, $cp, $pays, $email, $passwd){

	//Réaffichage de la page avec les erreurs survenues
	if(!empty($error)){
		td_account_content($error, $bd);
	}
	else{

		$error = td_valide_modify($bd, $adresse, $ville, $cp, $pays, $email, $passwd);
		td_account_content($error, $bd);
	}
}

/**
 * création des requêtes sql pour les modifications
 *
 * @param 			$bd 	 		Connexion à la BDD
 * @param 	String	$adress 	 	Adresse de l'utilisateur
 * @param 	String	$adress 	 	Adresse de l'utilisateur
 * @param 	String	$ville 	 		Ville de l'utilisateur
 * @param 	Integer	$cp 	 		Code postal de l'utilisateur
 * @param 	String	$email 	 		email de l'utilisateur
 * @param 	String	$passwd 	 	nouveau mot de passe de l'utilisateur
 *
 * @return 	void
 */
function td_valide_modify($bd, $adresse, $ville, $cp, $pays, $email, $passwd){

	if($adresse === "" || $ville === "" || $cp === "" || $pays === ""){
		return array('error' => 'Tous les champs Adresse doivent être renseignés ! ');
	}

	// ---------------------Création d'une requête spécifique à la demande de changement
	$sql = "UPDATE clients
			SET "; 
	if($adresse != null && $ville != null && $cp != null && $pays != null){
		$sql .= "cliAdresse = \"".td_bd_protect($bd, $adresse)."\", cliVille=\"".td_bd_protect($bd, $ville)."\", cliPays=\"".td_bd_protect($bd, $pays)."\", cliCP=".intval($cp);
	} 
	else if($email != null){
		$sql .= "cliEmail = \"".td_bd_protect($bd, $email)."\"";
	}
	else if($passwd != null){
		$sql .= "cliPassword=\"".md5(td_bd_protect($bd, $passwd))."\"";
	}
	$sql .= " WHERE cliID=".td_bd_protect($bd, $_SESSION['idUser']);		
	

	mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	return array('success' => 'Modification réalisé avec succès !');
}

?>