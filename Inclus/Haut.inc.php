<?php

ini_set("include_path", __DIR__.':'.str_replace('/Inclus','',__DIR__).':'.str_replace('/Inclus','/vendor',__DIR__).':' . ini_get("include_path"));

require('config.php');

setlocale(LC_ALL,'fr_FR.UTF-8','fr.UTF-8');
date_default_timezone_set('Europe/Paris');

// Chargement des fonctions
require('Fonctions.inc.php');

// Démarer les sessions
session_set_cookie_params(0, '/', DOMAIN, true, true);
session_start ();
$Ancien_Id_Session = session_id();
if (count($_GET) <= 1 and count($_POST) == 0 and !isset($_GET['Clé']) ) {
	session_regenerate_id(true);
}

// Connexion à la base de donnée MYSQL
$dsn = 'mysql:host='.$SQL['host'].';dbname='.$SQL['dbname'].';charset=utf8';
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    PDO::ATTR_PERSISTENT => true
);
$dbh = new PDO($dsn, $SQL['username'], $SQL['password'], $options);
$dbh->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8', lc_time_names = 'fr_FR'");

// Traiter les inputs
foreach($_REQUEST as $Clé => $Valeur) {
	$_REQUEST[$Clé] = traiter_inputs($Valeur);
}

if (!isset($_REQUEST['Vue'])) $_REQUEST['Vue'] = '';
if (!isset($_REQUEST['Action'])) $_REQUEST['Action'] = '';

// Connexion et déconnexion
switch ($_REQUEST['Action']) {
	case 'Se connecter':
		/*if ($_REQUEST['g-recaptcha-response'] != '') {
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, ['secret' => '6Lep74gUAAAAAM0ICotoFIasWZ_FSAQzfLlgGUQq', 'response' => $_POST['g-recaptcha-response']]);
			$JSON = json_decode(curl_exec($ch));
			curl_close ($ch);
			if ($JSON->success) {*/
				// Vérification de l'existance des cet utilisateur
				$query =  "SELECT COUNT(*) FROM `Utilisateurs` WHERE `Identifiant` = ?";
				$result = $dbh->prepare($query);
				$result->execute(array($_REQUEST['Identifiant']));
				if ($result->fetchColumn() > 0) {
					$query =  "SELECT * FROM `Utilisateurs` WHERE `Identifiant` = ?";
					$result = $dbh->prepare($query);
					$result->execute(array($_REQUEST['Identifiant']));
					$Utilisateur = $result->fetchObject();
					if (password_verify($_REQUEST['Mot_de_passe'], $Utilisateur->Mot_de_passe)) {
						$_SESSION['Utilisateur_Con'] = $Utilisateur->Id;
					}
				}
		/*	}
		}*/
		break;
	case 'Se déconnecter':
		unset($_SESSION['Utilisateur_Con'],$_SESSION['Mur']);
		break;
}

// Récupération des informations sur l'utilisateur
if (isset($_SESSION['Utilisateur_Con'])) {
	$query =  "SELECT COUNT(*) FROM `Utilisateurs` WHERE `Id` = ".$_SESSION['Utilisateur_Con'];
	if ($dbh->query($query)->fetchColumn() > 0) {
		$query =  "SELECT * FROM `Utilisateurs` WHERE `Id` = ".$_SESSION['Utilisateur_Con'];
		$result = $dbh->query($query);
		$Utilisateur_Con = $result->fetchObject();
	} else {
		// Cet utilisateur n'existe pas donc suppresssion de la variable session
		unset($_SESSION['Utilisateur_Con'],$_SESSION['Mur']);
	}
} else {
	$Utilisateur_Con = new stdClass();
	$Utilisateur_Con->Id = null;
}

$Mur = initialiser_Mur();
$Tournoi = initialiser_Tournoi();

//Page de connexion si non connecté
if ($Utilisateur_Con->Id == null and $_SERVER['PHP_SELF'] != '/index.php' and $_SERVER['PHP_SELF'] != '/CAS.php' and $_SERVER['PHP_SELF'] != '/Tournois/Resultats.php' and $_SERVER['PHP_SELF'] != '/Tournois/Fiche individuelle.php') {
    header('Location: '.URL);
    exit();
}

if ($_REQUEST['Action'] == 'insérer' and $_SESSION['Old_Action'] != 'créer') {
	unset($_REQUEST['Action']);
}
if ($_REQUEST['Action'] == 'éditer' and $_SESSION['Old_Action'] != 'modifier') {
	unset($_REQUEST['Action']);
}
if ($_REQUEST['Action'] == 'retirer' and $_SESSION['Old_Action'] != 'supprimer') {
	unset($_REQUEST['Action']);
}
$_SESSION['Old_Action'] = $_REQUEST['Action'];

?>