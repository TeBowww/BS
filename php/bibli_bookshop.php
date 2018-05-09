<?php


define ('BS_SERVER', 'localhost'); // nom d'hôte ou adresse IP du serveur MySQL
define('BS_DB', 'bookshop_db'); // nom de la base sur le serveur MySQL
define('BS_USER', 'bookshop_user'); // nom de l'utilisateur de la base
define('BS_PASS', 'bookshop_pass'); // mot de passe de l'utilisateur de la base


/*define ('BS_SERVER', 'localhost'); // nom d'hôte ou adresse IP du serveur MySQL
define('BS_DB', 'bookshop_delavoux'); // nom de la base sur le serveur MySQL
define('BS_USER', 'u_delavoux'); // nom de l'utilisateur de la base
define('BS_PASS', 'p_delavoux'); // mot de passe de l'utilisateur de la base*/


//---------------------------------------------------------------
// Définition des types de zones de saisies
//---------------------------------------------------------------
define('TD_Z_TEXT', 'text');
define('TD_Z_PASSWORD', 'password');
define('TD_Z_SUBMIT', 'submit');
define('TD_Z_HIDDEN', 'hidden');


/*################################################################################################
										FONCTIONS
################################################################################################*/


// ------------ Fonctions d'affichage générales à toutes les pages  ------------------- //

//____________________________________________________________________________
/**
 * Affichage de la banière réseaux sociaux
 *
 * @param boolean $connected   défini si l'utilisateur est connecté à un compte
 * @param String position      défini le chemin pour revenir à la racine
 * @param String php_pos      défini le chemin pour aller dans le repertoire php
 *
 * @return 	void
 */
function td_social_banner($connected, $position, $php_pos){
	echo 
		'<aside>',
			'<a href="http://www.facebook.com" target="_blank"></a>',
			'<a href="http://www.twitter.com" target="_blank"></a>',
			'<a href="http://plus.google.com" target="_blank"></a>',
			'<a href="http://www.pinterest.com" target="_blank"></a>',
		'</aside>';

		td_header($connected, $position, $php_pos);
}

//____________________________________________________________________________
/**
 * Affichage de l'entête et du menu du site
 *
 * @param boolean $connected   défini si l'utilisateur est connecté à un compte
 * @param String position      défini le chemin pour revenir à la racine
 * @param String php_pos      défini le chemin pour aller dans le repertoire php
 *
 * @return 	void
 */
function td_header($connected, $position, $php_pos){
	echo		
		'<header>',
			'<nav>',
				'<a href="', $position, 'index.php"></a>',
				'<a class="lienMenu" href="',$php_pos, 'recherche.php" title="Effectuer une recherche"></a>',
				'<a class="lienMenu" href="',$php_pos, 'panier.php" title="Voir votre panier"></a>',
				'<a class="lienMenu" href="',$php_pos, 'liste.php" title="Voir une liste de cadeaux"></a>';
				if($connected == true){
					echo '<a class="lienMenu" href="',$php_pos, 'compte.php" title="Consulter votre compte"></a>',
						'<a class="lienMenu" href="',$php_pos, 'deconnexion.php" title="Se déconnecter"></a>';
				}
				else{
					echo '<a class="lienMenu" href="',$php_pos, 'login.php" title="Se connecter"></a>';
				}
			echo	
			'</nav>',
			'<img src="',$position,'images/soustitre.png" alt="sous titre">',
		'</header>';
}

//____________________________________________________________________________
/**
 * Pied de page du site BookShop
 *
 * @param 	String 	$year 	Année en cours
 *
 * @return 	void
 */
function td_footer($year){
	echo 
		'<footer>',
			'BookShop &amp; Partners &copy; 2018 -' ,
			'<a href="apropos.html">A propos</a> -' ,
			'<a href="confident.html">Emplois @ BookShop</a> -', 
			'<a href="conditions.html">Conditions d\'utilisation</a>',
		'</footer>';
}

/*################################################################################
            Fonctions de traitement des données et affichage des livres 
##################################################################################*/

//____________________________________________________________________________
/**
 * Traitement de l'affichage des livres pour l'index
 *
 * @param 	sql 	$res 		Données de requête sql
 * @param 	String 	$position 	Position dans l'arboresence du site
 * @param 	Boolean $sider 		Défini si l'ajout en whishlist est poissible pour les livres
 *
 * @return 	void
 */
function td_data_traitement_vertical($res, $position, $sider){

	$last_id = -1;
	$limit = 0;
	$buffer=array();
	$author = array();
	while($tableau = mysqli_fetch_assoc($res)){

		//On mémorise l'auteur
		if($last_id == $tableau['liID'] || $last_id === -1){
			$author[]= array('prenom' => $tableau['auPrenom'],'nom' => $tableau['auNom']);
		}
		
		//on merge les deux tableaux en un
		if($last_id != $tableau['liID'] && $last_id != -1){
			$show_whish = $buffer[3] === null ? true : false;
			td_print_books_vertical($buffer, $author, $sider, $show_whish);
			$author = array();
			$buffer = array();
			$limit +=1;
			if($limit === 4)
				break;
		}

		$to_wish = isset($_SESSION['idUser']) ? $tableau['listIDLivre'] : null;

		//recuperation des information sur le livre
		$buffer=array("{$position}images/livres/{$tableau['liID']}_mini.jpg", $tableau['liTitre'], $tableau['liID'], $to_wish);


		if(empty($author)){
			$author[]= array('prenom' => $tableau['auPrenom'],'nom' => $tableau['auNom']);
		}
		$last_id = $tableau['liID'];
	}
}

//____________________________________________________________________________
/**
 * Affichage vertical des livres
 *
 * @param array 	$books    		tableau contenant les information d'un livre
 * @param array 	$auth     		tableau d'auteurs
 * @param Boolean 	$show_sider   	Défini si l'on affiche les siders
 * @param Boolean $show_whish 	Défini si l'ajout en whishlist est poissible pour les livres
 *
 * @return 	void
 */
function td_print_books_vertical($books, $auth, $show_sider, $show_whish){
	global $connected;
	echo
		'<div class="bcArticle bcSell">';
		if($show_sider === true)
			echo td_siders($books[2], $show_whish);

			echo
			'<a href="php/details.php?article=', $books[2], '" title="',$books[1],'">',
				'<img class="index" src="', $books[0], '" alt="', $books[1],'">',
			'</a><br>';
			
			 td_print_author($auth, 'php/');
		echo
			'<br>',
			'<strong>', $books[1], '</strong> '	,		
		'</div>';
}

//____________________________________________________________________________
/**
 * Traitement de l'affichage des livres générique
 *
 * @param 	sql 	$res 			Données de requête sql
 * @param 	Boolean $sider 			Défini si l'ajout en whishlist est poissible pour les livres
 * @param 	Int 	$pagination 	Nombre de livres à afficher
 * @param 	int 	$nb 			Nombre de livres affichés
 *
 * @return 	void
 */
function td_data_traitement($res, $sider, $pagination, $nb){
		$last_id= -1;
		$buffer=array();
		$author=array();
		while($tableau = mysqli_fetch_assoc($res)){

			if($last_id == $tableau['liID']){
				$author[]= array($tableau['auPrenom'], $tableau['auNom']);
			}
				
			if($last_id != $tableau['liID'] && $last_id != -1){
				$show_whish = $buffer['whish'] === null ? true : false;
				td_print_book($buffer, $author, $sider, $show_whish);
				$author = array();
				$buffer = array();
				if($pagination != null){
					$nb ++;
					if ($nb >= $pagination) {
						break;
					}
				}
			}
			$to_wish = isset($_SESSION['idUser']) && $sider === true ? $tableau['listIDLivre'] : null;
			$buffer=array('liID' => $tableau['liID'], 'liTitre' => $tableau['liTitre'], 'liPrix' => $tableau['liPrix'],
						'liISBN13' => $tableau['liISBN13'], 'liResume' => $tableau['liResume'], 'liPages' => $tableau['liPages'],
						'edNom' => $tableau['edNom'], 'edWeb' => $tableau['edWeb'], 'auNom' => $tableau['auNom'], 'whish' => $to_wish);


			if(empty($author)){
				$author[]= array($tableau['auPrenom'], $tableau['auNom']);
			}
			$last_id = $tableau['liID'];
		}

		if($last_id === -1){
				echo '<p>Aucun livre ne correspond à votre recherche</p>';
		}
		else if($pagination === null || $nb < $pagination){
			$show_whish = $buffer['whish'] === null ? true : false;
			td_print_book($buffer, $author, $sider, $show_whish);
		}
}

//____________________________________________________________________________
/**
 * Affichage des livre
 *
 * @param array 	$books     		tableau des livres
 * @param array 	$author    		tableau des autheurs
 * @param Boolean 	$show_sider   	Défini si l'on affiche les siders
 * @param Boolean 	$show_whish 	Défini si l'ajout en whishlist est poissible pour les livres
 *
 * @return 	void
 */
function td_print_book($book, $author, $show_sider, $show_whish){
	global $quoi;
	echo 
		'<div class="book_search bcSell book_print">',
		'<div class="sider_shop">';
		if($show_sider === true){
			echo td_siders($book['liID'], $show_whish);
		}
		else{
			$adds = '';
			$adds .= isset($_GET['t']) ? '&t='.$_GET['t'] : '';
			$adds .= isset($_GET['p']) ? '&p='.$_GET['p'] : '';
			echo '<a class="addToCart" href="?cart=', $book['liID'], $adds, '" title="Ajouter au panier"></a>';
			if(isset($_SESSION['idUser']) && $_SESSION['idUser'] === $quoi)
				echo '<a class="deleteFromList" href="?del=', $book['liID'], $adds, '" title="Supprimer"></a>';
		}
		echo
		'</div>',
		'<a href="details.php?article=', $book['liID'], '"><img class ="index" src="../images/livres/',$book['liID'],'_mini.jpg" alt="',td_entities_protect($book['liTitre']),'"></a>',
		'<strong>', td_entities_protect($book['liTitre']),'</strong><br>',
		'Ecrit par : ';
		$last_key = end($author);
		foreach($author as $aut){
			echo  '<a title="Rechercher l\'auteur ', $aut[1],'" href="./recherche.php?type=auteur&quoi=', urlencode($aut[1]),'">', $aut[0], ' ', $aut[1], '</a>';
			if($aut !=  $last_key)
				echo ', ';
		}
	echo
		'<br>',
		'Edité Par : <a href ="http://',td_entities_protect($book['edWeb']),'">', td_entities_protect($book['edNom']),'</a><br>',
		'Prix : ', $book['liPrix'] ,'€<br>',
		'Pages : ', $book['liPages'], '<br>',
		'ISBN13 : ', td_entities_protect($book['liISBN13']) , '<br>',
		'</div>';
}

//____________________________________________________________________________
/**
 * Affichage le nom des auteurs et crée un lien vers une recherche avec leur nom
 *
 * @param array 	$author    		tableau des autheurs
 * @param String 	$position      	défini le chemin pour atteindre la page recherche
 *
 * @return 	void
 */
function td_print_author($auth, $position){
	$last_key = end($auth);
	foreach($auth as $author){
				$dot = $author == $last_key ?  '' : ',';
				echo '<a title="Rechercher l\'auteur ', $author['nom'],'"  href="',$position,'recherche.php?type=auteur&quoi=', urlencode($author['nom']),'">',$author['prenom'], ' ', $author['nom'], $dot, '  </a>';	
			}
}

//____________________________________________________________________________
/**
 * Affiche les liens de mise au panier et de mise en whishlist
 *
 * @param Int		$liID    		Numero d'id du livre
 * @param Boolean 	$show_whish 	Défini si l'ajout en whishlist est poissible pour les livres
 *
 * @return 	void
 */
function td_siders($liID, $show_whish){
	global $connected;
	$adds = '';
	$adds .= isset($_GET['quoi']) ? '&quoi='.$_GET['quoi'] : '';
	$adds .= isset($_GET['type']) ? '&type='.$_GET['type'] : '';
	$adds .= isset($_GET['t']) ? '&t='.$_GET['t'] : '';
	$adds .= isset($_GET['p']) ? '&p='.$_GET['p'] : '';

	if(isset($_POST['quoi'])){
		$adds = '&quoi='.$_POST['quoi'];
		$adds .= isset($_POST['type']) ? '&type='.$_POST['type'] : '';
	}

	echo '<a class="addToCart" href="?cart=', $liID, $adds, '" title="Ajouter au panier"></a>';
	if($connected === true && $show_whish)
		echo '<a class="addToWishlist" href="?whish=',$liID, $adds,'" title="Ajouter à la liste de cadeaux"></a>';
}

//____________________________________________________________________________
/**
 * Vérification de la présence des paramètres de recherche
 * Autrement, redirection vers l'index
 *
 * @param 	String 	$quoi 		(sous)chaine de charactère a rechercher
 * @param 	String 	$type		Type de recherche (auteur ou titre de livre)
 * @param 	String 	$position   défini le chemin pour atteindre la page déconnexion
 *
 * @return 	void
 */
function td_verify_parameters($quoi, $type, $position){
	if($type == false || $quoi == false){
		td_redirection("{$position}deconnexion.php");
	}
	return true;
}

//____________________________________________________________________________
/**
 * Affichage des livres pour le pannier et la page commandes
 *
 * @param 	array 	$livre 		Tableau avec les informations du livre à afficher 
 *											- id : noero d'id du livre
 *											- quantite : quantite de livre
 *											- titre : titre du livre
 *											- prix : prix du livre seul
 *											- total: prix total (Quantite *prix)
 * @param 	boolean 	$supp	défini si le livre peut être supprimé (utilisé pour le pannier mais pas pour le recap des commandes)
 *
 *
 * @return 	void
 */
function td_print_cart_book($livre, $supp){
	$to_supp = $supp === true ? "<a href=\"?del={$livre['id']}\"  title=\"delete\">Supprimer</a>" : '';
	echo '<div class ="book_print">';
			if($supp){
				echo td_sider_cart($livre['id']);
			}
			echo
			'<img class="index" src="../images/livres/',$livre['id'],'_mini.jpg" alt="',td_entities_protect($livre['titre']),'">',
			'<p>', td_entities_protect($livre['titre']), '<br> Quantite : ', td_entities_protect($livre['quantite']), '<br> Prix/u : ', td_entities_protect($livre['prix']),  '€<br><br> Sous-total : ',
			td_entities_protect($livre['total']), '€</p>',
			$to_supp,
		'</div>';
}

//____________________________________________________________________________
/**
 * Affiche les liens de gestion de la quantité d'un article au panier
 *
 * @param Int	$liID    numero d'id du livre
 *
 * @return 	void
 */
function td_sider_cart($liID){

	echo '<a class="upQuantite" href="?upcart=', $liID, '" title="Ajouter une unité"></a>';
	echo '<a class="downQuantite" href="?downcart=',$liID,'" title="Enlever une unité"></a>';
}


/*################################################################################
                  Fonctions de gestion du panier et de la whishlist
##################################################################################*/

//____________________________________________________________________________
/**
 * Ajout d'un element a la whishlist
 *
 * @param Int	$whishID    numero d'id du livre
 * @param 		$bd 		Acces a la BDD
 *
 * @return array 			Message de réussite de l'ajout
 */
function td_add_to_wish($whishID, $bd){

	if(td_verify_book_existence($whishID, $bd)){
		return td_book_not_found();
	}

	if(td_verify_whish_presence($whishID, $bd))
		return array('error' => "L'élément est déja présent dans votre liste !");

	$sql = "INSERT INTO listes (listIDLivre, listIDClient)
			VALUES (".td_bd_protect($bd, $whishID).", ".td_bd_protect($bd, $_SESSION['idUser']).")";

	mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

	return array('success' => "L'élément a bien été ajouté à votre liste !");
}

//____________________________________________________________________________
/**
 * Ajout d'un element au Panier
 *
 * @param Int	$liID     numero d'id du livre
 * @param 		$bd 	  Acces a la BDD
 *
 * @return array 		 Message de réussite de l'ajout
 */
function td_add_to_cart($liID, $bd){

	if(td_verify_book_existence($liID, $bd)){
		return td_book_not_found();
	}

	$where = "WHERE liID = {$liID}";
	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	$tab = mysqli_fetch_assoc($res);

	td_ajouter_article($liID, 1, $tab['liTitre'], $tab['liPrix']);

	return array('success' => "L'élément a bien été ajouté à votre panier !");
}

//____________________________________________________________________________
/**
 * Vérification de l'existance d'un livre en BDD
 *
 * @param Int		$liID     numero d'id du livre
 * @param 			$bd 	  Acces a la BDD
 *
 * @return boolean  $tab      résultat de la recherche : présence ou non
 */
function td_verify_book_existence($liID, $bd){
	$id = td_bd_protect($bd, $liID);
	
	$where = "WHERE liID = '$id'";
	$sql = td_sql_book_create(isset($_SESSION['idUser']), $where);

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	$tab = mysqli_fetch_assoc($res);
	return $tab === null ?  true : false;
}

//____________________________________________________________________________
/**
 * génération d'un Message d'erreur renvoyé quand un livre n'existe pas
 *
 * @return array      Message d'erreur
 */
function td_book_not_found(){
	return array('error' => 'Nous ne sommes pas en mesure de trouver le livre demandé, il a peut être été supprimé ou n\'a jamais existé');
}

//____________________________________________________________________________
/**
 * Vérification de l'existance d'un livre dans la liste de shouait de l'utilisateur
 *
 * @param Int		$liID     numero d'id du livre
 * @param 			$bd 	  Acces a la BDD
 *
 * @return boolean  $presence      résultat de la recherche : présence ou non
 */
function td_verify_whish_presence($whishID, $bd){
	$sql = "SELECT liID
			FROM livres, listes, clients
			WHERE liID = listIDLivre
			AND listIDClient = cliID
			AND cliID=".$_SESSION['idUser'].
			" AND liID=".$whishID;

	$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);
	$presence = false;
	while(mysqli_fetch_assoc($res)){
		$presence = true;
	}
	return $presence;
}	

//____________________________________________________________________________
/**
 * Vérification de l'existance d'un livre dans la liste de shouait de l'utilisateur
 *
 * @param array		$error    Message d'erreur à renvoyer à l'utilisateur et type de message (succès ou échec)
 *
 * @return void
 */
function td_add_result($error){

	if($error){
		if(isset($error['error'])){
			echo '<div class="bloc_error">',
				$error['error'];
		}
		else{
			echo '<div class="bloc_success">',
				$error['success'];
		}

		echo	'</div>';
	}
}


/*################################################################################
                  Fonctions de panigation
##################################################################################*/

//____________________________________________________________________________
/**
 * Affichage par pagination
 *
 * @param 			$bd    		  Acces à la BDD
 * @param 	String	$qui    	  Element à chercher (provient de la barre de recherche)
 * @param 	String	$sql    	  Requête sql
 * @param 	String	$to_search    Element majeur de la recherche (liID pour recherche de livres)
 * @param 	String	$from   	  Page à paginer (recherche / list)
 * @param 	String	$type   	  Type de la recherche
 * @param 	Boolean	$siders   	  Défini les siders à afficher (whish et cart ou cart et delete)
 *
 * @return void
 */
function td_pagination_start($bd, $qui, $sql, $to_search, $from, $type, $siders){
	$pagination = 5;
	$total_result = array('books' => -1, 'lines' => 0);
	$position = -1;
	$nb = 0;

	if (isset($_GET['t']) && is_numeric($_GET['t'])) {
		$total_result['books'] = (int) $_GET['t'];
	}

	if (isset($_GET['p']) && is_numeric($_GET['p'])) {
		$position = (int) $_GET['p'];
	}

	if ($total_result['books'] < 0 || $position < 0) {
		$total_result['books'] = $position = 0;
	}

	if(strlen($qui) < 2){
		echo '<p>Erreur, la recherche doit contenir au moins deux charactères</p>';
	}
	else{
		
		$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

		$total_result = td_get_total_res($res, $to_search);		


		if(!estEntre($position, 0, $total_result['lines'] -1 )){
			$position = 0;
		}

		if($from === 'recherche'){
			echo '<h3>Livre(s) Correspondant à la recherche"', $qui, '"</h3>';

			$pages = td_prepare_pages($res, $total_result, $pagination,'liID');
			$res->data_seek($position);
			
			td_data_traitement($res, $siders, $pagination, $nb);

			td_print_pagination($pages, $position, $qui, $type, $total_result['books']);
		}
		else if($from === 'list'){
			
			$pages = td_prepare_pages($res, $total_result['books'], $pagination, 'liID');
			$res->data_seek($position);
			
			td_data_traitement($res, $siders, $pagination, $nb);

			td_print_pagination($pages, $position, $qui, null, $total_result['books'] );
		}
	
		// Liberation des ressources ----------------
			mysqli_free_result($res);
		
	}
}

//____________________________________________________________________________
/**
 * Retourn si la position acctuelle dans la pagination est entre la borne inférieur et supérieur
 *
 * @param  Int		$position   Position en cours dans la pagination
 * @param  Int		$begin   	borne inférieur
 * @param  Int		$pend   	borne supérieur
 *
 * @return boolean			défini si la position est bien entre $begin et $end ou non
 */
function estEntre($pos, $begin, $end){

	if($pos >= $begin && $pos <= $end)
		return true;
	return false;
}

//____________________________________________________________________________
/**
 * Calcul du nombre de résultats (livres) renvoyé par la requête à paginer 
 *
 * @param  String	$sql   resultat de la requête sql à parcourir
 * @param  String	$what  element pour regrouper les résultats (liID pour compter le nombre de livres)
 *
 * @return array			Nombre total de livres et de lignes
 */
function td_get_total_res($sql, $what){
	$total = 0;
	$total_lines = 0;
	$last_id = -1;
	while($tab = mysqli_fetch_assoc($sql)){
		if($last_id === -1 || $last_id != $tab[$what]){
			$total += 1;
		}
		$last_id =  $tab[$what];
		$total_lines++;
	}
	$sql->data_seek(0);
	return array('books' => $total, 'lines' => $total_lines);
}

//____________________________________________________________________________
/**
 * Préparation des liens vers les sections de la pagination
 *
 * @param  String	$sql 			resultat de la requête sql à parcourir
 * @param  Int		$total_livres  	nombre de livres retourné par la requête
 * @param  Int		$pagination  	nombre de livre à afficher par pages
 * @param  String	$from  			Page à paginer (recherche / list)
 *
 * @return array 	$pages		tableau contenant pour chaque secions
 * 													- num_page : numero de la page
 * 													- position : ligne de la requête a partir de laquelle imprimer les livres sur la page
 *
 */
function td_prepare_pages($sql, $total_livres, $pagination, $from){
	$prepare = false;
	$pages = array();
	$pages[] = array('position' => 0, 'num_page' => 1);
	$current = 0;
	$nb_books = 0;
	$num_page = 2;
	$last_id = -1;
	while($tab = mysqli_fetch_assoc($sql)){
	$res = $tab[$from];
		if($last_id === -1 || $last_id != $res){
			$nb_books++;
			if($nb_books%$pagination === 0 && $nb_books < $total_livres){
				$prepare = true;
			}
			else if($prepare === true && $last_id != $res){
				$prepare = false;
				$pages[] = array('position' => $current, 'num_page' => $num_page++);
			}
		}
		$last_id = $res;
		$current++;
	}
	return $pages;
}

//____________________________________________________________________________
/**
 * affichage de la pagination (numeros de pages linkant vers une section)
 *
 * @param array 	$pages			tableau contenant pour chaque secions
 * 													- num_page : numero de la page
 * 													- position : ligne de la requête a partir de laquelle imprimer les livres sur la page
 * @param  Int		$position  		position acctuelle dans la pagination
 * @param  Int		$quoi  			contenu de la recherche à renvoyer à la page
 * @param  String	$type  			type de la recherche (auteurs ou livres)
 * @param  Int		$total_livres  	nombre de livres retourné par la requête
 *
 * @param  void
 */
function td_print_pagination($pages, $position, $quoi, $type, $total_livres){
	$print_type = $type != null ? "&type=$type" : '';
	echo '<p class="pagination">Pages : ';
			foreach($pages as $p){
				if($p['position'] === $position)
					echo $p['num_page'], ' ';
				else{
					echo '<a href="', $_SERVER['PHP_SELF'],
					'?quoi=', $quoi, $print_type, '&t=', $total_livres, '&p=', $p['position'], '">', 
					$p['num_page'], ' </a> ';
				}
			}
		echo '</p>';
}

//____________________________________________________________________________
/**
 * retourne la date du jour au format YYYYMMDD
 *
 * @param  String 	Date du jour
 */
function td_get_current_date(){
	return intval(date("Ymd"));
}

/*################################################################################
                  Fonctions modelage de formulaires
##################################################################################*/


//____________________________________________________________________________
/**
 * création d'une ligne de formulaire
 *
 * @param   String  $gauche  element HTML à inserer dans la colone gauche de la ligne
 * @param   String  $droite  element HTML à inserer dans la colone droite de la ligne
 * @return  String  $chaine  code HTML de la ligne de tableau
 */
function td_form_ligne($gauche, $droite, $extra=''){
	$extra = ($extra === '') ? '' : "<td>{$extra}</td>";
	return "<tr><td class=\"right\">{$gauche} :</td><td class=\"left\">{$droite}</td>{$extra}</tr>";
}

//____________________________________________________________________________
/**
 * formation générique d'un element input pour un fomulaire
 *
 * @param   String  $type	type d'input a créer (checkbox, text, password ...)
 * @param   String  $name 	non à attribuer à l'input
 * @param   String  $value 	valeur à attribuer à l'input
 * @param   String  $taille	taille de l'élément (0 par défault)
 * @return  String  $chaine  code HTML de l'input généré
 */
function td_form_input($type, $name, $value, $modified='', $size=''){
	$value =  td_entities_protect($value);
    $size = ($size == 0) ? '' : "size='{$size}'";
    return "<input type='{$type}' name='{$name}' {$size} value='{$value}' $modified>";
}

//____________________________________________________________________________
/**
 * formation générique de select et leurs option destiné à la saisie d'une date
 *
 * @param   String  $nom 	Nom de 'élément'
 * @param   String  $jour 	Element jour a selectionner par défault
 * @param   String  $mois 	Element mois à selectionner par défault
 * @param   String  $annee 	Element année à secectionner par défault
 * @return  String  $chaine code HTML de l'élement généré
 */
function td_form_date($nom, $jour = 0, $mois = 0, $annee = 0){

	$months = array("-", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Aout", "Septembre", "Octobre", "Novembre", "Décembre");


	$res = "<select name='{$nom}_j'>";
	for($i = 1; $i < 32; $i++){
		if($jour === 0 && $i == date("j"))
			$res .= "<option selected value='$i'>$i</option>";
		else
			$res .= "<option value='$i'>$i</option>";
	}
	$res .= "</select>";

	$res .= "<select name='{$nom}_m'>";
	for($i = 1; $i < 13; $i++){
		if($jour === 0 && $i == date("m"))
			$res .= "<option selected value='$i'>$months[$i]</option>";
		else
			$res .= "<option value='$i'>$months[$i]</option>";
	}
	$res .= "</select>";

	$res .= "<select name='{$nom}_a'>";
	for($i = date("Y"); $i > 2018 - 100; $i--){
		if($jour === 0 && $i == date("Y"))
			$res .= "<option selected value='$i'>$i</option>";
		else
			$res .= "<option value='$i'>$i</option>";
	}
	$res .= "</select>";

	return $res;
}

//____________________________________________________________________________
/**
 * formation générique d'un element bouton pour l'envoi de formulaire
 *
 * @param   String  $value 	Texte a inserer dans le bouton (value)
 * @param   String  $type 	Type d'input
 * @param   String  $class 	Classe d'apatenance de l'élément HTML
 * @param   String  $name 	Nom de l'élément
 * @return  String  $chaine  code HTML de l'input généré
 */
function td_button($type, $value, $name, $class=''){
	return "<input class='btn $class' type='$type' name='$name' value='$value'>";
}

//____________________________________________________________________________
/**
 * Affichage des erreurs renvoyées par les formulaures
 *
 * @param array		$error    Message d'erreur à renvoyer à l'utilisateur
 *
 * @return void
 */
function td_error_formulaire($error){

	if(!empty($error)){
		echo '<div class="bloc_error">',
				'<p> l\'inscription n\'a pas pu être réalisée a cause des erreurs suivantes : <br>';
		foreach($error as $err){
			if($err != null)
				echo $err, '<br>';
		}
		echo 	'</p>',
			'</div>';
	}
}


/*################################################################################
                  Fonctions de vérifications avant mise en BD
##################################################################################*/

//____________________________________________________________________________
/**
 * Verification de la validité du champ Nom Prenom
 *
 * @param 	array 	$names		Nom et prénom de l'utilisateur
 *
 * @return 	String 	$chaine 	Erreur relevée
 */
function td_verify_name($names){
	$trim_nom = trim($names);
	$name_lenght = mb_strlen($trim_nom, 'utf-8');
	if($name_lenght > 100 || $name_lenght < 2)
		return "- Le champ Nom Prenom doit être comprit entre 2 et 30 characteres";
	if($trim_nom != strip_tags($trim_nom))
		return "- Le champ Nom Prenom ne doit pas contenir de balises html";
	if (! preg_match("/^[a-zA-Z][a-zA-Z\- ']{1,29}$/", $trim_nom))
		return "- Le champ Nom Prenom ne doit pas contenir de characteres speciaux";
}

//____________________________________________________________________________
/**
 * Verification de la validité du champ email
 *
 * @param 	array 	$bd			Connexion à la Base de donnée
 * @param 	array 	$email		email saisis par l'utilisateur
 *
 * @return 	String 	$chaine 	Erreur relevée
 */
function td_verify_email($bd, $email){
	$trim_mail =  trim($email);
	if(mb_strpos($trim_mail, "@", 0, 'utf-8') === false || mb_strpos($trim_mail, "." , 0, 'utf-8') === false)
		return "- L'email doit contenir au minima un '.' et un '@'";
	if(mb_strlen($trim_mail, 'utf-8') > 30)
		return "- L'email ne doit pas dépasser 30 characteres";
	if($trim_mail != strip_tags($trim_mail))
		return "- L'email ne doit pas contenir de balises html";
	if(td_verify_mail_exist($bd, $trim_mail))
		return "- L'email exite déja";	
}

//____________________________________________________________________________
/**
 * Verification de l'absence d'une entrée email correspondant dans la base de donnée
 * Pour l'inscription, l'email doit être unique et deux comptes ne peuvent pas partager la même adresse email
 *
 * @param 	array 	$bd			Connexion à la Base de donnée
 * @param 	array 	$email		email saisis par l'utilisateur
 * @return 	boolean $res 		Présence ou non d'un email correspondant
 *
 */
function td_verify_mail_exist($bd, $mail){
	
	$wanted =  td_bd_protect($bd, $mail);
	$exist = false;

	// Création et envoie de la recherche -----
	
	$sql = "SELECT cliEmail
			FROM clients
			WHERE cliEmail =\"".$wanted."\"";

						
		$res = mysqli_query($bd, $sql) or td_bd_erreur($bd, $sql);

		//Geretation dynamique du code HTML--------

		if($tableau = mysqli_fetch_assoc($res)){
			$exist = true;
		}

		// Liberation des ressources ----------------
		mysqli_free_result($res);
		return $exist;
}

//____________________________________________________________________________
/**
 * Verification de la validité des champs password
 *
 * @param 	array 	$pass			password choisis
 * @param 	array 	$pass_report	password retapé pour verification de concordence
 *
 * @return 	String 	$chaine 		Erreur relevée
 */
function td_verify_passwd($pass, $pass_repet){
	$trim_pass = trim($pass);
	$trim_pass_repet = trim($pass_repet);
	$pass_length = mb_strlen($trim_pass, 'utf-8');
	if(strcmp($trim_pass, $trim_pass_repet))
		return "- Les mots de passes ne correspondent pas";
	if($pass_length < 4 || $pass_length > 20)
		return "- Le mot de passe doit être comprit entre 4 et 20 characteres";
	if($trim_pass != strip_tags($trim_pass))
		return "- Le mot de passe ne doit pas contenir de balises html";
}


/*################################################################################
                  Fonctions de Gestion d'un Panier
##################################################################################*/

//____________________________________________________________________________
/**
 * Supression d'un pannieret de tout son contenu
 *
 * @return  void 
 */
function td_supprime_panier(){
   unset($_SESSION['panier']);
}

//____________________________________________________________________________
/**
 * Création d'un panier vide
 *
 * @return  void 
 */
function td_creation_panier(){
   if (!isset($_SESSION['panier'])){
      $_SESSION['panier'] = array();
      $_SESSION['panier']['liID'] = array();
      $_SESSION['panier']['QtLivre'] = array();
      $_SESSION['panier']['liTitre'] = array();
      $_SESSION['panier']['prix'] = array();
   }
   return true;
}

//____________________________________________________________________________
/**
 * Ajout d'un article au panier de l'utilisateur. Si celui ci n'existe pas, en crée un.
 * Si l'article est deja dans le panier, augmente la quantité
 *
 * @param Integer $liID 	ID du livre à ajouter
 * @param Integer $QtLivre  Quantité d'article a ajouter
 * @param String  $titre 	Titre du livre
 * @param Integer $prix 	Prix a l'unité du livre
 *
 * @return  void 
 */
function td_ajouter_article($liID ,$QtLivre, $titre, $prix){
   //Si le panier existe
   if(td_creation_panier())
   {
      //Si le produit existe déjà on ajoute seulement la quantité
      $positionProduit = array_search($liID, $_SESSION['panier']['liID']);

      if ($positionProduit !== false)
      {
         $_SESSION['panier']['QtLivre'][$positionProduit] += $QtLivre ;
      }
      else
      {
         //Sinon on ajoute le produit
         array_push( $_SESSION['panier']['liID'],$liID);
         array_push( $_SESSION['panier']['QtLivre'],$QtLivre);
         array_push( $_SESSION['panier']['liTitre'],$titre);
         array_push( $_SESSION['panier']['prix'],$prix);
      }
   }
   else
   		echo "Un problème est survenu veuillez contacter l'administrateur du site.";
}


/*################################################################################
                  Création de requêtes SQL
##################################################################################*/
/**
 * Création d'une requête sql générique
 *
 * @param boolean $connected	défini si l'utilisateur est connecté ou non
 * @param String  $where  		clause where spécifique aux besoin définie dans les pages concernées
 *
 * @return  String 	$sql 	Requête personnalisée 
 */
function td_sql_book_create($connected, $where){

	if(!$connected){
		$sql = "SELECT liID, liTitre, liPrix, liPages, liISBN13, liResume, auBio, liAnnee, liLangue, liCat, edNom, edWeb, auNom, auPrenom, auPays
				FROM livres INNER JOIN editeurs on edID = liIDediteur
				INNER JOIN aut_livre ON liID = al_IDLivre
				INNER JOIN auteurs ON al_IDAuteur = auID
				{$where}";
	}
	else{
			$sql = "SELECT liID, liTitre, liPrix, liPages, liISBN13, liResume, auBio, liAnnee, liLangue, liCat, edNom, edWeb, auNom, auPrenom, auPays, listIDLivre
					FROM livres INNER JOIN editeurs on edID = liIDediteur
					INNER JOIN aut_livre ON liID = al_IDLivre
					INNER JOIN auteurs ON al_IDAuteur = auID
					LEFT OUTER JOIN listes ON liID = listIDLivre AND listIDClient = '{$_SESSION['idUser']}'
					{$where}";
		}
		
	return $sql;
}

?>