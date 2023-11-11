<?php
//TODO : essayer d'y comprendre qqch et rendre les calculs paramètrable (donc connaitre les parametres) : bon courage ggrzeckowicz

require('../Inclus/Haut.inc.php');
session_write_close();
if (isset($_REQUEST['Actualiser']) and $_REQUEST['Actualiser'] != '') {
	header('Refresh: '.$_REQUEST['Actualiser'].';');
}
$Page_Ouv = 'Résultats - '.$Tournoi->Nom;
require('Entête.inc.php');

echo '<form method="POST">';
echo '<h2>Résultats -';
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
echo '<p class="noPrint"><a href="Tournois/Resultats?Actualiser=5'.(isset($_REQUEST['Type']) ? '&Type='.$_REQUEST['Type'] : '').'">Actualiser ces résultats toutes les 5 secondes</a></p>';

switch($Tournoi->Id) {
	case 1:
		$Equipe = [];
		echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; /*font-size:0.75em */">';
		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Difficulté'";
		$result = $dbh->query($query);
		$Score_Voie = [];
		while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Prise') {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`, SUM(`Nb_Points`) AS `Points` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Zones` ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Grimpeur->Points;
				}
			} else {
				if ($Voie->Evaluation == 'Zone') {
					$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
					$result1 = $dbh->query($query);
					while($Zone = $result1->fetchObject()) {
						//On vérifie que la zone est qualifiée
						$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND (CONCAT(',',`Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Zones` IS NULL)")->fetchObject()->Nombre;
						if ($Nombre > 0) {
							$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND CONCAT(',',`Zones`,',') LIKE '%,".$Zone->Id.",%'";
							if ($Voie->Nb_Essais_Evalués != null) {
								$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
							}
							$result2 = $dbh->query($query);
							$Points = round(1000/$result2->rowCount());
							while($Grimpeur = $result2->fetchObject()) {
								$Score_Voie[$Grimpeur->Id] += $Points;
							}
						}
					}
				}
				$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$result1 = $dbh->query($query);
				$Points = round(1000/$result1->rowCount());
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Points;
				}
			}
		}
		foreach ($Score_Voie as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Difficulté' AND `Essais`.`Chrono` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
		}
		array_multisort(array_column($Score_Voie, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Voie, 'Cotation'),SORT_ASC,SORT_STRING,array_column($Score_Voie, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Voie);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Difficulté</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Voie as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Voie[$i-1]['Points'] == $Infos['Points'] and $Score_Voie[$i-1]['Cotation'] == $Infos['Cotation'] and $Score_Voie[$i-1]['Secondes'] == $Infos['Secondes']) {
				$Code .= '<td>'.$Score_Voie[$i-1]['Rang'].'</td>';
				$Score_Voie[$i]['Rang'] = $Score_Voie[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Voie[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' en '.$Infos['Secondes'].'s)</td>';
			$Code .= '</tr>';
			$Equipe['Difficulté'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		arsort($Equipe['Difficulté']);
		$count = 1;
		foreach ($Equipe['Difficulté'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
		$result = $dbh->query($query);
		$Score_Vitesse = [];
		while($Voie = $result->fetchObject()) {
			$query = "SELECT `Essais`.`Utilisateur` AS `Id`, MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`, COUNT(*) AS `Nb_Essais` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Zones` ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$query .= " GROUP BY `Essais`.`Utilisateur`";
			$result1 = $dbh->query($query);
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Vitesse[$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
				$Score_Vitesse[$Grimpeur->Id]['Temps'] += $Grimpeur->Secondes;
				$Score_Vitesse[$Grimpeur->Id]['Nb_Essais'] += $Grimpeur->Nb_Essais;
			}
		}
		array_multisort(array_column($Score_Vitesse, 'Temps'),SORT_ASC,SORT_NUMERIC,array_column($Score_Vitesse, 'Nb_Essais'),SORT_ASC,SORT_NUMERIC ,$Score_Vitesse);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Vitesse</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Vitesse as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Vitesse[$i-1]['Temps'] == $Infos['Temps'] and $Score_Vitesse[$i-1]['Nb_Essais'] == $Infos['Nb_Essais']) {
				$Code .= '<td>'.$Score_Vitesse[$i-1]['Rang'].'</td>';
				$Score_Vitesse[$i]['Rang'] = $Score_Vitesse[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Vitesse[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Infos['Temps']))->format('i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
			$Code .= '</tr>';
			$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
		}
		asort($Equipe['Vitesse']);
		$count = 1;
		foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Secondes))->format('H:i:s.v').'</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Bloc'";
		$result = $dbh->query($query);
		$Score_Bloc = [];
		while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Zone') {
				$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
				$result1 = $dbh->query($query);
				while($Zone = $result1->fetchObject()) {
					//On vérifie que la zone est qualifiée
					$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND (CONCAT(',',`Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Zones` IS NULL)")->fetchObject()->Nombre;
					if ($Nombre > 0) {
						$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND CONCAT(',',`Zones`,',') LIKE '%,".$Zone->Id.",%'";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$result2 = $dbh->query($query);
						$Points = round(1000/$result2->rowCount());
						while($Grimpeur = $result2->fetchObject()) {
							$Score_Bloc[$Grimpeur->Id] += $Points;
						}
					}
				}
			}
			$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$result1 = $dbh->query($query);
			$Points = round(1000/$result1->rowCount());
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Bloc[$Grimpeur->Id] += $Points;
			}
		}
		foreach ($Score_Bloc as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Bloc' AND `Essais`.`Chrono` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
		}
		array_multisort(array_column($Score_Bloc, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Bloc, 'Cotation'),SORT_ASC,SORT_STRING,array_column($Score_Bloc, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Bloc);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Bloc as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Bloc[$i-1]['Points'] == $Infos['Points'] and $Score_Bloc[$i-1]['Cotation'] == $Infos['Cotation'] and $Score_Bloc[$i-1]['Secondes'] == $Infos['Secondes']) {
				$Code .= '<td>'.$Score_Bloc[$i-1]['Rang'].'</td>';
				$Score_Bloc[$i]['Rang'] = $Score_Bloc[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Bloc[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' en '.$Infos['Secondes'].'s)</td>';
			$Code .= '</tr>';
			$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		arsort($Equipe['Bloc']);
		$count = 1;
		foreach ($Equipe['Bloc'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$Total = [];
		$Classement_Voie = array_combine(array_column($Score_Voie, 'Grimpeur'), array_column($Score_Voie, 'Rang'));
		$Classement_Vitesse = array_combine(array_column($Score_Vitesse, 'Grimpeur'), array_column($Score_Vitesse, 'Rang'));
		$Classement_Bloc = array_combine(array_column($Score_Bloc, 'Grimpeur'), array_column($Score_Bloc, 'Rang'));
		foreach(array_unique(array_merge(array_column($Score_Voie, 'Grimpeur'),array_column($Score_Vitesse, 'Grimpeur'),array_column($Score_Bloc, 'Grimpeur'))) as $Utilisateur) {
			if (isset($Classement_Voie[$Utilisateur])) {
				$Total[$Utilisateur] += $Classement_Voie[$Utilisateur];
			} else {
				$Total[$Utilisateur] += count($Classement_Voie)+1;
			}
			if (isset($Classement_Vitesse[$Utilisateur])) {
				$Total[$Utilisateur] += $Classement_Vitesse[$Utilisateur];
			} else {
				$Total[$Utilisateur] += count($Classement_Vitesse)+1;
			}
			if (isset($Classement_Bloc[$Utilisateur])) {
				$Total[$Utilisateur] += $Classement_Bloc[$Utilisateur];
			} else {
				$Total[$Utilisateur] += count($Classement_Bloc)+1;
			}
		}
		asort($Total);
		$Total = [array_keys($Total),array_values($Total)];
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Général</th></tr></thead><tbody>';
		$count = 1;
		for ($i = 0; $i < count($Total[0]); $i++) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Total[0][$i]]);
			$Grimpeur = $result->fetchObject();
			echo '<tr>';
			if ($i > 0 and $Total[1][$i-1] == $Total[1][$i]) {
				echo '<td>'.$Total[2][$i-1].'</td>';
				$Total[2][$i1] = $Total[2][$i-1];
			} else {
				echo '<td>'.strval($i+1).'</td>';
				$Total[2][$i] = $i+1;
			}
			echo '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			echo '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				echo ' ('.$Grimpeur->Equipe.')';
			}
			echo '</td>';
			echo '<td>'.$Total[1][$i].'</td>';
			echo '</tr>';
			$count++;
		}
		echo '</tbody></table>';

		echo '</div>';

		break;
	case 2:
		$Equipe = [];
		echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';

		//Etablit par propagation si la voie est validée (deux supérieures)
		$Voie_validées=[];
		$int = 0;
		$query = "SELECT `Voies`.`Id` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Difficulté' ORDER BY `Voies`.`Cotation`";
		$result = $dbh->query($query);
		while ($Voie = $result->fetchObject()) {
			$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
			$result1 = $dbh->query($query);
			while ($Grimpeur = $result1->fetchObject()) {
				$query = "SELECT COUNT(*) AS `Réussi` FROM `Essais` WHERE `Réussite` IS NULL AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
				if ($dbh->query($query)->fetchObject()->Réussi > 0) {
					$Voie_validées[$int][$Grimpeur->Id] = true;
				} else {
					$Voie_validées[$int][$Grimpeur->Id] = false;
				}
			}
			$int++;
		}
		//If propagation
		if (true) {
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				$i = $int-2;
				$v = false;
				while ($i >= 0) {
					if ($Voie_validées[$i+1][$Grimpeur] and $Voie_validées[$i][$Grimpeur]) {
						$v = true;
					}
					if ($v) {
						$Voie_validées[$i][$Grimpeur] = true;
					}
					$i--;
				}
			}
		}
		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Difficulté'";
		$result = $dbh->query($query);
		$Score_Voie = [];
		/*while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Prise') {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`, SUM(`Nb_Points`) AS `Points` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Zones` ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Grimpeur->Points;
				}
			} else {
				if ($Voie->Evaluation == 'Zone') {
					$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
					$result1 = $dbh->query($query);
					while($Zone = $result1->fetchObject()) {
						//On vérifie que la zone est qualifiée
						$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Zones` IS NULL)")->fetchObject()->Nombre;
						if ($Nombre > 0) {
							$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Zones`,',') LIKE '%,".$Zone->Id.",%'";
							if ($Voie->Nb_Essais_Evalués != null) {
								$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
							}
							$result2 = $dbh->query($query);
							$Points = round(1000/$result2->rowCount());
							while($Grimpeur = $result2->fetchObject()) {
								$Score_Voie[$Grimpeur->Id] += $Points;
							}
						}
					}
				}
				$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$result1 = $dbh->query($query);
				$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0 ;
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Points;
				}
			}
		}*/
		foreach (array_keys($Voie_validées) as $Voie) {
			$Somme = array_sum($Voie_validées[$Voie]);
			$Points = $Somme > 0 ? round(1000/$Somme) : 0;
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				if ($Voie_validées[$Voie][$Grimpeur]) {
					$Score_Voie[$Grimpeur] += $Points;
				}
			}
		}
		foreach ($Score_Voie as $Id => &$Points) {
			$query="SELECT 
				`Voies`.`Cotation`,
				TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`,
				TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, 
				(SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) AS `Num_Essai`
			FROM `Essais`
			LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie`
			LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id."
			WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Difficulté' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Num_Essai`, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Num_Essai' =>$Classement->Num_Essai , 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
		}
		array_multisort(array_column($Score_Voie, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Voie, 'Cotation'),SORT_ASC,SORT_STRING,array_column($Score_Voie, 'Num_Essai'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Voie);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Difficulté</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Voie as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Voie[$i-1]['Points'] == $Infos['Points'] and $Score_Voie[$i-1]['Cotation'] == $Infos['Cotation'] and $Score_Voie[$i-1]['Num_Essai'] == $Infos['Num_Essai'] and $Score_Voie[$i-1]['Secondes'] == $Infos['Secondes']) {
				$Code .= '<td>'.$Score_Voie[$i-1]['Rang'].'</td>';
				$Score_Voie[$i]['Rang'] = $Score_Voie[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Voie[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' en '.$Infos['Secondes'].'s)</td>';
			$Code .= '</tr>';
			$Equipe['Difficulté'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		arsort($Equipe['Difficulté']);
		$count = 1;
		foreach ($Equipe['Difficulté'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		//Vitesse	
		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
		$result = $dbh->query($query);
		$Score_Vitesse = [];
		while($Voie = $result->fetchObject()) {
			$query = "SELECT `Essais`.`Utilisateur` AS `Id`, MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`, COUNT(*) AS `Nb_Essais` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Zones` ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$query .= " GROUP BY `Essais`.`Utilisateur`";
			$result1 = $dbh->query($query);
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Vitesse[$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
				$Score_Vitesse[$Grimpeur->Id]['Temps'] += $Grimpeur->Secondes;
				$Score_Vitesse[$Grimpeur->Id]['Nb_Essais'] += $Grimpeur->Nb_Essais;
			}
		}
		array_multisort(array_column($Score_Vitesse, 'Temps'),SORT_ASC,SORT_NUMERIC,array_column($Score_Vitesse, 'Nb_Essais'),SORT_ASC,SORT_NUMERIC ,$Score_Vitesse);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Vitesse</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Vitesse as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Vitesse[$i-1]['Temps'] == $Infos['Temps'] and $Score_Vitesse[$i-1]['Nb_Essais'] == $Infos['Nb_Essais']) {
				$Code .= '<td>'.$Score_Vitesse[$i-1]['Rang'].'</td>';
				$Score_Vitesse[$i]['Rang'] = $Score_Vitesse[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Vitesse[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Infos['Temps'] = str_replace(',','.',$Infos['Temps']);
			$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Infos['Temps']))->format('H:i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
			$Code .= '</tr>';
			$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
		}
		asort($Equipe['Vitesse']);
		$count = 1;
		/*foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Secondes))->format('H:i:s.v').'</td>';
			echo '</tr>';
			$count++;
		}*/
		echo $Code;
		echo '</tbody></table>';

		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Bloc'";
		$result = $dbh->query($query);
		$Score_Bloc = [];
		while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Zone') {
				$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
				$result1 = $dbh->query($query);
				while($Zone = $result1->fetchObject()) {
					//On vérifie que la zone est qualifiée
					$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Zones` IS NULL)")->fetchObject()->Nombre;
					if ($Nombre > 0) {
						$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Zones`,',') LIKE '%,".$Zone->Id.",%'";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$result2 = $dbh->query($query);
						$Points = round(1000/$result2->rowCount());
						while($Grimpeur = $result2->fetchObject()) {
							$Score_Bloc[$Grimpeur->Id] += $Points;
						}
					}
				}
			}
			$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$result1 = $dbh->query($query);
			$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0;
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Bloc[$Grimpeur->Id] += $Points;
			}
		}
		foreach ($Score_Bloc as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, (SELECT COUNT(*) FROM `Essais` AS `E` LEFT OUTER JOIN `Tournois_Voies` AS `T` ON `T`.`Voie` = `E`.`Voie` AND `T`.`Tournoi` = `E`.`Tournoi` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `T`.`Type` = 'Bloc') AS `Nb_Essais_Total` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Bloc' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure, 'Nb_Essais_Total' =>$Classement->Nb_Essais_Total];
		}
		array_multisort(array_column($Score_Bloc, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Bloc, 'Nb_Essais_Total'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Cotation'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Bloc);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Bloc as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Bloc[$i-1]['Points'] == $Infos['Points'] and $Score_Bloc[$i-1]['Nb_Essais_Total'] == $Infos['Nb_Essais_Total']) {
				$Code .= '<td>'.$Score_Bloc[$i-1]['Rang'].'</td>';
				$Score_Bloc[$i]['Rang'] = $Score_Bloc[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Bloc[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' avec '.$Infos['Nb_Essais_Total'].' essais)</td>';
			$Code .= '</tr>';
			$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		/*$Score_Bloc[0]['Rang'] = 2;
		$Score_Bloc[2]['Rang'] = 3;*/
		arsort($Equipe['Bloc']);
		$count = 1;
		foreach ($Equipe['Bloc'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$Total = [];
		$Classement_Voie = array_combine(array_column($Score_Voie, 'Grimpeur'), array_column($Score_Voie, 'Rang'));
		$Classement_Vitesse = array_combine(array_column($Score_Vitesse, 'Grimpeur'), array_column($Score_Vitesse, 'Rang'));
		$Classement_Bloc = array_combine(array_column($Score_Bloc, 'Grimpeur'), array_column($Score_Bloc, 'Rang'));
		foreach(array_unique(array_merge(array_column($Score_Voie, 'Grimpeur'),array_column($Score_Vitesse, 'Grimpeur'),array_column($Score_Bloc, 'Grimpeur'))) as $Utilisateur) {
			$Total[$Utilisateur] = 1;
			if (isset($Classement_Voie[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Voie[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Voie)+1;
			}
			if (isset($Classement_Vitesse[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Vitesse[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Vitesse)+1;
			}
			if (isset($Classement_Bloc[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Bloc[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Bloc)+1;
			}
		}
		asort($Total);
		$Total = [array_keys($Total),array_values($Total)];
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Général</th></tr></thead><tbody>';
		$count = 1;
		$Code = '';
		for ($i = 0; $i < count($Total[0]); $i++) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Total[0][$i]]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Total[1][$i-1] == $Total[1][$i]) {
				$Code .= '<td>'.$Total[2][$i-1].'</td>';
				$Total[2][$i] = $Total[2][$i-1];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Total[2][$i] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Total[1][$i].'</td>';
			$Code .= '</tr>';
			$Equipe['Général'][$Grimpeur->Equipe][1] += 1;
			if ($Equipe['Général'][$Grimpeur->Equipe][0] == null) {
				$Equipe['Général'][$Grimpeur->Equipe][0] = 1;
			}
			if ($Equipe['Général'][$Grimpeur->Equipe][1] <= 3) {
				$Equipe['Général'][$Grimpeur->Equipe][0] *= $Total[2][$i];
			}
			$count++;
		}
		array_multisort(array_column($Equipe['Général'], '0'),SORT_ASC,SORT_NUMERIC,$Equipe['Général']);
		$count = 1;
		foreach ($Equipe['Général'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points[0].' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		echo '</div>';
		break;
	case 3:
		echo '<p>Il n’y a aucun résultats pour ce tournoi.</p>';
		break;
	case 4:
		$Equipe = [];
		echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';

		//Difficulté
		if (!isset($_REQUEST['Type']) or $_REQUEST['Type'] == 'Difficulté') {
			//Etablit par propagation si la voie est validée (voie inférieure validée)
			$Score_Voie=[];
			$query = "SELECT `Voies`.`Id`, `Voies`.`Cotation`, `Tournois_Voies`.`Phase` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Difficulté' ORDER BY `Voies`.`Cotation`,`Voies`.`Id`";
			$result = $dbh->query($query);
			$Actual_phase = null;
			while ($Voie = $result->fetchObject()) {
				if ($Voie->Phase == null) {
					$Voie->Phase = '';
				}
				if ($Voie->Phase != $Actual_phase) {
					$Previous = '0';
					$Actual_phase = $Voie->Phase;
				}
				$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id`, `Tournois_Utilisateurs`.`Catégorie` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
				$result1 = $dbh->query($query);
				while ($Grimpeur = $result1->fetchObject()) {
					$query = "SELECT `Réussite`, TIME_TO_SEC(`Chrono`) AS `Secondes` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
					$result2 = $dbh->query($query);
					if ($result2->rowCount() > 0) {
						$Essai = $result2->fetchObject();
						foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
							if ($Voie->Phase == 'Qualification') {
								if ($Essai->Réussite == null) {
									$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Niveau'] = $Voie->Cotation;
									$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Next_Prise'] = 0;
									$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
								} else {
									if ($Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Niveau'] == $Previous) {
										$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Next_Niveau'] = $Voie->Cotation;
										$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Next_Prise'] = $Essai->Réussite;
									}
								}
							} else {
								$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Top'] = $Essai->Réussite == null ? 1 : 0;
								$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Prise'] = $Essai->Réussite != null ? $Essai->Réussite : 0;
								$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
								if ($Voie->Phase == 'Finale' and $catégorie != 'Femme') {
									if ($Essai->Secondes != null) {
										$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Temps'] = $Essai->Secondes;
									} else {
										$Score_Voie[$Voie->Phase][$catégorie][$Grimpeur->Id]['Temps'] = 6*60;
									}
								}
							}
						}
					}
				}
				$Previous = $Voie->Cotation;
			}
			
			foreach ($Score_Voie as $Phase => &$Catégories) {
				foreach ($Catégories as $Catégorie => &$Tableau) {
					if ($Phase == 'Qualification') {
						array_multisort(array_column($Tableau, 'Niveau'),SORT_DESC,SORT_STRING,array_column($Tableau, 'Next_Prise'),SORT_DESC,SORT_NUMERIC,$Tableau);
						echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Difficulté';
						if ($Phase != '') {
							echo '<br/>'.$Phase;
						}if ($Catégorie != '') {
							echo '<br/>'.$Catégorie;
						}
						echo '</th></tr></thead><tbody>';
						$Code = '';
						foreach ($Tableau as $i => &$Niveau) {
							$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
							$result = $dbh->prepare($query);
							$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Niveau['Grimpeur']]);
							$Grimpeur = $result->fetchObject();
							$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
							if ($i > 0 and $Tableau[$i-1]['Niveau'] == $Niveau['Niveau'] and $Tableau[$i-1]['Next_Prise'] == $Niveau['Next_Prise']) {
								$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
								$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
							} else {
								$Code .= '<td>'.strval($i+1).'</td>';
								$Tableau[$i]['Rang'] = $i+1;
							}
							$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
							$Code .= '<br>'.$Grimpeur->Genre;
							if ($Grimpeur->Equipe != null) {
								$Code .= ' ('.$Grimpeur->Equipe.')';
							}
							$Code .= '</td>';
							$Code .= '<td>'.($Niveau['Next_Prise'] == 0 ? $Niveau['Niveau'] : $Niveau['Next_Prise'].' prises dans la '.$Niveau['Next_Niveau']).'</td>';
							$Code .= '</tr>';
							$Equipe['Difficulté'][$Grimpeur->Equipe] += $Niveau['Rang'];
							if (isset($Score_Voie['Demi-finale'][$Catégorie][$Grimpeur->Id])) {
								$Score_Voie['Demi-finale'][$Catégorie][$Grimpeur->Id]['Qualification'] = $Niveau['Rang'];
							}
							if (isset($Score_Voie['Finale'][$Catégorie][$Grimpeur->Id])) {
								$Score_Voie['Finale'][$Catégorie][$Grimpeur->Id]['Qualification'] = $Niveau['Rang'];
							}
						}
						/*arsort($Equipe['Difficulté']);
						$count = 1;
						foreach ($Equipe['Difficulté'] as $Eq => $Points) {
							echo '<tr>';
							echo '<td>'.$count.'</td>';
							echo '<td>'.$Eq.'</td>';
							echo '<td>'.$Points.' points</td>';
							echo '</tr>';
							$count++;
						}*/
						
						echo $Code;
						echo '</tbody></table>';
					} else {
						if ($Phase == 'Demi-finale') {
							array_multisort(array_column($Tableau, 'Top'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Prise'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Qualification'),SORT_ASC,SORT_NUMERIC,$Tableau);
						} elseif ($Phase == 'Finale' and $Catégorie == 'Femme') {
							array_multisort(array_column($Tableau, 'Top'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Prise'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Qualification'),SORT_ASC,SORT_NUMERIC,$Tableau);
						} else {
							array_multisort(array_column($Tableau, 'Top'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Prise'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Demi-finale'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Qualification'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Temps'),SORT_ASC,SORT_NUMERIC,$Tableau);
						}
						echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Difficulté';
						if ($Phase != '') {
							echo '<br/>'.$Phase;
						}if ($Catégorie != '') {
							echo '<br/>'.$Catégorie;
						}
						echo '</th></tr></thead><tbody>';
						$Code = '';
						foreach ($Tableau as $i => &$Niveau) {
							$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
							$result = $dbh->prepare($query);
							$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Niveau['Grimpeur']]);
							$Grimpeur = $result->fetchObject();
							$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
							if ($i > 0 and $Tableau[$i-1]['Top'] == $Niveau['Top'] and $Tableau[$i-1]['Prise'] == $Niveau['Prise'] and $Tableau[$i-1]['Qualification'] == $Niveau['Qualification'] and (!isset($Niveau['Demi-finale']) or $Tableau[$i-1]['Demi-finale'] == $Niveau['Demi-finale']) and (!isset($Niveau['Temps']) or $Tableau[$i-1]['Temps'] == $Niveau['Temps'])) {
								$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
								$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
							} else {
								$Code .= '<td>'.strval($i+1).'</td>';
								$Tableau[$i]['Rang'] = $i+1;
							}
							$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
							$Code .= '<br>'.$Grimpeur->Genre;
							if ($Grimpeur->Equipe != null) {
								$Code .= ' ('.$Grimpeur->Equipe.')';
							}
							$Code .= '</td>';
							$Code .= '<td>';
							if ($Niveau['Top'] == 1) {
								$Code .= 'Top';
								if ($Niveau['Temps'] != null) {
									$Code .= '<br/>'.date_create_from_format('U.u',sprintf("%0.3F",$Niveau['Temps']))->format('i:s.v');
								}
							} else {
								$Code .= $Niveau['Prise'].' prises';
							}
							$Code .=  '</td>';
							$Code .= '</tr>';
							$Equipe['Difficulté'][$Grimpeur->Equipe] += $Niveau['Rang'];
							if ($Phase == 'Demi-finale' and isset($Score_Voie['Finale'][$Catégorie][$Grimpeur->Id])) {
								$Score_Voie['Finale'][$Catégorie][$Grimpeur->Id]['Demi-finale'] = $Niveau['Rang'];
							}
						}
						/*arsort($Equipe['Difficulté']);
						$count = 1;
						foreach ($Equipe['Difficulté'] as $Eq => $Points) {
							echo '<tr>';
							echo '<td>'.$count.'</td>';
							echo '<td>'.$Eq.'</td>';
							echo '<td>'.$Points.' points</td>';
							echo '</tr>';
							$count++;
						}*/
						echo $Code;
						echo '</tbody></table>';
					}
				}
			}
		}

		//Vitesse
		if (!isset($_REQUEST['Type']) or $_REQUEST['Type'] == 'Vitesse') {
			$query = "SELECT `Voie` AS `Id`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
			$result = $dbh->query($query);
			$Score_Vitesse = [];
			while($Voie = $result->fetchObject()) {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`,
						MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`,
						COUNT(*) AS `Nb_Essais`,
						`Tournois_Utilisateurs`.`Catégorie`,
						`Tournois_Voies`.`Phase`
					FROM `Essais`
					LEFT OUTER JOIN `Tournois_Voies_Zones`
						ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id."
							AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones`
					LEFT OUTER JOIN `Tournois_Utilisateurs`
						ON `Tournois_Utilisateurs`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur`
					LEFT OUTER JOIN `Tournois_Voies`
						ON `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Voies`.`Voie` = `Essais`.`Voie`
					WHERE `Essais`.`Tournoi` = ".$Tournoi->Id."
						AND `Essais`.`Voie` = ".$Voie->Id."
						AND `Evalué` IS NOT NULL
						AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					if ($Grimpeur->Phase == null) {
						$Grimpeur->Phase = '';
					}
					foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
						$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
						if ($Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] > 0) {
							$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = min($Grimpeur->Secondes,$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps']);
						} else {
							$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = $Grimpeur->Secondes;
						}
						$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Nb_Essais'] += $Grimpeur->Nb_Essais;
					}
				}
			}
			foreach ($Score_Vitesse as $Phase => $Catégories) {
				foreach ($Catégories as $Catégorie => $Tableau) {
					array_multisort(array_column($Tableau, 'Temps'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Nb_Essais'),SORT_ASC,SORT_NUMERIC ,$Tableau);
					echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Vitesse';
					if ($Phase != '') {
						echo '<br/>'.$Phase;
					}if ($Catégorie != '') {
						echo '<br/>'.$Catégorie;
					}
					echo '</th></tr></thead><tbody>';
					$Code = '';
					foreach ($Tableau as $i => $Infos) {
						$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
						$result = $dbh->prepare($query);
						$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
						$Grimpeur = $result->fetchObject();
						$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
						if ($i > 0 and $Tableau[$i-1]['Temps'] == $Infos['Temps'] and $Tableau[$i-1]['Nb_Essais'] == $Infos['Nb_Essais']) {
							$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
							$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
						} else {
							$Code .= '<td>'.strval($i+1).'</td>';
							$Tableau[$i]['Rang'] = $i+1;
						}
						$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
						$Code .= '<br>'.$Grimpeur->Genre;
						if ($Grimpeur->Equipe != null) {
							$Code .= ' ('.$Grimpeur->Equipe.')';
						}
						$Code .= '</td>';
						$Infos['Temps'] = str_replace(',','.',$Infos['Temps']);
						$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Infos['Temps']))->format('i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
						$Code .= '</tr>';
						$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
					}
					/*asort($Equipe['Vitesse']);
					$count = 1;
					foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
						echo '<tr>';
						echo '<td>'.$count.'</td>';
						echo '<td>'.$Eq.'</td>';
						echo '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Secondes))->format('H:i:s.v').'</td>';
						echo '</tr>';
						$count++;
					}*/
					echo $Code;
					echo '</tbody></table>';
				}
			}
		}

		//Bloc
		if (!isset($_REQUEST['Type']) or $_REQUEST['Type'] == 'Bloc') {
			$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués`, `Nb_Points_Absolu`, `Nb_Points_Relatif`, `Tournois_Voies`.`Phase` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Bloc'";
			$result = $dbh->query($query);
			$Score_Bloc = [];
			while($Voie = $result->fetchObject()) {
				if ($Voie->Phase == null) {
					$Voie->Phase = '';
				}
				$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " ORDER BY `Id`";
				$result1 = $dbh->query($query);
				if ($result1->rowCount() > 0) {
					while($Zone = $result1->fetchObject()) {
						$query = "SELECT 
							`Essais`.`Utilisateur` AS `Id`,
							`Tournois_Utilisateurs`.`Catégorie`,
							MIN(`Essais`.`Date`) AS `Date`
						FROM `Essais`
						LEFT OUTER JOIN `Tournois_Utilisateurs`
						ON `Tournois_Utilisateurs`.`Tournoi` = `Essais`.`Tournoi`
							AND `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur`
						WHERE `Essais`.`Tournoi` = ".$Tournoi->Id."
							AND `Voie` = ".$Voie->Id."
							AND `Evalué` IS NOT NULL
							AND (`Zones` >= ".$Zone->Id." OR `Essais`.`Réussite` IS NULL)";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$query .= " GROUP BY `Essais`.`Utilisateur`";
						$result2 = $dbh->query($query);
						if ($result2->rowCount() > 0) {
							$Points = round($Zone->Nb_Points_Relatif/$result2->rowCount());
							while($Grimpeur = $result2->fetchObject()) {
								if ($Voie->Phase == 'Qualification') {
									foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
										$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id] += $Points+$Zone->Nb_Points_Absolu;
									}
								} else {
									$query = "SELECT COUNT(*)+1 AS `Nb_Essais`
										FROM `Essais`
										WHERE `Tournoi` = ".$Tournoi->Id."
											AND `Utilisateur` = ".$Grimpeur->Id."
											AND `Voie` = ".$Voie->Id."
											AND `Evalué` IS NOT NULL
											AND `Réussite` IS NOT NULL
											AND (`Zones` IS NULL OR `Zones` < ".$Zone->Id.")
											AND `Date` < '".$Grimpeur->Date."'";
									$result3 = $dbh->query($query);
									$Essais = $result3->fetchObject();
									foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
										$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id]['Zone_Count'] += 1;
										$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id]['Zone_Count_Essais'] += $Essais->Nb_Essais;
									}
								}
							}
						}
					}
				}
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`, `Tournois_Utilisateurs`.`Catégorie`, MIN(`Essais`.`Date`) AS `Date` FROM `Essais` LEFT OUTER JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Tournoi` = `Essais`.`Tournoi` AND `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				if ($result1->rowCount() > 0) {
					$Points = round($Voie->Nb_Points_Relatif/$result1->rowCount());
					while($Grimpeur = $result1->fetchObject()) {
						if ($Voie->Phase == 'Qualification') {
							foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
								$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id] += $Points+$Voie->Nb_Points_Absolu;
							}
						} else {
							$query = "SELECT COUNT(*)+1 AS `Nb_Essais`
								FROM `Essais`
								WHERE `Tournoi` = ".$Tournoi->Id."
									AND `Utilisateur` = ".$Grimpeur->Id."
									AND `Voie` = ".$Voie->Id."
									AND `Evalué` IS NOT NULL
									AND `Réussite` IS NOT NULL
									AND `Date` < '".$Grimpeur->Date."'";
							$result3 = $dbh->query($query);
							$Essais = $result3->fetchObject();
							foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
								$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id]['Top_Count'] += 1;
								$Score_Bloc[$Voie->Phase][$catégorie][$Grimpeur->Id]['Top_Count_Essais'] += $Essais->Nb_Essais;
							}
						}
					}
				}
			}
			
			foreach ($Score_Bloc as $Phase => $Catégories) {
				foreach ($Catégories as $Catégorie => $Tableau) {
					if ($Phase == 'Qualification') {
						foreach ($Tableau as $Id => &$Points) {
							$query="SELECT COUNT(*) AS `Nb_Essais_Total` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = `Essais`.`Tournoi` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Evalué` IS NOT NULL AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Bloc'";
							$result = $dbh->query($query);
							$Classement = $result->fetchObject();
							$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Nb_Essais_Total' =>$Classement->Nb_Essais_Total];
						}
						array_multisort(array_column($Tableau, 'Points'),SORT_DESC,SORT_NUMERIC,$Tableau);
						echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc';
						if ($Phase != '') {
							echo '<br/>'.$Phase;
						}if ($Catégorie != '') {
							echo '<br/>'.$Catégorie;
						}
						echo '</th></tr></thead><tbody>';
						$Code = '';
						foreach ($Tableau as $i => $Infos) {
							$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
							$result = $dbh->prepare($query);
							$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
							$Grimpeur = $result->fetchObject();
							$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
							if ($i > 0 and $Tableau[$i-1]['Points'] == $Infos['Points']) {
								$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
								$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
							} else {
								$Code .= '<td>'.strval($i+1).'</td>';
								$Tableau[$i]['Rang'] = $i+1;
							}
							$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
							$Code .= '<br>'.$Grimpeur->Genre;
							if ($Grimpeur->Equipe != null) {
								$Code .= ' ('.$Grimpeur->Equipe.')';
							}
							$Code .= '</td>';
							$Code .= '<td>'.$Infos['Points'].' points</td>';
							$Code .= '</tr>';
							$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
						}
						/*
						arsort($Equipe['Bloc']);
						$count = 1;
						foreach ($Equipe['Bloc'] as $Eq => $Points) {
							echo '<tr>';
							echo '<td>'.$count.'</td>';
							echo '<td>'.$Eq.'</td>';
							echo '<td>'.$Points.' points</td>';
							echo '</tr>';
							$count++;
						}*/
						echo $Code;
						echo '</tbody></table>';
					} else {
						foreach ($Tableau as $Id => &$Tab) {
							$Tab['Grimpeur'] = $Id;
							if (!isset($Tab['Zone_Count'])) {
								$Tab['Zone_Count'] = 0;
								$Tab['Zone_Count_Essais'] = 0;
							}
							if (!isset($Tab['Top_Count'])) {
								$Tab['Top_Count'] = 0;
								$Tab['Top_Count_Essais'] = 0;
							}
						}
						array_multisort(array_column($Tableau, 'Top_Count'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Zone_Count'),SORT_DESC,SORT_NUMERIC,array_column($Tableau, 'Top_Count_Essais'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Zone_Count_Essais'),SORT_ASC,SORT_NUMERIC,$Tableau);
						echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc';
						if ($Phase != '') {
							echo '<br/>'.$Phase;
						}if ($Catégorie != '') {
							echo '<br/>'.$Catégorie;
						}
						echo '</th></tr></thead><tbody>';
						$Code = '';
						foreach ($Tableau as $i => $Infos) {
							$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
							$result = $dbh->prepare($query);
							$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
							$Grimpeur = $result->fetchObject();
							$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
							if ($i > 0 and $Tableau[$i-1]['Top_Count'] == $Infos['Top_Count'] and $Tableau[$i-1]['Zone_Count'] == $Infos['Zone_Count'] and $Tableau[$i-1]['Top_Count_Essais'] == $Infos['Top_Count_Essais'] and $Tableau[$i-1]['Zone_Count_Essais'] == $Infos['Zone_Count_Essais']) {
								$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
								$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
							} else {
								$Code .= '<td>'.strval($i+1).'</td>';
								$Tableau[$i]['Rang'] = $i+1;
							}
							$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
							$Code .= '<br>'.$Grimpeur->Genre;
							if ($Grimpeur->Equipe != null) {
								$Code .= ' ('.$Grimpeur->Equipe.')';
							}
							$Code .= '</td>';
							$Code .= '<td>';
							$Code .=  $Infos['Top_Count'].' top';
							if ($Infos['Top_Count'] > 0) {
								$Code .=  ' ('.$Infos['Top_Count_Essais'].' essais)';
							}
							$Code .=  '<br/>';
							$Code .=  $Infos['Zone_Count'].' zones';
							if ($Infos['Zone_Count'] > 0) {
								$Code .=  ' ('.$Infos['Zone_Count_Essais'].' essais)';
							}
							$Code .=  '</td>';
							$Code .= '</tr>';
							$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
						}
						/*
						arsort($Equipe['Bloc']);
						$count = 1;
						foreach ($Equipe['Bloc'] as $Eq => $Points) {
							echo '<tr>';
							echo '<td>'.$count.'</td>';
							echo '<td>'.$Eq.'</td>';
							echo '<td>'.$Points.' points</td>';
							echo '</tr>';
							$count++;
						}*/
						echo $Code;
						echo '</tbody></table>';
					}
				}
			}
		}

		/*
		//Combiné
		$Total = [];
		$Classement_Voie = array_combine(array_column($Score_Voie, 'Grimpeur'), array_column($Score_Voie, 'Rang'));
		$Classement_Vitesse = array_combine(array_column($Score_Vitesse, 'Grimpeur'), array_column($Score_Vitesse, 'Rang'));
		$Classement_Bloc = array_combine(array_column($Score_Bloc, 'Grimpeur'), array_column($Score_Bloc, 'Rang'));
		foreach(array_unique(array_merge(array_column($Score_Voie, 'Grimpeur'),array_column($Score_Vitesse, 'Grimpeur'),array_column($Score_Bloc, 'Grimpeur'))) as $Utilisateur) {
			$Total[$Utilisateur] = 1;
			if (isset($Classement_Voie[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Voie[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Voie)+1;
			}
			if (isset($Classement_Vitesse[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Vitesse[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Vitesse)+1;
			}
			if (isset($Classement_Bloc[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Bloc[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Bloc)+1;
			}
		}
		asort($Total);
		$Total = [array_keys($Total),array_values($Total)];
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Général</th></tr></thead><tbody>';
		$count = 1;
		$Code = '';
		for ($i = 0; $i < count($Total[0]); $i++) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Total[0][$i]]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Total[1][$i-1] == $Total[1][$i]) {
				$Code .= '<td>'.$Total[2][$i-1].'</td>';
				$Total[2][$i] = $Total[2][$i-1];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Total[2][$i] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Total[1][$i].'</td>';
			$Code .= '</tr>';
			$Equipe['Général'][$Grimpeur->Equipe][1] += 1;
			if ($Equipe['Général'][$Grimpeur->Equipe][0] == null) {
				$Equipe['Général'][$Grimpeur->Equipe][0] = 1;
			}
			if ($Equipe['Général'][$Grimpeur->Equipe][1] <= 3) {
				$Equipe['Général'][$Grimpeur->Equipe][0] *= $Total[2][$i];
			}
			$count++;
		}
		array_multisort(array_column($Equipe['Général'], '0'),SORT_ASC,SORT_NUMERIC,$Equipe['Général']);
		$count = 1;
		foreach ($Equipe['Général'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points[0].' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';
		*/

		echo '</div>';
		break;
	case 6:
		// ================================================================================
		// === NOM DES COMPETITEURS ET SCORE ==============================================
		// ================================================================================

		$nomGrimpeur = array();
		$armeGrimpeur = array();

		$scoreD = array(
			"Qualification - Seniors femmes" => array(),
			"Qualification - Seniors hommes" => array(),
			"Qualification - Vétérans hommes" => array(),
			"Qualification - Homme" => array(),
			"Demi-finale - Seniors hommes" => array(),
			"Demi-finale - Vétérans hommes" => array(),
			"Demi-finale - Homme" => array(),
			"Finale - Seniors femmes" => array(),
			"Finale - Seniors hommes" => array(),
			"Finale - Vétérans hommes" => array(),
			"Finale - Homme" => array()
		);
		$scoreB = array(
			"Qualification - Seniors femmes" => array(),
			"Qualification - Seniors hommes" => array(),
			"Qualification - Vétérans hommes" => array(),
			"Qualification - Homme" => array(),
			"Finale - Seniors femmes" => array(),
			"Finale - Seniors hommes" => array(),
			"Finale - Vétérans hommes" => array(),
			"Finale - Homme" => array()
		);

		$MAX_PRISES = 1000;

		// ================================================================================
		// === DIFFICULTÉ =================================================================
		// ================================================================================

				// fonction pour calculer le score d'un grimpeur
				function quantifierScoreD($e) {
					global $MAX_PRISES;
					$v = 5;
					if ($e["cotation"] == "6a") $v = 0;
					else if ($e["cotation"] == "6b") $v = 1;
					else if ($e["cotation"] == "6c") $v = 2;
					else if ($e["cotation"] == "7a") $v = 3;
					else if ($e["cotation"] == "7b") $v = 4;
					$p = $e["prise"];
					$p = is_null($p)? $MAX_PRISES - 1 : intval(10 * floatval($p));
					$t = $e["temps"];
					$h = $i = $s = $u = 0;
					if (!is_null($t)) {
						$h = intval(substr($t, 0, 2));
						$i = 59 - intval(substr($t, 3, 2));
						$s = 59 - intval(substr($t, 6, 2));
						$u = 999 - intval(substr($t, 9, 3));
					}
					$x = ((($v * $MAX_PRISES + $p) * 60 + $i) * 60 + $s) * 1000 + $u;
					return $x;
				}

				// récupérer les infos depuis la DB pour les essais en difficulté
				$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 6 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Difficulté' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 6 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur` ORDER BY `Voies`.`Cotation` ";
				$result = $dbh->query($query);
				// mettre les données dans les bons tableaux
				while ($e = $result->fetchObject()) {
					$u = $e->Utilisateur;
					$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom;
					$armeGrimpeur[$u] = $e->Equipe;
					$classements = array($e->Phase." - ".$e->Catégorie, $e->Phase." - ".$e->Genre);
					foreach ($classements as $classement) if (array_key_exists($classement, $scoreD)) {
						$valide = false;
						$p = $scoreD[$classement][$u];
						if (is_null($e->Cotation) || is_null($e->Réussite) || $e->Cotation == "6a")
							$valide = true;
						if ($e->Cotation == "6b" && !is_null($p) && $p["cotation"] == "6a" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "6c" && !is_null($p) && $p["cotation"] == "6b" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "7a" && !is_null($p) && $p["cotation"] == "6c" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "7b" && !is_null($p) && $p["cotation"] == "7a" && is_null($p["prise"])) $valide = true;
						if ($valide) {
							$scoreD[$classement][$u] = array(
								"cotation" => $e->Cotation,
								"prise" => $e->Réussite,
								"temps" => $e->Chrono
							);
						}
					}
				}
				// calculer les scores à partir des essais
				foreach ($scoreD as $classement => &$s) {
					$s = array_map('quantifierScoreD', $s);
				}

		// ================================================================================
		// === BLOC =======================================================================
		// ================================================================================

				// hashmap contenant le nombre de grimpeurs qui ont réussi une zone / bloc
				$zone = array(
					"Qualification - Seniors femmes" => array(),
					"Qualification - Seniors hommes" => array(),
					"Qualification - Vétérans hommes" => array(),
					"Qualification - Homme" => array(),
					"Finale - Seniors femmes" => array(),
					"Finale - Seniors hommes" => array(),
					"Finale - Vétérans hommes" => array(),
					"Finale - Homme" => array()
				);
				$top = array(
					"Qualification - Seniors femmes" => array(),
					"Qualification - Seniors hommes" => array(),
					"Qualification - Vétérans hommes" => array(),
					"Qualification - Homme" => array(),
					"Finale - Seniors femmes" => array(),
					"Finale - Seniors hommes" => array(),
					"Finale - Vétérans hommes" => array(),
					"Finale - Homme" => array()
				);

				// fonction pour calculer les scores à partir des essais d'un grimpeur
				function quantifierScoreB($classement, $es) {
					global $zone;
					global $top;
					$x = 0;
					foreach ($es as $e) {
						$v = $e->Voie;
						$z = $zone[$classement][$v];
						$t = $top[$classement][$v];
						if (is_null($e->Réussite))
							$x += 2000 / (2 * $t);
					}
					return $x;
				}

				// récupérer les infos depuis la DB pour les essais en bloc
				$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 6 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Bloc' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 6 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur`";
				$result = $dbh->query($query);
				// mettre les données dans les bons tableaux
				while ($e = $result->fetchObject()) {
					$u = $e->Utilisateur;
					$v = $e->Voie;
					$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom;
					$armeGrimpeur[$u] = $e->Equipe;
					$c = $e->Catégorie;
					$classements = array($e->Phase." - ".$e->Catégorie, $e->Phase." - ".$e->Genre);
					foreach ($classements as $classement) if (array_key_exists($classement, $scoreB)) {
						if (is_null($e->Réussite))
							$top[$classement][$v] = $top[$classement][$v] + 1;
						else
							$zone[$classement][$v] = $zone[$classement][$v] + 1;
						if (!array_key_exists($u, $scoreB[$classement]))
							$scoreB[$classement][$u] = array();
						array_push($scoreB[$classement][$u], $e);
					}
				}
				// calculer les scores à partir des essais
				foreach ($scoreB as $classement => &$s) {
					$s = array_map(
						function ($es) use ($classement) {return quantifierScoreB($classement, $es);},
						$s
					);
				}


		//Vitesse
		if (!isset($_REQUEST['Type']) or $_REQUEST['Type'] == 'Vitesse') {
			$query = "SELECT `Voie` AS `Id`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
			$result = $dbh->query($query);
			$Score_Vitesse = [];
			while($Voie = $result->fetchObject()) {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`,
						MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`,
						COUNT(*) AS `Nb_Essais`,
						`Tournois_Utilisateurs`.`Catégorie`,
						`Tournois_Voies`.`Phase`
					FROM `Essais`
					LEFT OUTER JOIN `Tournois_Voies_Zones`
						ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id."
							AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones`
					LEFT OUTER JOIN `Tournois_Utilisateurs`
						ON `Tournois_Utilisateurs`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur`
					LEFT OUTER JOIN `Tournois_Voies`
						ON `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id."
							AND `Tournois_Voies`.`Voie` = `Essais`.`Voie`
					WHERE `Essais`.`Tournoi` = ".$Tournoi->Id."
						AND `Essais`.`Voie` = ".$Voie->Id."
						AND `Evalué` IS NOT NULL
						AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					if ($Grimpeur->Phase == null) {
						$Grimpeur->Phase = '';
					}
					foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
						$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
						if ($Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] > 0) {
							$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = min($Grimpeur->Secondes,$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps']);
						} else {
							$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = $Grimpeur->Secondes;
						}
						$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Nb_Essais'] += $Grimpeur->Nb_Essais;
					}
				}
			}
			foreach ($Score_Vitesse as $Phase => $Catégories) {
				foreach ($Catégories as $Catégorie => $Tableau) {
					array_multisort(array_column($Tableau, 'Temps'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Nb_Essais'),SORT_ASC,SORT_NUMERIC ,$Tableau);
					echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Vitesse';
					if ($Phase != '') {
						echo '<br/>'.$Phase;
					}if ($Catégorie != '') {
						echo '<br/>'.$Catégorie;
					}
					echo '</th></tr></thead><tbody>';
					$Code = '';
					foreach ($Tableau as $i => $Infos) {
						$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
						$result = $dbh->prepare($query);
						$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
						$Grimpeur = $result->fetchObject();
						$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
						if ($i > 0 and $Tableau[$i-1]['Temps'] == $Infos['Temps'] and $Tableau[$i-1]['Nb_Essais'] == $Infos['Nb_Essais']) {
							$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
							$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
						} else {
							$Code .= '<td>'.strval($i+1).'</td>';
							$Tableau[$i]['Rang'] = $i+1;
						}
						$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
						$Code .= '<br>'.$Grimpeur->Genre;
						if ($Grimpeur->Equipe != null) {
							$Code .= ' ('.$Grimpeur->Equipe.')';
						}
						$Code .= '</td>';
						$Infos['Temps'] = str_replace(',','.',$Infos['Temps']);
						$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Infos['Temps']))->format('i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
						$Code .= '</tr>';
						$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
					}
					/*asort($Equipe['Vitesse']);
					$count = 1;
					foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
						echo '<tr>';
						echo '<td>'.$count.'</td>';
						echo '<td>'.$Eq.'</td>';
						echo '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Secondes))->format('H:i:s.v').'</td>';
						echo '</tr>';
						$count++;
					}*/
					echo $Code;
					echo '</tbody></table>';
				}
			}
		}

		// ================================================================================
		// === CALCUL DU CLASSEMENT ET AFFICHAGE ==========================================
		// ================================================================================

		$rating1 = NULL;
		$rating2 = NULL;
		$rating3 = NULL;

		// définit si une performance est meilleure qu'une autre pour l'ordre lex sur (rating1, rating2, rating3)
		function ltRank($u, $v) {
			global $rating1;
			global $rating2;
			global $rating3;
			if ($rating1[$u] != $rating1[$v])
				return $rating1[$v] - $rating1[$u];
			else if (!is_null($rating2) && $rating2[$u] != $rating2[$v])
				return $rating2[$v] - $rating2[$u];
			else if (!is_null($rating3) && $rating3[$u] > $rating3[$v])
				return $rating3[$v] - $rating3[$u];
			else
				return 0;
		}

		// map rang => grimpeur en fonction du rating donné par rating1 puis rating2 si égalité puis rating3 si égalité
		function ranking() {
			global $rating1;
			global $rating2;
			global $rating3;
			$rank = array();
			$cnt = 0;
			foreach ($rating1 as $u => $s) {
				$rank[$cnt] = $u;
				$cnt++;
			}
			usort($rank, 'ltRank');
			$rk = 0;
			$last = NULL;
			foreach ($rank as $cnt => &$u) {
				if ($cnt == 0 || ltRank($last, $u) < 0)
					$rk = $cnt + 1;
				$last = $u;
				$u = array("user" => $u, "rank" => $rk);
			}
			return $rank;
		}

		// phase précédente
		$previousRating = array(
			"Qualification - Seniors femmes" => NULL,
			"Qualification - Seniors hommes" => NULL,
			"Qualification - Vétérans hommes" => NULL,
			"Qualification - Homme" => NULL,
			"Demi-finale - Seniors femmes" => "Qualification - Seniors femmes",
			"Demi-finale - Seniors hommes" => "Qualification - Seniors hommes",
			"Demi-finale - Vétérans hommes" => "Qualification - Vétérans hommes",
			"Demi-finale - Homme" => "Qualification - Homme",
			"Finale - Seniors femmes" => "Demi-finale - Seniors femmes",
			"Finale - Seniors hommes" => "Demi-finale - Seniors hommes",
			"Finale - Vétérans hommes" => "Demi-finale - Vétérans hommes",
			"Finale - Homme" => "Demi-finale - Homme"
		);

		// transforme un score en performance affichable
		function performance($type, $x) {
			global $MAX_PRISES;
			if ($type == "Difficulté") {
				$u = 999 - $x % 1000; $x = intdiv($x, 1000);
				$s = 59 - $x % 60; $x = intdiv($x, 60);
				$i = 59 - $x % 60; $x = intdiv($x, 60);
				$p = $x % $MAX_PRISES; $x = intdiv($x, $MAX_PRISES);
				$v = $x;
				$p = $p == $MAX_PRISES - 1? "TOP" : $p / 10;
				if ($v == 0) return "6a, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
				else if ($v == 1) return "6b, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
				else if ($v == 2) return "6c, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
				else if ($v == 3) return "7a, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
				else if ($v == 4) return "7b, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
				else return "prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
			}
			else {
				return intval($x);
			}
		}

		// affichage des classements en difficulté
		$scores = array("Difficulté" => $scoreD, "Bloc" => $scoreB);
		foreach ($scores as $type => &$score) {
			foreach ($score as $classement => &$s) {
				$pClassement = $previousRating[$classement];
				$gClassement = is_null($pClassement)? NULL : $previousRating[$pClassement];
				$rating1 = $score[$classement];
				$rating2 = is_null($pClassement)? NULL : $score[$pClassement];
				$rating3 = is_null($gClassement)? NULL : $score[$gClassement];
				echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';
				echo '<table style="min-width: unset"><thead><tr><th colspan="4">'.$type.' - '.$classement.'</th></tr></thead><tbody>';
				echo '<tr>';
				echo '<td><b>RANG</b></td>';
				echo '<td><b>COMPÉTITEUR</b></td>';
				echo '<td><b>ARMÉE</b></td>';
				echo '<td><b>PERFORMANCE</b></td>';
				foreach (ranking() as $grimpeur) {
					$u = $grimpeur["user"];
					echo '<tr>';
					echo '<td>'.$grimpeur["rank"].'</td>';
					echo '<td>'.$nomGrimpeur[$u].'</td>';
					echo '<td>'.$armeGrimpeur[$u].'</td>';
					echo '<td>'.performance($type, $s[$u]).'</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
				echo '</div>';
			}
		}
		break;
	case 7: //TSGED 2022


		

		$Equipe = [];
		echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';

		//Etablit par propagation si la voie est validée (deux supérieures)
		$Voie_validées=[];
		$int = 0;
		$query = "SELECT `Voies`.`Id` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Difficulté' ORDER BY `Voies`.`Cotation`";
		$result = $dbh->query($query);
		while ($Voie = $result->fetchObject()) {
			$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
			$result1 = $dbh->query($query);
			while ($Grimpeur = $result1->fetchObject()) {
				$query = "SELECT COUNT(*) AS `Réussi` FROM `Essais` WHERE `Réussite` IS NULL AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
				if ($dbh->query($query)->fetchObject()->Réussi > 0) {
					$Voie_validées[$int][$Grimpeur->Id] = 2;
				} 
				else {
					$query = "SELECT COUNT(*) AS `Zone` FROM `Essais` WHERE `Zones`=1 AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
					if ($dbh->query($query)->fetchObject()->Zone > 0) {
						$Voie_validées[$int][$Grimpeur->Id] = 1;
					}
					else{
						$Voie_validées[$int][$Grimpeur->Id] = 0;
					}
				}
			} 
			$int++;
		}
		//If propagation
		if (true) {
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				$i = $int-2;
				$v = false;
				while ($i >= 0) {
					if ($Voie_validées[$i+1][$Grimpeur] == 2 and $Voie_validées[$i][$Grimpeur] == 2) {
						$v = true;
					}
					if ($v) {
						$Voie_validées[$i][$Grimpeur] = 2;
					}
					$i--;
				}
			}
		}
		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Voie'";
		$result = $dbh->query($query);
		$Score_Voie = [];
		/*while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Prise') {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`, SUM(`Nb_Points`) AS `Points` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Prises` ON `Tournois_Voies_Prises`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Prises`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Prises`.`Id` = `Essais`.`Prises_Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Grimpeur->Points;
				}
			} else {
				if ($Voie->Evaluation == 'Zone') {
					$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
					$result1 = $dbh->query($query);
					while($Zone = $result1->fetchObject()) {
						//On vérifie que la zone est qualifiée
						$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
						if ($Nombre > 0) {
							$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
							if ($Voie->Nb_Essais_Evalués != null) {
								$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
							}
							$result2 = $dbh->query($query);
							$Points = round(1000/$result2->rowCount());
							while($Grimpeur = $result2->fetchObject()) {
								$Score_Voie[$Grimpeur->Id] += $Points;
							}
						}
					}
				}
				$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$result1 = $dbh->query($query);
				$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0 ;
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Points;
				}
			}
		}*/


		//Calcul des points : 1000/(nb de grimpeur) si réussi et 500/(nb de grimpeur) si zone pour le bloc
		foreach (array_keys($Voie_validées) as $Voie) {

			$SommeTop = 0;
			$SommeZone = 0;

			
			foreach (array_values($Voie_validées[$Voie]) as $Grimpeur) {
				if ($Grimpeur == 2){
					$SommeTop += 1;
					$SommeZone += 1;
				}
				if ($Grimpeur == 1){
					$SommeZone += 1;
				}
			}
			
		
			$PointsTop = $SommeTop > 0 ? round(1000/$SommeTop) : 0;
			$PointsZone = $SommeZone > 0 ? round(500/$SommeZone) : 0;
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				if ($Voie_validées[$Voie][$Grimpeur] == 2) {
					$Score_Voie[$Grimpeur] += $PointsTop;
				}
				elseif ($Voie_validées[$Voie][$Grimpeur] == 1) {
					$Score_Voie[$Grimpeur] += $PointsZone;
				}
			}
		}




		foreach ($Score_Voie as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) AS `Num_Essai` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Voie' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Num_Essai`, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Num_Essai' =>$Classement->Num_Essai , 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
		}
		array_multisort(array_column($Score_Voie, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Voie, 'Cotation'),SORT_ASC,SORT_STRING,array_column($Score_Voie, 'Num_Essai'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Voie);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Voie</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Voie as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Voie[$i-1]['Points'] == $Infos['Points'] and $Score_Voie[$i-1]['Cotation'] == $Infos['Cotation'] and $Score_Voie[$i-1]['Num_Essai'] == $Infos['Num_Essai'] and $Score_Voie[$i-1]['Secondes'] == $Infos['Secondes']) {
				$Code .= '<td>'.$Score_Voie[$i-1]['Rang'].'</td>';
				$Score_Voie[$i]['Rang'] = $Score_Voie[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Voie[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' en '.$Infos['Secondes'].'s)</td>';
			$Code .= '</tr>';
			$Equipe['Voie'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		arsort($Equipe['Voie']);
		$count = 1;
		foreach ($Equipe['Voie'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}

		echo $Code;
		echo '</tbody></table>';




		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Bloc'";
		$result = $dbh->query($query);
		$Score_Bloc = [];
		/*while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Zone') {
				$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
				$result1 = $dbh->query($query);
				while($Zone = $result1->fetchObject()) {
					//On vérifie que la zone est qualifiée
					$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
					if ($Nombre > 0) {
						$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$result2 = $dbh->query($query);
						$Points = round(1000/$result2->rowCount());
						while($Grimpeur = $result2->fetchObject()) {
							$Score_Bloc[$Grimpeur->Id] += $Points;
						}
					}
				}
			}
			$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$result1 = $dbh->query($query);
			$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0;
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Bloc[$Grimpeur->Id] += $Points;
			}
		}*/


		$Bloc_validées=[];
		$int = 0;
		$query = "SELECT `Voies`.`Id` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Bloc' ORDER BY `Voies`.`Cotation`";
		$result = $dbh->query($query);
		while ($Voie = $result->fetchObject()) {
			$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
			$result1 = $dbh->query($query);
			while ($Grimpeur = $result1->fetchObject()) {
				$query = "SELECT COUNT(*) AS `Réussi` FROM `Essais` WHERE `Réussite` IS NULL AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
				if ($dbh->query($query)->fetchObject()->Réussi > 0) {
					$Bloc_validées[$int][$Grimpeur->Id] = 2;
				} 
				else {
					$query = "SELECT COUNT(*) AS `Zone` FROM `Essais` WHERE `Zones`=1 AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
					if ($dbh->query($query)->fetchObject()->Zone > 0) {
						$Bloc_validées[$int][$Grimpeur->Id] = 1;
					}
					else{
						$Bloc_validées[$int][$Grimpeur->Id] = 0;
					}
				}
			} 
			$int++;
		}

		$Score_Bloc = [];

		foreach (array_keys($Bloc_validées) as $Voie) {

			$SommeTop = 0;
			$SommeZone = 0;

			
			foreach (array_values($Bloc_validées[$Voie]) as $Grimpeur) {
				if ($Grimpeur == 2){
					$SommeTop += 1;
					$SommeZone += 1;
				}
				if ($Grimpeur == 1){
					$SommeZone += 1;
				}
			}
			
		
			$PointsTop = $SommeTop > 0 ? round(1000/$SommeTop) : 0;
			$PointsZone = $SommeZone > 0 ? round(500/$SommeZone) : 0;
			foreach (array_keys($Bloc_validées[0]) as $Grimpeur) {
				if ($Bloc_validées[$Voie][$Grimpeur] == 2) {
					$Score_Bloc[$Grimpeur] += $PointsTop;
				}
				elseif ($Bloc_validées[$Voie][$Grimpeur] == 1) {
					$Score_Bloc[$Grimpeur] += $PointsZone;
				}
			}
		}



		foreach ($Score_Bloc as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, (SELECT COUNT(*) FROM `Essais` AS `E` LEFT OUTER JOIN `Tournois_Voies` AS `T` ON `T`.`Voie` = `E`.`Voie` AND `T`.`Tournoi` = `E`.`Tournoi` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `T`.`Type` = 'Bloc') AS `Nb_Essais_Total` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Bloc' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure, 'Nb_Essais_Total' =>$Classement->Nb_Essais_Total];
		}
		array_multisort(array_column($Score_Bloc, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Bloc, 'Nb_Essais_Total'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Cotation'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Bloc);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Bloc as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Bloc[$i-1]['Points'] == $Infos['Points'] and $Score_Bloc[$i-1]['Nb_Essais_Total'] == $Infos['Nb_Essais_Total']) {
				$Code .= '<td>'.$Score_Bloc[$i-1]['Rang'].'</td>';
				$Score_Bloc[$i]['Rang'] = $Score_Bloc[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Bloc[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' avec '.$Infos['Nb_Essais_Total'].' essais)</td>';
			$Code .= '</tr>';
			$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		/*$Score_Bloc[0]['Rang'] = 2;
		$Score_Bloc[2]['Rang'] = 3;*/
		arsort($Equipe['Bloc']);
		$count = 1;
		foreach ($Equipe['Bloc'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$Total = [];
		$Classement_Voie = array_combine(array_column($Score_Voie, 'Grimpeur'), array_column($Score_Voie, 'Rang'));
		$Classement_Vitesse = array_combine(array_column($Score_Vitesse, 'Grimpeur'), array_column($Score_Vitesse, 'Rang'));
		$Classement_Bloc = array_combine(array_column($Score_Bloc, 'Grimpeur'), array_column($Score_Bloc, 'Rang'));
		foreach(array_unique(array_merge(array_column($Score_Voie, 'Grimpeur'),array_column($Score_Vitesse, 'Grimpeur'),array_column($Score_Bloc, 'Grimpeur'))) as $Utilisateur) {
			$Total[$Utilisateur] = 1;
			if (isset($Classement_Voie[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Voie[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Voie)+1;
			}
			if (isset($Classement_Vitesse[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Vitesse[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Vitesse)+1;
			}
			if (isset($Classement_Bloc[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Bloc[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Bloc)+1;
			}
		}
		asort($Total);
		$Total = [array_keys($Total),array_values($Total)];
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Général</th></tr></thead><tbody>';
		$count = 1;
		$Code = '';
		for ($i = 0; $i < count($Total[0]); $i++) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Total[0][$i]]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Total[1][$i-1] == $Total[1][$i]) {
				$Code .= '<td>'.$Total[2][$i-1].'</td>';
				$Total[2][$i] = $Total[2][$i-1];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Total[2][$i] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Total[1][$i].'</td>';
			$Code .= '</tr>';
			$Equipe['Général'][$Grimpeur->Equipe][1] += 1;
			if ($Equipe['Général'][$Grimpeur->Equipe][0] == null) {
				$Equipe['Général'][$Grimpeur->Equipe][0] = 1;
			}
			if ($Equipe['Général'][$Grimpeur->Equipe][1] <= 3) {
				$Equipe['Général'][$Grimpeur->Equipe][0] *= $Total[2][$i];
			}
			$count++;
		}
		array_multisort(array_column($Equipe['Général'], '0'),SORT_ASC,SORT_NUMERIC,$Equipe['Général']);
		$count = 1;
		foreach ($Equipe['Général'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points[0].' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		echo '</div>';

		


		break;
	case 8: // CFM 2022
		// ================================================================================
		// === NOM DES COMPETITEURS ET SCORE ==============================================
		// ================================================================================
		
				$nomGrimpeur = array();
				$armeGrimpeur = array();
		
				$scoreD = array(
					"Qualification" => array(),
				);
				$scoreB = array(
					"Qualifications" => array(),
				);
		
				$MAX_PRISES = 1000;
		
		// ================================================================================
		// === DIFFICULTÉ =================================================================
		// ================================================================================
		
				// fonction pour calculer le score d'un grimpeur
				function quantifierScoreD($e) {
					global $MAX_PRISES;
					$v = 5;
					if ($e["cotation"] == "6a") $v = 0;
					else if ($e["cotation"] == "6b") $v = 1;
					else if ($e["cotation"] == "6c") $v = 2;
					else if ($e["cotation"] == "7a") $v = 3;
					else if ($e["cotation"] == "7b") $v = 4;
					$p = $e["prise"];
					$p = is_null($p)? $MAX_PRISES - 1 : intval(10 * floatval($p));
					$t = $e["temps"];
					$h = $i = $s = $u = 0;
					if (!is_null($t)) {
						$h = intval(substr($t, 0, 2));
						$i = 59 - intval(substr($t, 3, 2));
						$s = 59 - intval(substr($t, 6, 2));
						$u = 999 - intval(substr($t, 9, 3));
					}
					$x = ((($v * $MAX_PRISES + $p) * 60 + $i) * 60 + $s) * 1000 + $u;
					return $x;
				}
		
				// récupérer les infos depuis la DB pour les essais en difficulté
				$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 8 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Difficulté' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 8 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur` ORDER BY `Voies`.`Cotation` ";
				$result = $dbh->query($query);
				// mettre les données dans les bons tableaux
				while ($e = $result->fetchObject()) {
					$u = $e->Utilisateur;
					$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom;
					$armeGrimpeur[$u] = $e->Equipe;
					$classements = array($e->Phase);
					foreach ($classements as $classement) if (array_key_exists($classement, $scoreD)) {
						$valide = false;
						$p = $scoreD[$classement][$u];
						if (is_null($e->Cotation) || is_null($e->Réussite) || $e->Cotation == "6a")
							$valide = true;
						if ($e->Cotation == "6b" && !is_null($p) && $p["cotation"] == "6a" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "6c" && !is_null($p) && $p["cotation"] == "6b" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "7a" && !is_null($p) && $p["cotation"] == "6c" && is_null($p["prise"])) $valide = true;
						if ($e->Cotation == "7b" && !is_null($p) && $p["cotation"] == "7a" && is_null($p["prise"])) $valide = true;
						if ($valide) {
							$scoreD[$classement][$u] = array(
								"cotation" => $e->Cotation,
								"prise" => $e->Réussite,
								"temps" => $e->Chrono
							);
						}
					}
				}
				// calculer les scores à partir des essais
				foreach ($scoreD as $classement => &$s) {
					$s = array_map('quantifierScoreD', $s);
				}
		
		// ================================================================================
		// === BLOC =======================================================================
		// ================================================================================
		
				// hashmap contenant le nombre de grimpeurs qui ont réussi une zone / bloc
				$zone = array(
					"Qualification - Seniors femmes" => array(),
					"Qualification - Seniors hommes" => array(),
					"Qualification - Vétérans hommes" => array(),
					"Qualification - Homme" => array(),
					"Finale - Seniors femmes" => array(),
					"Finale - Seniors hommes" => array(),
					"Finale - Vétérans hommes" => array(),
					"Finale - Homme" => array()
				);
				$top = array(
					"Qualification - Seniors femmes" => array(),
					"Qualification - Seniors hommes" => array(),
					"Qualification - Vétérans hommes" => array(),
					"Qualification - Homme" => array(),
					"Finale - Seniors femmes" => array(),
					"Finale - Seniors hommes" => array(),
					"Finale - Vétérans hommes" => array(),
					"Finale - Homme" => array()
				);
		
				// fonction pour calculer les scores à partir des essais d'un grimpeur
				function quantifierScoreB($classement, $es) {
					global $zone;
					global $top;
					$x = 0;
					foreach ($es as $e) {
						$v = $e->Voie;
						$z = $zone[$classement][$v];
						$t = $top[$classement][$v];
						if (is_null($e->Réussite))
							$x += 2000 / (2 * $t);
					}
					return $x;
				}
		
				// récupérer les infos depuis la DB pour les essais en bloc
				$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 8 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Bloc' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 8 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur`";
				$result = $dbh->query($query);
				// mettre les données dans les bons tableaux
				while ($e = $result->fetchObject()) {
					$u = $e->Utilisateur;
					$v = $e->Voie;
					$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom;
					$armeGrimpeur[$u] = $e->Equipe;
					$c = $e->Catégorie;
					$classements = array($e->Phase." - ".$e->Catégorie, $e->Phase." - ".$e->Genre);
					foreach ($classements as $classement) if (array_key_exists($classement, $scoreB)) {
						if (is_null($e->Réussite))
							$top[$classement][$v] = $top[$classement][$v] + 1;
						else
							$zone[$classement][$v] = $zone[$classement][$v] + 1;
						if (!array_key_exists($u, $scoreB[$classement]))
							$scoreB[$classement][$u] = array();
						array_push($scoreB[$classement][$u], $e);
					}
				}
				// calculer les scores à partir des essais
				foreach ($scoreB as $classement => &$s) {
					$s = array_map(
						function ($es) use ($classement) {return quantifierScoreB($classement, $es);},
						$s
					);
				}
		
		
		//Vitesse
				if (!isset($_REQUEST['Type']) or $_REQUEST['Type'] == 'Vitesse') {
					$query = "SELECT `Voie` AS `Id`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
					$result = $dbh->query($query);
					$Score_Vitesse = [];
					while($Voie = $result->fetchObject()) {
						$query = "SELECT `Essais`.`Utilisateur` AS `Id`,
								MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`,
								COUNT(*) AS `Nb_Essais`,
								`Tournois_Utilisateurs`.`Catégorie`,
								`Tournois_Voies`.`Phase`
							FROM `Essais`
							LEFT OUTER JOIN `Tournois_Voies_Zones`
								ON `Tournois_Voies_Zones`.`Tournoi` = ".$Tournoi->Id."
									AND `Tournois_Voies_Zones`.`Voie` = ".$Voie->Id."
									AND `Tournois_Voies_Zones`.`Id` = `Essais`.`Zones`
							LEFT OUTER JOIN `Tournois_Utilisateurs`
								ON `Tournois_Utilisateurs`.`Tournoi` = ".$Tournoi->Id."
									AND `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur`
							LEFT OUTER JOIN `Tournois_Voies`
								ON `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id."
									AND `Tournois_Voies`.`Voie` = `Essais`.`Voie`
							WHERE `Essais`.`Tournoi` = ".$Tournoi->Id."
								AND `Essais`.`Voie` = ".$Voie->Id."
								AND `Evalué` IS NOT NULL
								AND `Réussite` IS NULL";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$query .= " GROUP BY `Essais`.`Utilisateur`";
						$result1 = $dbh->query($query);
						while($Grimpeur = $result1->fetchObject()) {
							if ($Grimpeur->Phase == null) {
								$Grimpeur->Phase = '';
							}
							foreach (explode(',',$Grimpeur->Catégorie != null ? $Grimpeur->Catégorie : '') as $catégorie) {
								$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Grimpeur'] = $Grimpeur->Id;
								if ($Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] > 0) {
									$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = min($Grimpeur->Secondes,$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps']);
								} else {
									$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Temps'] = $Grimpeur->Secondes;
								}
								$Score_Vitesse[$Grimpeur->Phase][$catégorie][$Grimpeur->Id]['Nb_Essais'] += $Grimpeur->Nb_Essais;
							}
						}
					}
					foreach ($Score_Vitesse as $Phase => $Catégories) {
						foreach ($Catégories as $Catégorie => $Tableau) {
							array_multisort(array_column($Tableau, 'Temps'),SORT_ASC,SORT_NUMERIC,array_column($Tableau, 'Nb_Essais'),SORT_ASC,SORT_NUMERIC ,$Tableau);
							echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Vitesse';
							if ($Phase != '') {
								echo '<br/>'.$Phase;
							}if ($Catégorie != '') {
								echo '<br/>'.$Catégorie;
							}
							echo '</th></tr></thead><tbody>';
							$Code = '';
							foreach ($Tableau as $i => $Infos) {
								$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
								$result = $dbh->prepare($query);
								$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
								$Grimpeur = $result->fetchObject();
								$Code .= '<tr onclick="window.location.href = \'Tournois/Fiche%20individuelle?Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
								if ($i > 0 and $Tableau[$i-1]['Temps'] == $Infos['Temps'] and $Tableau[$i-1]['Nb_Essais'] == $Infos['Nb_Essais']) {
									$Code .= '<td>'.$Tableau[$i-1]['Rang'].'</td>';
									$Tableau[$i]['Rang'] = $Tableau[$i-1]['Rang'];
								} else {
									$Code .= '<td>'.strval($i+1).'</td>';
									$Tableau[$i]['Rang'] = $i+1;
								}
								$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
								$Code .= '<br>'.$Grimpeur->Genre;
								if ($Grimpeur->Equipe != null) {
									$Code .= ' ('.$Grimpeur->Equipe.')';
								}
								$Code .= '</td>';
								$Infos['Temps'] = str_replace(',','.',$Infos['Temps']);
								$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Infos['Temps']))->format('i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
								$Code .= '</tr>';
								$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
							}
							/*asort($Equipe['Vitesse']);
							$count = 1;
							foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
								echo '<tr>';
								echo '<td>'.$count.'</td>';
								echo '<td>'.$Eq.'</td>';
								echo '<td>'.date_create_from_format('U.u',sprintf("%0.3F",$Secondes))->format('H:i:s.v').'</td>';
								echo '</tr>';
								$count++;
							}*/
							echo $Code;
							echo '</tbody></table>';
						}
					}
				}
		
		// ================================================================================
		// === CALCUL DU CLASSEMENT ET AFFICHAGE ==========================================
		// ================================================================================
		
				$rating1 = NULL;
				$rating2 = NULL;
				$rating3 = NULL;
		
				// définit si une performance est meilleure qu'une autre pour l'ordre lex sur (rating1, rating2, rating3)
				function ltRank($u, $v) {
					global $rating1;
					global $rating2;
					global $rating3;
					if ($rating1[$u] != $rating1[$v])
						return $rating1[$v] - $rating1[$u];
					else if (!is_null($rating2) && $rating2[$u] != $rating2[$v])
						return $rating2[$v] - $rating2[$u];
					else if (!is_null($rating3) && $rating3[$u] > $rating3[$v])
						return $rating3[$v] - $rating3[$u];
					else
						return 0;
				}
		
				// map rang => grimpeur en fonction du rating donné par rating1 puis rating2 si égalité puis rating3 si égalité
				function ranking() {
					global $rating1;
					global $rating2;
					global $rating3;
					$rank = array();
					$cnt = 0;
					foreach ($rating1 as $u => $s) {
						$rank[$cnt] = $u;
						$cnt++;
					}
					usort($rank, 'ltRank');
					$rk = 0;
					$last = NULL;
					foreach ($rank as $cnt => &$u) {
						if ($cnt == 0 || ltRank($last, $u) < 0)
							$rk = $cnt + 1;
						$last = $u;
						$u = array("user" => $u, "rank" => $rk);
					}
					return $rank;
				}
		
				// phase précédente
				$previousRating = array(
					"Qualification - Seniors femmes" => NULL,
					"Qualification - Seniors hommes" => NULL,
					"Qualification - Vétérans hommes" => NULL,
					"Qualification - Homme" => NULL,
					"Demi-finale - Seniors femmes" => "Qualification - Seniors femmes",
					"Demi-finale - Seniors hommes" => "Qualification - Seniors hommes",
					"Demi-finale - Vétérans hommes" => "Qualification - Vétérans hommes",
					"Demi-finale - Homme" => "Qualification - Homme",
					"Finale - Seniors femmes" => "Demi-finale - Seniors femmes",
					"Finale - Seniors hommes" => "Demi-finale - Seniors hommes",
					"Finale - Vétérans hommes" => "Demi-finale - Vétérans hommes",
					"Finale - Homme" => "Demi-finale - Homme"
				);
		
				// transforme un score en performance affichable
				function performance($type, $x) {
					global $MAX_PRISES;
					if ($type == "Difficulté") {
						$u = 999 - $x % 1000; $x = intdiv($x, 1000);
						$s = 59 - $x % 60; $x = intdiv($x, 60);
						$i = 59 - $x % 60; $x = intdiv($x, 60);
						$p = $x % $MAX_PRISES; $x = intdiv($x, $MAX_PRISES);
						$v = $x;
						$p = $p == $MAX_PRISES - 1? "TOP" : $p / 10;
						if ($v == 0) return "6a, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
						else if ($v == 1) return "6b, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
						else if ($v == 2) return "6c, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
						else if ($v == 3) return "7a, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
						else if ($v == 4) return "7b, prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
						else return "prise : ".$p.", temps : ".$i."min ".$s."s ".$u."ms";
					}
					else {
						return intval($x);
					}
				}
		
				// affichage des classements en difficulté
				$scores = array("Difficulté" => $scoreD, "Bloc" => $scoreB);
				foreach ($scores as $type => &$score) {
					foreach ($score as $classement => &$s) {
						$pClassement = $previousRating[$classement];
						$gClassement = is_null($pClassement)? NULL : $previousRating[$pClassement];
						$rating1 = $score[$classement];
						$rating2 = is_null($pClassement)? NULL : $score[$pClassement];
						$rating3 = is_null($gClassement)? NULL : $score[$gClassement];
						echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';
						echo '<table style="min-width: unset"><thead><tr><th colspan="4">'.$type.' - '.$classement.'</th></tr></thead><tbody>';
						echo '<tr>';
						echo '<td><b>RANG</b></td>';
						echo '<td><b>COMPÉTITEUR</b></td>';
						echo '<td><b>ARMÉE</b></td>';
						echo '<td><b>PERFORMANCE</b></td>';
						foreach (ranking() as $grimpeur) {
							$u = $grimpeur["user"];
							echo '<tr>';
							echo '<td>'.$grimpeur["rank"].'</td>';
							echo '<td>'.$nomGrimpeur[$u].'</td>';
							echo '<td>'.$armeGrimpeur[$u].'</td>';
							echo '<td>'.performance($type, $s[$u]).'</td>';
							echo '</tr>';
						}
						echo '</tbody></table>';
						echo '</div>';
					}
				}
		break;
	case 9: //TSGED 2023

		

		$Equipe = [];
		echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';

		/////////////////////////////////////////////////////////////////////////////////
		/////// CRÉER LE TABLEAU DES RÉUSSITE(2)/ZONE(1)/RIEN(0) AVEC PROPAGATION ///////
		/////////////////////////////////////////////////////////////////////////////////

		$Voie_validées=[];
		$int = 0;
		$query = "SELECT `Voies`.`Id` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Difficulté' ORDER BY `Voies`.`Cotation`";
		$result = $dbh->query($query);
		while ($Voie = $result->fetchObject()) {
			$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
			$result1 = $dbh->query($query);
			while ($Grimpeur = $result1->fetchObject()) {
				$query = "SELECT COUNT(*) AS `Réussi` FROM `Essais` WHERE `Réussite` IS NULL AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
				if ($dbh->query($query)->fetchObject()->Réussi > 0) {
					$Voie_validées[$int][$Grimpeur->Id] = 2;
				} 
				else {
					$query = "SELECT COUNT(*) AS `Zone` FROM `Essais` WHERE `Zones`=1 AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
					if ($dbh->query($query)->fetchObject()->Zone > 0) {
						$Voie_validées[$int][$Grimpeur->Id] = 1;
					}
					else{
						$Voie_validées[$int][$Grimpeur->Id] = 0;
					}
				}
			} 
			$int++;
		}
		//If propagation --- Ici, codé pour propagation ssi 3 voies consécutives validées
		if (true) {
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				$i = $int-3;
				$v = false;
				while ($i >= 0) {
					if ($Voie_validées[$i+2][$Grimpeur] == 2 and $Voie_validées[$i+1][$Grimpeur] == 2 and $Voie_validées[$i][$Grimpeur] == 2) {
						$v = true;
					}
					if ($v) {
						$Voie_validées[$i][$Grimpeur] = 2;
					}
					$i--;
				}
			}
		}
		$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Voie'";
		$result = $dbh->query($query);
		$Score_Voie = [];
		/*while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Prise') {
				$query = "SELECT `Essais`.`Utilisateur` AS `Id`, SUM(`Nb_Points`) AS `Points` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Prises` ON `Tournois_Voies_Prises`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Prises`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Prises`.`Id` = `Essais`.`Prises_Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$query .= " GROUP BY `Essais`.`Utilisateur`";
				$result1 = $dbh->query($query);
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Grimpeur->Points;
				}
			} else {
				if ($Voie->Evaluation == 'Zone') {
					$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
					$result1 = $dbh->query($query);
					while($Zone = $result1->fetchObject()) {
						//On vérifie que la zone est qualifiée
						$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
						if ($Nombre > 0) {
							$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
							if ($Voie->Nb_Essais_Evalués != null) {
								$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
							}
							$result2 = $dbh->query($query);
							$Points = round(1000/$result2->rowCount());
							while($Grimpeur = $result2->fetchObject()) {
								$Score_Voie[$Grimpeur->Id] += $Points;
							}
						}
					}
				}
				$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
				if ($Voie->Nb_Essais_Evalués != null) {
					$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
				}
				$result1 = $dbh->query($query);
				$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0 ;
				while($Grimpeur = $result1->fetchObject()) {
					$Score_Voie[$Grimpeur->Id] += $Points;
				}
			}
		}*/


		/////////////////////////////////////////////////////////////////////////////////
		/////////////////////////// CALCUL DES POINTS PAR VOIE //////////////////////////		# 1000/(nb de grimpeur TOP) si réussi et 1000/(nb de grimpeur TOP+ZONE) si zone
		/////////////////////////////////////////////////////////////////////////////////  

		foreach (array_keys($Voie_validées) as $Voie) {
			$SommeTop = 0;
			$SommeZone = 0;
			
			foreach (array_values($Voie_validées[$Voie]) as $Grimpeur) {
				if ($Grimpeur == 2){
					$SommeTop += 1;
					$SommeZone += 1;
				}
				if ($Grimpeur == 1){
					$SommeZone += 1;
				}
			}
			
			$PointsTop = $SommeTop > 0 ? round(1000/$SommeTop) : 0;
			$PointsZone = $SommeZone > 0 ? round(1000/$SommeZone) : 0;
			foreach (array_keys($Voie_validées[0]) as $Grimpeur) {
				if ($Voie_validées[$Voie][$Grimpeur] == 2) {
					$Score_Voie[$Grimpeur] += $PointsTop;
				}
				elseif ($Voie_validées[$Voie][$Grimpeur] == 1) {
					$Score_Voie[$Grimpeur] += $PointsZone;
				}
			}
		}




		/////////////////////////////////////////////////////////////////////////////////
		/////////////////////// AFFICHAGE DU CLASSEMENT INDIVIDUEL //////////////////////		# Pour égalité, classement par voie la plus dure réussie, puis nb d'essais et tps sur la voie
		/////////////////////////////////////////////////////////////////////////////////

		foreach ($Score_Voie as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) AS `Num_Essai` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Difficulté' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Num_Essai`, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			echo is_null($Classement);
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Num_Essai' =>$Classement->Num_Essai , 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
		}
		array_multisort(array_column($Score_Voie, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Voie, 'Cotation'),SORT_DESC,SORT_STRING,array_column($Score_Voie, 'Num_Essai'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Voie);
		echo '<table style="height: fit-content;min-width: unset;"><thead><tr><th colspan="3">Classement</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Voie as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Voie[$i-1]['Points'] == $Infos['Points'] and $Score_Voie[$i-1]['Cotation'] == $Infos['Cotation'] and $Score_Voie[$i-1]['Num_Essai'] == $Infos['Num_Essai'] and $Score_Voie[$i-1]['Secondes'] == $Infos['Secondes']) {
				$Code .= '<td>'.$Score_Voie[$i-1]['Rang'].'</td>';
				$Score_Voie[$i]['Rang'] = $Score_Voie[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Voie[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' en '.$Infos['Secondes'].'s)</td>';
			$Code .= '</tr>';
			$Equipe['Voie'][$Grimpeur->Equipe] = array_merge((array)$Equipe['Voie'][$Grimpeur->Equipe], array($Score_Voie[$i]['Rang']));
		}
		foreach ($Equipe['Voie'] as $Eq => $Rangs) {
			arsort($Rangs);
			if (isset($Rangs[2])){
				$Equipe['Voie'][$Eq] = array($Rangs[0]+$Rangs[1]+$Rangs[2], $Rangs[2]);
			} else {
				$Equipe['Voie'][$Eq] = array('Non classé', 0, 0);
			}
		}
		asort($Equipe['Voie']);
		$count = 1;
		foreach ($Equipe['Voie'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>Rang cumulé : '.strval($Points[0]).'</td>';
			echo '</tr>';
			$count++;
		}
		echo '</tbody></table>';

		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Individuel</th></tr></thead><tbody>';
		echo $Code;
		echo '</tbody></table>';




		////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////// BLOC /////////////////////////////////////		
		////////////////////////////////////////////////////////////////////////////////

		/*$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Bloc'";
		$result = $dbh->query($query);
		$Score_Bloc = [];
		while($Voie = $result->fetchObject()) {
			if ($Voie->Evaluation == 'Zone') {
				$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
				$result1 = $dbh->query($query);
				while($Zone = $result1->fetchObject()) {
					//On vérifie que la zone est qualifiée
					$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
					if ($Nombre > 0) {
						$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
						if ($Voie->Nb_Essais_Evalués != null) {
							$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
						}
						$result2 = $dbh->query($query);
						$Points = round(1000/$result2->rowCount());
						while($Grimpeur = $result2->fetchObject()) {
							$Score_Bloc[$Grimpeur->Id] += $Points;
						}
					}
				}
			}
			$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `Réussite` IS NULL";
			if ($Voie->Nb_Essais_Evalués != null) {
				$query .= " AND (SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = ".$Voie->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = `Essais`.`Utilisateur` AND `E`.`Date` < `Essais`.`Date`) < ".$Voie->Nb_Essais_Evalués;
			}
			$result1 = $dbh->query($query);
			$Points = $result1->rowCount() > 0 ? round(1000/$result1->rowCount()) : 0;
			while($Grimpeur = $result1->fetchObject()) {
				$Score_Bloc[$Grimpeur->Id] += $Points;
			}
		}


		$Bloc_validées=[];
		$int = 0;
		$query = "SELECT `Voies`.`Id` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` WHERE `Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies`.`Type` = 'Bloc' ORDER BY `Voies`.`Cotation`";
		$result = $dbh->query($query);
		while ($Voie = $result->fetchObject()) {
			$query="SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Grimpeur'";
			$result1 = $dbh->query($query);
			while ($Grimpeur = $result1->fetchObject()) {
				$query = "SELECT COUNT(*) AS `Réussi` FROM `Essais` WHERE `Réussite` IS NULL AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
				if ($dbh->query($query)->fetchObject()->Réussi > 0) {
					$Bloc_validées[$int][$Grimpeur->Id] = 2;
				} 
				else {
					$query = "SELECT COUNT(*) AS `Zone` FROM `Essais` WHERE `Zones`=1 AND `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Utilisateur` = ".$Grimpeur->Id;
					if ($dbh->query($query)->fetchObject()->Zone > 0) {
						$Bloc_validées[$int][$Grimpeur->Id] = 1;
					}
					else{
						$Bloc_validées[$int][$Grimpeur->Id] = 0;
					}
				}
			} 
			$int++;
		}

		$Score_Bloc = [];

		foreach (array_keys($Bloc_validées) as $Voie) {

			$SommeTop = 0;
			$SommeZone = 0;

			
			foreach (array_values($Bloc_validées[$Voie]) as $Grimpeur) {
				if ($Grimpeur == 2){
					$SommeTop += 1;
					$SommeZone += 1;
				}
				if ($Grimpeur == 1){
					$SommeZone += 1;
				}
			}
			
		
			$PointsTop = $SommeTop > 0 ? round(1000/$SommeTop) : 0;
			$PointsZone = $SommeZone > 0 ? round(500/$SommeZone) : 0;
			foreach (array_keys($Bloc_validées[0]) as $Grimpeur) {
				if ($Bloc_validées[$Voie][$Grimpeur] == 2) {
					$Score_Bloc[$Grimpeur] += $PointsTop;
				}
				elseif ($Bloc_validées[$Voie][$Grimpeur] == 1) {
					$Score_Bloc[$Grimpeur] += $PointsZone;
				}
			}
		}



		foreach ($Score_Bloc as $Id => &$Points) {
			$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure`, (SELECT COUNT(*) FROM `Essais` AS `E` LEFT OUTER JOIN `Tournois_Voies` AS `T` ON `T`.`Voie` = `E`.`Voie` AND `T`.`Tournoi` = `E`.`Tournoi` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `T`.`Type` = 'Bloc') AS `Nb_Essais_Total` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Bloc' AND `Essais`.`Evalué` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Evalué` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
			$result = $dbh->query($query);
			$Classement = $result->fetchObject();
			$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure, 'Nb_Essais_Total' =>$Classement->Nb_Essais_Total];
		}
		array_multisort(array_column($Score_Bloc, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Bloc, 'Nb_Essais_Total'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Cotation'),SORT_ASC,SORT_NUMERIC,array_column($Score_Bloc, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Bloc);
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Bloc</th></tr></thead><tbody>';
		$Code = '';
		foreach ($Score_Bloc as $i => $Infos) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Infos['Grimpeur']]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Score_Bloc[$i-1]['Points'] == $Infos['Points'] and $Score_Bloc[$i-1]['Nb_Essais_Total'] == $Infos['Nb_Essais_Total']) {
				$Code .= '<td>'.$Score_Bloc[$i-1]['Rang'].'</td>';
				$Score_Bloc[$i]['Rang'] = $Score_Bloc[$i-1]['Rang'];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Score_Bloc[$i]['Rang'] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Infos['Points'].' points<br>('.$Infos['Cotation'].' avec '.$Infos['Nb_Essais_Total'].' essais)</td>';
			$Code .= '</tr>';
			$Equipe['Bloc'][$Grimpeur->Equipe] += $Infos['Points'];
		}
		/*$Score_Bloc[0]['Rang'] = 2;
		$Score_Bloc[2]['Rang'] = 3;
		arsort($Equipe['Bloc']);
		$count = 1;
		foreach ($Equipe['Bloc'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points.' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		$Total = [];
		$Classement_Voie = array_combine(array_column($Score_Voie, 'Grimpeur'), array_column($Score_Voie, 'Rang'));
		$Classement_Vitesse = array_combine(array_column($Score_Vitesse, 'Grimpeur'), array_column($Score_Vitesse, 'Rang'));
		$Classement_Bloc = array_combine(array_column($Score_Bloc, 'Grimpeur'), array_column($Score_Bloc, 'Rang'));
		foreach(array_unique(array_merge(array_column($Score_Voie, 'Grimpeur'),array_column($Score_Vitesse, 'Grimpeur'),array_column($Score_Bloc, 'Grimpeur'))) as $Utilisateur) {
			$Total[$Utilisateur] = 1;
			if (isset($Classement_Voie[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Voie[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Voie)+1;
			}
			if (isset($Classement_Vitesse[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Vitesse[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Vitesse)+1;
			}
			if (isset($Classement_Bloc[$Utilisateur])) {
				$Total[$Utilisateur] *= $Classement_Bloc[$Utilisateur];
			} else {
				$Total[$Utilisateur] *= count($Classement_Bloc)+1;
			}
		}
		asort($Total);
		$Total = [array_keys($Total),array_values($Total)];
		echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Général</th></tr></thead><tbody>';
		$count = 1;
		$Code = '';
		for ($i = 0; $i < count($Total[0]); $i++) {
			$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
			$result = $dbh->prepare($query);
			$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $Total[0][$i]]);
			$Grimpeur = $result->fetchObject();
			$Code .= '<tr>';
			if ($i > 0 and $Total[1][$i-1] == $Total[1][$i]) {
				$Code .= '<td>'.$Total[2][$i-1].'</td>';
				$Total[2][$i] = $Total[2][$i-1];
			} else {
				$Code .= '<td>'.strval($i+1).'</td>';
				$Total[2][$i] = $i+1;
			}
			$Code .= '<td>'.$Grimpeur->Dossard.' - '.$Grimpeur->Nom;
			$Code .= '<br>'.$Grimpeur->Genre;
			if ($Grimpeur->Equipe != null) {
				$Code .= ' ('.$Grimpeur->Equipe.')';
			}
			$Code .= '</td>';
			$Code .= '<td>'.$Total[1][$i].'</td>';
			$Code .= '</tr>';
			$Equipe['Général'][$Grimpeur->Equipe][1] += 1;
			if ($Equipe['Général'][$Grimpeur->Equipe][0] == null) {
				$Equipe['Général'][$Grimpeur->Equipe][0] = 1;
			}
			if ($Equipe['Général'][$Grimpeur->Equipe][1] <= 3) {
				$Equipe['Général'][$Grimpeur->Equipe][0] *= $Total[2][$i];
			}
			$count++;
		}
		array_multisort(array_column($Equipe['Général'], '0'),SORT_ASC,SORT_NUMERIC,$Equipe['Général']);
		$count = 1;
		foreach ($Equipe['Général'] as $Eq => $Points) {
			echo '<tr>';
			echo '<td>'.$count.'</td>';
			echo '<td>'.$Eq.'</td>';
			echo '<td>'.$Points[0].' points</td>';
			echo '</tr>';
			$count++;
		}
		echo $Code;
		echo '</tbody></table>';

		echo '</div>';*/
		break;
	case 10: // CFM 2023
		// ================================================================================
		// === NOM DES COMPETITEURS ET SCORE ==============================================
		// ================================================================================
		
			$nomGrimpeur = array();
			$armeGrimpeur = array();

			// Il faut que les keys des arrays scoreD et scoreB soient les mêmes que la case Phase renseignée dans la création des voies
			$scoreD = array(
				"Qualification - Homme" => array(),
				"Qualification - Femme" => array(),
			);
			$scoreB = array(
				"Qualification - Homme" => array(),
				"Qualification - Femme" => array(),
			);

			$MAX_PRISES = 1000;
		
		// ================================================================================
		// === DIFFICULTÉ =================================================================
		// ================================================================================
		
			// fonction pour calculer le score d'un grimpeur
			function quantifierScoreD($e) {
				global $MAX_PRISES;
				$v = 5;
				if ($e["cotation"] == "6a") $v = 0;
				else if ($e["cotation"] == "6b") $v = 1;
				else if ($e["cotation"] == "6c") $v = 2;
				else if ($e["cotation"] == "7a") $v = 3;
				else if ($e["cotation"] == "7b") $v = 4;
				$p = $e["prise"];
				$p = is_null($p)? $MAX_PRISES - 1 : intval(10 * floatval($p));
				$t = $e["temps"];
				$h = $i = $s = $u = 0;
				if (!is_null($t)) {
					$h = intval(substr($t, 0, 2));
					$i = 59 - intval(substr($t, 3, 2));
					$s = 59 - intval(substr($t, 6, 2));
					$u = 999 - intval(substr($t, 9, 3));
				}
				$x = ((($v * $MAX_PRISES + $p) * 60 + $i) * 60 + $s) * 1000 + $u;
				return $x;
			}

			// récupérer les infos depuis la DB pour les essais en difficulté
			$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 10 AND `Tournois_Voies`.`Tournoi` = 10 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Difficulté' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 10 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur` ORDER BY `Voies`.`Cotation` ";
			$result = $dbh->query($query);
			// mettre les données dans les bons tableaux
			while ($e = $result->fetchObject()) {
				$u = $e->Utilisateur;
				$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom." (".$e->Dossard.")";
				$dossardGrimpeur[$u] = $e->Dossard;
				$armeGrimpeur[$u] = $e->Equipe;
				$classements = array($e->Phase." - ".$e->Genre);
				foreach ($classements as $classement) if (array_key_exists($classement, $scoreD)) {
					$valide = false;
					$p = $scoreD[$classement][$u];
					// D'après ce que j'ai compris, une voie non réussie n'est valide QUE si la voie de cotation juste inférieure a déjà été réussie
					if (is_null($e->Cotation) || is_null($e->Réussite) || $e->Cotation == "6a") 				$valide = true;
					if ($e->Cotation == "6b" && !is_null($p) && $p["cotation"] == "6a" && is_null($p["prise"])) $valide = true;
					if ($e->Cotation == "6c" && !is_null($p) && $p["cotation"] == "6b" && is_null($p["prise"])) $valide = true;
					if ($e->Cotation == "7a" && !is_null($p) && $p["cotation"] == "6c" && is_null($p["prise"])) $valide = true;
					if ($e->Cotation == "7b" && !is_null($p) && $p["cotation"] == "7a" && is_null($p["prise"])) $valide = true;
					if ($valide) {
						$scoreD[$classement][$u] = array(
							"cotation" => $e->Cotation,
							"prise" => $e->Réussite,
							"temps" => $e->Chrono
						);
					}
				}
			}
			// calculer les scores à partir des essais
			foreach ($scoreD as $classement => &$s) {
				$s = array_map('quantifierScoreD', $s);
			}
		
		// ================================================================================
		// === BLOC =======================================================================
		// ================================================================================
		
			// hashmap contenant le nombre de grimpeurs qui ont réussi une zone / bloc
			$zone = array(
				"Qualification - Homme" => array(),
				"Qualification - Femme" => array()
			);
			$top = array(
				"Qualification - Homme" => array(),
				"Qualification - Femme" => array()
			);

			// fonction pour calculer les scores à partir des essais d'un grimpeur
			function quantifierScoreB($classement, $es) {
				global $zone;
				global $top;
				$x = 0;
				foreach ($es as $e) {
					$v = $e->Voie;
					$t = $top[$classement][$v];

					// 1000 points sont répartis entre ceux qui ont toppés
					// 500 points sont répartis entre ceux qui ont seulement atteint une zone cependant le nombre de points est divisé 
					if (is_null($e->Réussite)) {
						$x += 1000 / $t;
					}
					elseif ($e->Zones == 1) {
						$z = $zone[$classement][$v];
						$x += 500 / ($z+$t);
					}
				}
				return $x;
			}

			// récupérer les infos depuis la DB pour les essais en bloc
			$query = "SELECT * FROM `Essais` JOIN `Tournois_Voies` ON `Essais`.`Tournoi` = 10 AND `Tournois_Voies`.`Tournoi` = 10 AND `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Type` = 'Bloc' JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` JOIN `Tournois_Utilisateurs` ON `Tournois_Utilisateurs`.`Utilisateur` = `Essais`.`Utilisateur` AND `Tournois_Utilisateurs`.`Tournoi` = 10 AND `Tournois_Utilisateurs`.`Type` = 'Grimpeur' JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur`";
			$result = $dbh->query($query);

			// mettre les données dans les bons tableaux
			while ($e = $result->fetchObject()) {
				$u = $e->Utilisateur;
				$v = $e->Voie;
				$nomGrimpeur[$u] = $e->Prénom." ".$e->Nom." (".$e->Dossard.")";
				$armeGrimpeur[$u] = $e->Equipe;
				$c = $e->Catégorie;
				$classements = array($e->Phase." - ".$e->Catégorie, $e->Phase." - ".$e->Genre);			
				foreach ($classements as $classement) if (array_key_exists($classement, $scoreB)) {
					if (is_null($e->Réussite))
						$top[$classement][$v] = $top[$classement][$v] + 1;
					elseif ($e->Zones == 1)
						$zone[$classement][$v] = $zone[$classement][$v] + 1;
					if (!array_key_exists($u, $scoreB[$classement]))
						$scoreB[$classement][$u] = array();
					array_push($scoreB[$classement][$u], $e);
				}
			}
			// calculer les scores à partir des essais
			foreach ($scoreB as $classement => &$s) {
				$s = array_map(
					function ($es) use ($classement) {return quantifierScoreB($classement, $es);},
					$s
				);
			}

		// ================================================================================
		// === CALCUL DU CLASSEMENT ET AFFICHAGE ==========================================
		// ================================================================================
				$rating = NULL;

				// définit si une performance est meilleure qu'une autre
				function ltRank($u, $v) {
					global $rating;
					return $rating[$v] - $rating[$u];
				}
		
				// map rang => grimpeur en fonction du rating
				function ranking() {
					global $rating;
					$rank = array();
					$cnt = 0;
					foreach ($rating as $u => $s) {
						$rank[$cnt] = $u;
						$cnt++;
					}
					usort($rank, 'ltRank');
					$rk = 0;
					$last = NULL;
					foreach ($rank as $cnt => &$u) {
						if ($cnt == 0 || ltRank($last, $u) < 0)
							$rk = $cnt + 1;
						$last = $u;
						$u = array("user" => $u, "rank" => $rk);
					}
					return $rank;
				}
		
				// transforme un score en performance affichable
				function performance($type, $x) {
					global $MAX_PRISES;
					if ($type == "Difficulté") {
						$u = 999 - $x % 1000; $x = intdiv($x, 1000);
						$s = 59 - $x % 60; $x = intdiv($x, 60);
						$i = 59 - $x % 60; $x = intdiv($x, 60);
						$p = $x % $MAX_PRISES; $x = intdiv($x, $MAX_PRISES);
						$v = $x;
						if ($p == $MAX_PRISES - 1) {
							$p = "TOP";
						}
						else {
							// Si le nombre de prises est demi entier, cela veut dire que la dernière prise à été tenue
							// on note ça avec un "+" après le nombre de prises
							if ($p % 10 == 5) $p = floor($p / 10).'+ prises';
							else              $p = floor($p / 10).' prises';
						}
						if ($v == 0) return "6a, ".$p;
						else if ($v == 1) return "6b, ".$p;
						else if ($v == 2) return "6c, ".$p;
						else if ($v == 3) return "7a, ".$p;
						else if ($v == 4) return "7b, ".$p;
						else return $p;
					}
					else {
						return intval($x);
					}
				}
		
				// affichage des classements en difficulté en en bloc
				$scores = array("Difficulté" => $scoreD, "Bloc" => $scoreB);
				$ranks  = array("Difficulté" => array(), "Bloc" => array());
				foreach ($scores as $type => &$score) {
					foreach ($score as $classement => &$s) {
						echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; font-size:0.75em">';
						echo '<table style="min-width: unset"><thead><tr><th colspan="4">'.$type.' - '.$classement.'</th></tr></thead><tbody>';
						echo '<tr>';
						echo '<td><b>RANG</b></td>';
						echo '<td><b>COMPÉTITEUR</b></td>';
						echo '<td><b>ARMÉE</b></td>';
						echo '<td><b>PERFORMANCE</b></td>';

						$rating = $score[$classement];
						$ranks[$type][$classement] = ranking();
						foreach ($ranks[$type][$classement] as $grimpeur) {
							$u = $grimpeur["user"];
							echo '<tr>';
							echo '<td>'.$grimpeur["rank"].'</td>';
							echo '<td>'.$nomGrimpeur[$u].'</td>';
							echo '<td>'.$armeGrimpeur[$u].'</td>';
							echo '<td>'.performance($type, $s[$u]).'</td>';
							echo '</tr>';
						}
						echo '</tbody></table>';
						echo '</div>';
					}
				}
		break;
}

require('Pied de page.inc.php');
require('Bas.inc.php');
?>
