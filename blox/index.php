<?php

require('Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Blox';

if ($Utilisateur_Con->Id == null) {
    require('Inclus/Entête.inc.php');
    echo '<div style="max-width: 30rem; margin:auto;"><h2>Connexion</h2>';
    echo '<form method="post" id="SeConnecter" action="/">';
    echo '<h4>Pseudo</h4>';
    echo '<input type="hidden" name="Action" value="Se connecter">';
    echo '<p><input type="text" name="Identifiant" autocomplete="username" autocapitalize="none" autocorrect="off" required></p>';
    echo '<h4>Mot de passe</h4>';
    echo '<p><input type="password" name="Mot_de_passe" autocomplete="current-password" required></p>';
    echo '<p><input type="checkbox" name="Maintenir_connection" id="Maintenir_connection"><label for="Maintenir_connection">Rester connecté</label></p>';
    if ($_REQUEST['Action'] == 'Se connecter' and $Utilisateur_Con->Id == null) {
    	echo '<p style="color: #ff0000;">Le mot de passe saisi ne correspond pas ou cette adresse électronique nous est inconnue.</p>';
    }
	if (isset($_REQUEST['message']) and $_REQUEST['message'] != '') {
		echo '<p style="color: #ff0000;">'.$_REQUEST['message'].'</p>';
	}
    echo '<input type="submit" value="Se connecter">';
	//echo '<button class="g-recaptcha" data-sitekey="6Lep74gUAAAAAFcP6EfyDHm5mjWXX2OPjsFMVUeK" data-callback="onSubmit">Se connecter</button>';
    echo '</form>';
	echo '<form method="post" action="CAS">';
    echo '<input type="submit" value="Se connecter via frankiz ou le LDAP">';
    echo '</form>';
	/*
    echo '<form method="post" id="MPOublié" action="Administration">';
    echo '<input type="hidden" name="Action" value="MPOublié">';
    echo '</form>';
    echo '<form method="post" id="CréerCompte" action="Administration">';
    echo '<input type="hidden" name="Action" value="CréerCompte">';
    echo '</form>';
    echo '<p><a href="#" onclick="document.getElementById(\'MPOublié\').submit();return false;">Mot de passe oublié&nbsp;?</a><br><a href="#" onclick="document.getElementById(\'CréerCompte\').submit();return false;">Créer un compte</a></p>';*/
    echo '</div>';
	echo '<p style="text-align: center;"><a href="Tournois/Resultats">Résultat du tournoi en cours</a></p>';
    echo '<p id="noCookie" style="color:red; display:none;">Vous avez complètement désactivé les cookies. Ce site ne peut donc pas fonctionner (le cookie déposé sur votre téléphone le temps de votre session sert à vous identifier sur chaque une des pages auxquelles vous accédez). Merci de réactiver l’utilisation des cookies sur votre navigateur.</p>';
    echo "<script>if (!navigator.cookieEnabled) {document.getElementById('noCookie').style.display = 'block';}</script>";
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
	echo '<script>function onSubmit(token) {document.getElementById(\'SeConnecter\').submit();}</script>';
    
} else {
    echo 'im connected';
}

echo 'im there';

require('Inclus/Pied de page.inc.php');
require('Inclus/Bas.inc.php');

?>