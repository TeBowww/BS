<?php

/*################################################################################################
										Bibliotheque Générale HTML
################################################################################################*/
/**
 * Initialise une page web
 *
 * @param 	String 	$css_path 	Chemin du fichier css
 * @param 	String 	$name_page	Nom de la page
 * @return 	void
 */
function td_html_start($css_path, $name_page){
	echo
		'<!DOCTYPE html>',
		'<html lang="fr">',
			'<head>',
				'<meta charset="UTF-8">',
                '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                if($css_path == true){
				    echo '<link href="', $css_path, '" rel="stylesheet" type="text/css"> ';
                }
            echo
				'<title>', $name_page, '</title>',
			'</head>', 
			'<body>',
			'<div id="bcPage">';
}

//____________________________________________________________________________
/**
 * Initialise une page web
 *
 * @return  void
 */
function td_html_end() {
	echo
			'</div>',
			'</body>',
		'</html>';
}

/*################################################################################
								Gestion Base de donnée
##################################################################################*/
/** 
 *	Ouverture de la connexion à la base de données
 *
 *	@return objet 	connecteur à la base de données
 */
function td_bd_connect() {
    $conn = mysqli_connect(BS_SERVER, BS_USER, BS_PASS, BS_DB);
    if ($conn !== FALSE) {
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8') 
        or td_bd_erreurExit('<h4>Erreur lors du chargement du jeu de caractères utf8</h4>');
        return $conn;     // ===> Sortie connexion OK
    }
    // Erreur de connexion
    // Collecte des informations facilitant le debugage
    $msg = '<h4>Erreur de connexion base MySQL</h4>'
            .'<div style="margin: 20px auto; width: 350px;">'
            .'BD_SERVER : '. BS_SERVER
            .'<br>BS_USER : '. BS_USER
            .'<br>BS_PASS : '. BS_PASS
            .'<br>BS_DB : '. BS_DB
            .'<p>Erreur MySQL numéro : '.mysqli_connect_errno()
            .'<br>'.htmlentities(mysqli_connect_error(), ENT_QUOTES, 'ISO-8859-1')  
            //appel de htmlentities() pour que les éventuels accents s'affiche correctement
            .'</div>';
    td_bd_erreurExit($msg);
}

//____________________________________________________________________________
/**
 * Arrêt du script si erreur base de données 
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 * 		- lors de la phase de connexion au serveur MySQL
 *		- ou indirectement lorsque l'envoi d'une requête échoue
 *
 * @param string	$msg	Message d'erreur à afficher
 */
function td_bd_erreurExit($msg) {
    ob_end_clean();	// Supression de tout ce qui a pu être déja généré
    ob_start('ob_gzhandler');

    //envoie d'un email au moderateur si une erreur BDD apparait - nécéssite un hébergement
/*
    $mail = 'thibault.delavoux@edu.univ-fcomte.fr'; // Déclaration de l'adresse de destination.
    if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $mail)) // On filtre les serveurs qui rencontrent des bogues.
    {
        $passage_ligne = "\r\n";
    }
    else
    {
        $passage_ligne = "\n";
    }

    $boundary = "-----=".md5(rand());

    $sujet = "Error Bookshop BDD";


    $msg_html =    "<!DOCTYPE html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><title> Erreur base de données</title> <style>table{border-collapse: collapse;}td{border: 1px solid black;padding: 4px 10px;}</style>
                </head><body>". $msg."</body></html>";

    //header de l'email
    $header = "From: \"WeaponsB\"<weaponsb@mail.fr>".$passage_ligne;
    $header.= "Reply-to: \"WeaponsB\" <weaponsb@mail.fr>".$passage_ligne;
    $header.= "MIME-Version: 1.0".$passage_ligne;
    $header.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;

    //=====Création du message.
    $message = $passage_ligne."--".$boundary.$passage_ligne;
    //==========
    $message.= $passage_ligne."--".$boundary.$passage_ligne;
    //=====Ajout du message au format HTML
    $message.= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
    $message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
    $message.= $passage_ligne.$msg_html.$passage_ligne;
    //==========
    $message.= $passage_ligne."--".$boundary."--".$passage_ligne;
    $message.= $passage_ligne."--".$boundary."--".$passage_ligne;

    mail($mail,$sujet,$message,$header);
*/
    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>',
            'Erreur base de données</title>',
            '<style>table{border-collapse: collapse;}td{border: 1px solid black;padding: 4px 10px;}</style>',
            '</head><body>',
            $msg,
            '</body></html>';

    exit(1);
}

//____________________________________________________________________________
/**
 * Gestion d'une erreur de requête à la base de données.
 *
 * A appeler impérativement quand un appel de mysqli_query() échoue 
 * Appelle la fonction xx_bd_erreurExit() qui affiche un message d'erreur puis termine le script
 *
 * @param objet		$bd		Connecteur sur la bd ouverte
 * @param string	$sql	requête SQL provoquant l'erreur
 */
function td_bd_erreur($bd, $sql) {
    $errNum = mysqli_errno($bd);
    $errTxt = mysqli_error($bd);

    // Collecte des informations facilitant le debugage
    $msg =  '<h4>Erreur de requête</h4>'
            ."<pre><b>Erreur mysql :</b> $errNum"
            ."<br> $errTxt"
            ."<br><br><b>Requête :</b><br> $sql"
            .'<br><br><b>Pile des appels de fonction</b></pre>';

    // Récupération de la pile des appels de fonction
    $msg .= '<table>'
            .'<tr><td>Fonction</td><td>Appelée ligne</td>'
            .'<td>Fichier</td></tr>';

    $appels = debug_backtrace();
    for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
        $msg .= '<tr style="text-align: center;"><td>'
                .$appels[$i]['function'].'</td><td>'
                .$appels[$i]['line'].'</td><td>'
                .$appels[$i]['file'].'</td></tr>';
    }

    $msg .= '</table>';

    td_bd_erreurExit($msg);	// => ARRET DU SCRIPT
}

/*################################################################################
                  Fonctions de traitement des données entrée/sorties
##################################################################################*/

/** 
 *  Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *      - de saisies de l'utilisateur (formulaires)
 *      - de la bdD
 *  Permet de se protéger contre les attaques XSS (Cross site scripting)
 *  Convertit tous les caractères éligibles en entités HTML, notamment :
 *      - les caractères ayant une signification spéciales en HTML (<, >, ...)
 *      - les caractères accentués
 *
 *  @param  string  $text   la chaine à protéger    
 *  @return string  la chaîne protégée
 */
function td_entities_protect($str) {
    $str = trim($str);
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

//____________________________________________________________________________
/**
 * Protection des chaînes avant insertion dans une requête SQL
 *
 * Avant insertion dans une requête SQL, toutes les chaines contenant certains caractères spéciaux (", ', ...) 
 * doivent être protégées. En particulier, toutes les chaînes provenant de saisies de l'utilisateur doivent l'être. 
 * Echappe les caractères spéciaux d'une chaîne (en particulier les guillemets) 
 * Permet de se protéger contre les attaques de type injections SQL
 *
 * @param   objet       $bd     La connexion à la base de données
 * @param   string      $str    La chaîne à protéger
 * @return  string              La chaîne protégée
 */
function td_bd_protect($bd, $str) {
    $str = trim($str);
    return mysqli_real_escape_string($bd, $str);
}

//____________________________________________________________________________
/**
 * Redirection de l'utilisateur vers une page donnée
 *
 * @param   String  $destination    Adresse de destination
 *
 * @return  void
 */
function td_redirection($destination){
    header('location: '.$destination);
    exit();
}

//____________________________________________________________________________
/**
 * Vérification du bon nombre de parramètre passé via un formulaire - 
 * 
 * @param   Integer $n          nombre de paramètres attendu
 * @param   String  $position   chemin depuis la position acctuelle pour atteindre la page déconnexion
 * @param   array   $type       tableau à tester : $_GET ou $_POST
 *
 * @return   boolean $res        Conformité ou non du nombre de paramètres réel et attendu 
 */
function td_verify_form_parameters_number($n, $position, $type){
    count($type) != $n && td_redirection("{$position}deconnexion.php");
    return true;
}

//____________________________________________________________________________
/**
 * Vérification anti-hacking via la méthode POST (vérification des élément du tableau $_POST)
 * 
 * @param   String  $name       Nom du bouton d'envoie du formulaire à tester
 * @param   String  $button     Valeur du bouton d'envoie du formulaire à tester    
 * @param   Array   $tab_args   Tableau des arguments attendu dans le tableau $_POST       
 * @param   Integer $nb_args    Nombre d'arguments maximal attendu dans le tableau $_POST
 *
 * @return  void 
 */
function td_post_parameters($name, $button, $tab_args, $nb_args){
    $_POST[$name] != $button && td_redirection("deconnexion.php");

    foreach($tab_args as $arg){
        !isset($_POST[$arg]) && td_redirection("deconnexion.php");
    }

    count($_POST) > $nb_args && td_redirection("deconnexion.php");
}

//____________________________________________________________________________
/**
 * Identifie si la méthode d'appel de la page est GET et si les paramètres sont corrects
 * 
 * @return  boolean présence ou non de $_GET
 */
function td_verify_get_presence(){

    if(count($_GET) > 0){
        return true;
    }
    return false;
}

/**
 * Verification d'un nombre suffisant et de la bonne valeur attendue de paramètres pour la recherche en $_GET-> utilisé pour recherche en cliquant sur un nom d'auteur
 * Autrement, redirection vers l'index 
 *
 * @param   array   $get_possible_value    Tableau des clefs possible en $_GET
 * @param   int     $n                     Nombre minimum d'informations par méthode GET
 * @param   String  $path_to_index         Chemin pour rejoindre la page déconnexion
 *
 * @return  boolean                        Confirmation de la conformité des informations
 */
function td_verify_get_instance($get_possible_value, $n, $path_to_index){

    count($_GET) < $n && td_redirection("{$path_to_index}deconnexion.php"); 

    foreach($_GET as $key => $value){
        array_search($key, $get_possible_value) === false && td_redirection("{$path_to_index}deconnexion.php");
    }

    return true;

}

/**
 *  Contrôle de la validité des informations reçues via la query string et renvoie une version protégée de la valeur
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @param  $attribut     l'attribut à tester
 *
 * @return $valueQ       le livre a rechercher          
 */
function td_control_get ($attribut){

    !isset($attribut) && td_redirection("./deconnexion.php");

    $valueQ = td_entities_protect($attribut);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && td_redirection("./deconnexion.php");
    
    return $valueQ;
}

/*################################################################################
                  Fonctions relative à l'espace Utilisateur
##################################################################################*/


//____________________________________________________________________________
/**
 * Vérifie qu'un utilisateur est bien connecté pour acceder à une page, dans le cas contraire on le renvoie vers l'index
 * 
 * @param   Boolean  $id    resultat de l'isset de l'id d'un utilisateur dans le tableau $_SESSION 
 *
 * @return  void 
 */
function td_verify_loged($id){
    !$id && td_redirection("./deconnexion.php");
}

//____________________________________________________________________________
/**
 * Vérifie qu'un utilisateur n'est pas connecté pour acceder à une page, dans le cas contraire on le renvoie vers l'index
 * 
 * @param   Boolean  $id    resultat de l'isset de l'id d'un utilisateur dans le tableau $_SESSION 
 *
 * @return  void 
 */
function td_verify_unloged($id){
    $id && td_redirection("../index.php");
}


?>