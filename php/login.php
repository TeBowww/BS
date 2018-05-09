<?php

ob_start('ob_gzhandler');
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

//Verifie si la personne est deja authentifiée
td_verify_unloged(isset($_SESSION['idUser']));
($_GET && $_POST) && td_redirection("./deconnexion.php");

// Mise en forme de la Page générique-----
td_html_start('../styles/bookshop.css', 'Login');
td_social_banner(false, '../', './');

// ----------------------Le formulaire a déja été soumis-------------------------
if(isset($_POST['bounton_envoi_login'])){
	td_post_parameters('bounton_envoi_login', 'Se connecter', array('email', 'passwd'), 4);

	//------------------ ---- Affichage  ---------------------
	$cliID = td_verify_login_data();
	if(!$cliID){
		td_login_content(array('error' => "Identifiant / mdp non valide")); // réimpression du contenu avec message d'erreurs
	}
	else{
		$_SESSION['idUser'] = $cliID;  // Mémorisation de la connexion et redirection vers la page précédente
		if(isset($_POST['page_pre'])) 
			td_redirection($_POST['page_pre']);
		else
			td_redirection("../index.php");
	}
}
// ----------------- Arrivée sur la page depuis une autre page bookshop
else{
	td_login_content(false);
}


$current_year = getdate();
td_footer($current_year['year']);

td_html_end();
ob_end_flush();



/*################################################################################################
										Fonctions
################################################################################################*/

/**
 * Affichage du contenu de la page login
 *
 * @param 	boolean $error		erreur éventuelle à afficher su l'utilisateur n'est pas reconnu (mdp ou/et email faux)
 * @return 	String 	$chaine 	Code HTML généré
 */
function td_login_content($error){
	echo '<h1>Connexion à Bookshoop</h1>';
	
	td_add_result($error);

	echo  '<div class="box">'; 
	
	td_login_box();

	td_sign_in_box();

	echo '</div>';
}

/**
 * Création et Affichage du formulaire de connexion
 *
 * @return 	String 	$chaine 	Code HTML généré
 */
function td_login_box(){
	echo 
		 '<div class="sub_box">',
			 '<div class="entete"> Deja inscrit ? </div>',
				'<form method="post" action="login.php">';

		//definition de la page à afficher à l'issus de la connexion
		if(isset($_POST['page_pre'])) 
			echo td_button(TD_Z_HIDDEN, 'source', $_POST['page_pre']);
		else if(isset($_SERVER['HTTP_REFERER']))
			echo td_button(TD_Z_HIDDEN, 'source', $_SERVER['HTTP_REFERER']);

		echo	'<table>',
					td_form_ligne("email", td_form_input(TD_Z_TEXT, "email", "")),
					td_form_ligne("mot de passe", td_form_input(TD_Z_PASSWORD, "passwd", "")),
				'</table>',
					td_button(TD_Z_SUBMIT, "Se connecter", "bounton_envoi_login", "form_btn"),
				'</form>',
		 '</div>';
}

/**
 * affichage d'une zone pour demande d'inscription
 *
 * @return 	String 	$chaine 	Code HTML généré
 */
function td_sign_in_box(){
	echo 
		 '<div class="sub_box">',
			 '<div class="entete"> Pas encore inscrit ? </div>',
			 '<p>L\'inscription est gratuite et ne prends que quelques secondes</p>',
				'<form method="post" action="inscription.php">';

				//definition de la page à afficher à l'issus de l'inscription
				if(isset($_POST['page_pre'])) 
					echo '<input type="hidden" name="page_pre" value="',td_entities_protect($_POST['page_pre']),'">';
				else if(isset($_SERVER['HTTP_REFERER']))
					echo '<input type="hidden" name="page_pre" value="',$_SERVER['HTTP_REFERER'],'">';

				echo
					 td_button(TD_Z_SUBMIT, "Inscription", "bouton_envoi_inscription",  "form_btn"),
				'</form>',
		 '</div>';
}

/**
 * Verification en base de donnée de la concordance entre email et mot de passe 
 *
 * @return 	String 	$ID 	Id de l'utilisateur connecté si la connexion réussie/ false sinon
 */
function td_verify_login_data(){
	$bd = td_bd_connect();
	$mail =  td_bd_protect($bd, $_POST['email']);
	$mdp =  td_bd_protect($bd, $_POST['passwd']);
	$cliID = false;

	// Création et envoie de la recherche -----
	
	$sql = "SELECT cliID
			FROM clients
			WHERE cliEmail =\"".$mail."\"
			AND cliPassword=\"".md5($mdp)."\"";
		
		$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

		//Geretation dynamique du code HTML--------

		if( $tableau = mysqli_fetch_assoc($res)){
			$cliID = td_entities_protect($tableau['cliID']);
		}

		// Liberation des ressources ----------------
		mysqli_free_result($res);
		mysqli_close($bd);
		return $cliID;
}

?>