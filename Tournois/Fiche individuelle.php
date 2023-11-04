<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Evaluation - ' . $Tournoi->Nom;
require('Entête.inc.php');

echo '<form method="POST">';
echo '<h2>Fiche individuelle -';
echo '<select name="Tournoi" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
$query = "SELECT `Tournois`.`Id`, `Tournois`.`Nom` FROM `Tournois` ORDER BY `Tournois`.`Date` DESC";
$result = $dbh->query($query);
while ($tournoi = $result->fetchObject()) {
	echo '<option value="'.$tournoi->Id.'"';
	if ($Tournoi->Id == $tournoi->Id) {
		echo ' selected';
	}
	echo '>'.$tournoi->Nom.'</option>';
}
echo '</select></h2>';
echo '</form>';

if ($_REQUEST['Grimpeur'] != '') {
    $query = "SELECT `Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Utilisateur` = :Grimpeur AND (`Type` = 'Grimpeur')";
    $result = $dbh->prepare($query);
    $result->execute(['Grimpeur' => $_REQUEST['Grimpeur']]);
    if ($result->rowCount() > 0) {
        $Grimpeur = $result->fetchObject();
    	/*echo '<form method="POST">';
    	echo '<h2>Mes résultats -';
    	echo '<select name="Tournoi" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
    	$query = "SELECT DISTINCT `Id`, `Nom` FROM (SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Tournois` ON `Tournois`.`Id` = `Tournois_Utilisateurs`.`Tournoi` WHERE `Tournois_Utilisateurs`.`Utilisateur` = " . $Utilisateur_Con->Id . " AND (`Tournois_Utilisateurs`.`Type` = 'Grimpeur') UNION SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois` WHERE `Tournois`.`Id` = " . $Tournoi->Id . ") AS `Select` ORDER BY `Date` DESC";
    	$result = $dbh->query($query);
    	while ($tournoi = $result->fetchObject()) {
    		echo '<option value="' . $tournoi->Id . '"';
    		if ($Tournoi->Id == $tournoi->Id) {
    			echo ' selected';
    		}
    		echo '>' . $tournoi->Nom . '</option>';
    	}
    	echo '</select></h2>';
    	echo '</form>';
    	*/
    	// Page d'accueil de la fiche individuelle du grimpeur sur le tournoi
    	if ($_REQUEST['Voie'] == '') {
    		$Nombre_de_murs = $dbh->query("SELECT COUNT(DISTINCT `Emplacements`.`Mur`) AS `NombreMur` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Tournois_Voies`.`Tournoi`= " . $Tournoi->Id)->fetchObject()->NombreMur;
    		echo '<div class="table"><table><thead><tr>';
    		if ($Nombre_de_murs > 1) {
    			echo '<th>Mur</th>';
    		}
    		echo '<th>Voie</th><th>Type</th><th>Essais</th>';
    		echo '</tr></thead><tbody>';
    		$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Voies`.`Cotation`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2`, `Emplacements`.`Nom` AS `Emplacement`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi ORDER BY `Voies`.`Emplacement`");
    		$result->execute(array('Tournoi' => $Tournoi->Id));
    		while ($Voie = $result->fetchObject()) {
    			echo '<tr>';
    			if ($Nombre_de_murs > 1) {
    				echo '<td>' . $Voie->Mur . '</td>';
    			}
    			echo '<td style="';
    			if ($Voie->Couleur_2 != null) {
    				echo 'background-image: linear-gradient(to bottom right, #' . $Voie->Couleur_1 . ' 25%, #' . $Voie->Couleur_2 . ' 75%);';
    			} else {
    				echo 'background-color: #' . $Voie->Couleur_1 . ';';
    			}
    			echo ' color: ' . couleur_text('#' . $Voie->Couleur_1) . '">' . $Voie->Cotation . ' sur l’emplacement ' . $Voie->Emplacement . '</td>';
    			echo '<td>' . $Voie->Type . '</td>';
    			echo '<td>';
    			$query = "SELECT * FROM `Essais` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " AND `Utilisateur` = " . $Grimpeur->Id . " ORDER BY `Date`";
    			$result1 = $dbh->query($query);
    			while ($Essai = $result1->fetchObject()) {
    				echo date_create($Essai->Date)->format('H\hi') . '&nbsp;: essai ' . ($Essai->Réussite == null ? 'réussi' : ' non réussi'.($Voie->Evaluation != 'Top' ? ' ('.$Voie->Evaluation.' '.$Essai->Réussite.')' : ''));
    				if ($Essai->Chrono != null) {
    					echo ' (' . date_create($Essai->Chrono)->format('i:s.v') . ')';
    				}
    				if ($Essai->Zones != null) {
    					$query = "SELECT `Nom` FROM `Tournois_Voies_Zones` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " AND `Id` = " . $Essai->Zones;
    					$result2 = $dbh->query($query);
    					if ($result2->rowCount() > 0) {
    					    echo ' - ' . $result2->fetchObject()->Nom;
    					}
    				}
    				echo '<br>';
    			}
    			echo '</td>';
    			echo '</tr>';
    		}
    		echo '</tbody></table></div>';
    	}
    	// plus de détail sil clique sur une voie
    	else if ($_REQUEST['Voie'] != '') {
    		$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi AND `Voies`.`Id` = :Voie");
    		$result->execute(array('Tournoi' => $Tournoi->Id, 'Voie' => $_REQUEST['Voie']));
    		if ($result->rowCount() > 0) {
    			$Voie = $result->fetchObject();
    			$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur AND `Type` = 'Grimpeur'";
    			$result = $dbh->prepare($query);
    			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Grimpeur->Id]);
    			if ($result->rowCount() > 0) {
    				$Grimpeur = $result->fetchObject();
    				echo '<h3>' . $Voie->Mur . ' - ' . afficheVoieFromId($Voie->Id) . '</h3>';
    				echo '<p>';
    				echo 'Type&nbsp;: ' . $Voie->Type;
    				if ($Voie->Evaluation != null) {
    					$query = "SELECT COUNT(*) AS `Nombre` FROM `Tournois_Voies_Zones` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id;
    					$result = $dbh->query($query);
    					$Nombre = $result->fetchObject()->Nombre;
    					if ($Voie->Evaluation == 'Zone') {
    						echo '<br>Zones intermédiaires&nbsp;: ' . $Nombre . ' zones';
    					} elseif ($Voie->Evaluation == 'Prise') {
    						echo '<br>Prises comptabilisées&nbsp;: ' . $Nombre . ' prises à valider';
    					}
    				}
    				echo '</p>';
    				echo '<h3>';
    				if ($Grimpeur->Dossard != '') {
    					echo $Grimpeur->Dossard . ' - ';
    				}
    				echo $Grimpeur->Nom . '</h3>';
    				echo '<p>' . $Grimpeur->Genre;
    				if ($Grimpeur->Equipe != null) {
    					echo ' (' . $Grimpeur->Equipe . ')';
    				}
    				echo '</p>';
    				echo '<p>';
    				$query = "SELECT COUNT(`Chrono`) AS `Evalué`, COUNT(*)-COUNT(`Chrono`) AS `Libre` FROM `Essais` WHERE `Utilisateur` = " . $Grimpeur->Id . " AND `Voie` = " . $Voie->Id . " AND `Tournoi` = " . $Tournoi->Id;
    				$result = $dbh->query($query);
    				$Count = $result->fetchObject();
    				echo 'Nombre d’essais libres&nbsp;: ' . $Count->Libre . '/' . ($Voie->Nb_Essais_Libres != null ? $Voie->Nb_Essais_Libres : 'illimité');
    				echo '<br>Nombre d’essais évalués&nbsp;: ' . $Count->Evalué . '/' . ($Voie->Nb_Essais_Evalués != null ? $Voie->Nb_Essais_Evalués : 'illimité');
    				echo '</p>';
    			}
    		}
    	}
    } else {
        echo '<p>Ce grimpeur n\'existe pas !</p>';
    }
} else {
    echo '<p>Vous n\'avez pas saisie de grimpeur !</p>';
}

require('Pied de page.inc.php');
require('Bas.inc.php');
