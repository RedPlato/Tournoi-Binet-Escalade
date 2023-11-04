<?php
echo '<!DOCTYPE html>';
echo '<html lang="fr">';

// Entête
echo '<head>';
echo '<meta charset="utf-8">';
echo '<title>Escalade Polytechnique - ' . $Page_Ouv . '</title>';
echo '<meta name="description" content="Site de gestion des murs d\'escalade de l\'Ecole Polytechnique">';
echo '<meta name="keywords" content="Section escalade, Ecole Polytechnique, Polytechnique, X, ENSTA, Mur, Escalade">';
echo '<meta name="author" content="Grégoire Grzeczkowicz et Matthieu Laurent X2017">';
echo '<base href="' . URL . '/">';
echo '<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0">';

// Icones
echo '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">';
echo '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">';
echo '<link rel="icon" type="image/png" sizes="192x192" href="/android-chrome-192x192.png">';
echo '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">';
echo '<link rel="manifest" href="/site.webmanifest">';
echo '<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#4FC3F7">';
echo '<meta name="apple-mobile-web-app-title" content="Escalade">';
echo '<meta name="application-name" content="Escalade">';
echo '<meta name="msapplication-TileColor" content="#2d89ef">';
echo '<meta name="msapplication-TileImage" content="/mstile-150x150.png">';
echo '<meta name="theme-color" content="#4FC3F7">';
echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">';
echo '<link href="https://fonts.googleapis.com/css?family=Great+Vibes" rel="stylesheet">';
echo '<link rel="stylesheet" href="principal.css" />';

echo '</head>';

// Corps
echo '<body>';

echo '<header id="Entête">';
echo '<h1><a href="">Escalade</a></h1>';
// Menu
echo '<nav onclick="">';
echo '<ul>';

if ($Utilisateur_Con->Id != null) {
    //echo '<li class="ouvert">';  
    if (droits('V', $Mur->Id)) {
        echo '<li><a href="Utilisateur">Suivi des grimpeurs</a></li>';
        echo '<li><a href="Essais">Saisir un essai</a></li>';
        echo '<li>';
        echo '<a href="Voies/Gestion">Voies</a>';
        echo '<div><ul>';
    	echo '<li><a href="Voies/Gestion">Gestion des voies</a></li>';
    	echo '<li><a href="Voies/Suggestions">Suggestions</a></li>';
    	echo '<li><a href="Voies/Récentes">Voies récentes</a></li>';
		if (droits('A', $Mur->Id)) {
			echo '<li><a href="Voies/Emplacements">Gestion des emplacements</a></li>';
		}
    	echo '</ul></div>';
        echo '</li>';
        echo '<li><a href="Administration">Gestion des utilisateurs</a></li>';
        
    } else {
        echo '<li><a href="Essais">Saisir un essai</a></li>';
        echo '<li><a href="Utilisateur">Suivi grimpette</a></li>';
        echo '<li>';
        echo '<a href="Voies/Suggestions">Voies</a>';
        echo '<div><ul>';
    	echo '<li><a href="Voies/Suggestions">Suggestions</a></li>';
    	echo '<li><a href="Voies/Récentes">Voies récentes</a></li>';
    	echo '<li><a href="Voies/Gestion">Statitistiques des voies</a></li>';
    	echo '</ul></div>';
        echo '</li>';
        echo '<li><a href="Administration">Mon compte</a></li>';
    }
	$query = "SELECT COUNT(*) AS `Membre`, SUM(`Type` = 'Administrateur') AS `Administrateur`, SUM(`Type` = 'Juge' OR `Type` = 'Administrateur') AS `Juge` FROM `Tournois_Utilisateurs` WHERE `Utilisateur` = ".$Utilisateur_Con->Id;
	$result = $dbh->query($query);
	$Nb_tournoi = $result->fetchObject();
	if ($Nb_tournoi->Membre > 0) {
		if ($Nb_tournoi->Administrateur == 0 and $Nb_tournoi->Juge == 0) {
			echo '<li><a href="Tournois/Resultats">Tournois</a>';
			echo '<div><ul>';
			echo '<li><a href="Tournois/Resultats">Résultats</a></li>';
			echo '<li><a href="Tournois/Evaluation">Evaluation</a></li>';
			echo '</ul></div>';
			echo '</li>';
		} else {
			echo '<li>';
			if ($Nb_tournoi->Juge > 0) {
				echo '<a href="Tournois/Evaluation">Tournois</a>';
			} else {
				echo '<a href="Tournois/Resultats">Tournois</a>';
			}
			echo '<div><ul>';
			echo '<li><a href="Tournois/Resultats">Résultats</a></li>';
			if ($Nb_tournoi->Juge > 0) {
				echo '<li><a href="Tournois/Evaluation">Evaluation</a></li>';
			}
			if ($Nb_tournoi->Administrateur > 0) {
				echo '<li><a href="Tournois/Administration">Administration</a></li>';
			}
			echo '</ul></div>';
			echo '</li>';
		}
	}
	echo '<li style="font-style:italic;"><a href="?Action=Se déconnecter">Se déconnecter</a></li>';
}


echo '</ul>';
echo '</nav>';
echo '</header>';

// Contenu
echo '<section id="Contenu">';

?>