<?php
require('../Inclus/Haut.inc.php');
$Tournoi = initialiser_Tournoi();
session_write_close();
if ($_REQUEST['Actualiser'] != '') {
	header('Refresh: '.$_REQUEST['Actualiser'].';');
}
$Page_Ouv = 'Résultats - '.$Tournoi->Nom;
require('Entête.inc.php');

echo '<h2>'.$Page_Ouv.'</h2>';

$Equipe = [];
echo '<div style="display: flex; justify-content: space-around; flex-wrap: wrap; /*font-size:0.75em */">';
$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Voie'";
$result = $dbh->query($query);
$Score_Voie = [];
while($Voie = $result->fetchObject()) {
	if ($Voie->Evaluation == 'Prise') {
		$query = "SELECT `Essais`.`Utilisateur` AS `Id`, SUM(`Nb_Points`) AS `Points` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Prises` ON `Tournois_Voies_Prises`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Prises`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Prises`.`Id` = `Essais`.`Prises_Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL";
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
			$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
			$result1 = $dbh->query($query);
			while($Zone = $result1->fetchObject()) {
				//On vérifie que la zone est qualifiée
				$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
				if ($Nombre > 0) {
					$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
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
	$query="SELECT `Voies`.`Cotation`, TIME_TO_SEC(`Essais`.`Chrono`) AS `Secondes`, TIME_TO_SEC(`Essais`.`Date`) AS `Heure` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Tournois_Voies` ON `Tournois_Voies`.`Voie` = `Essais`.`Voie` AND `Tournois_Voies`.`Tournoi` = ".$Tournoi->Id." WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Utilisateur` = ".$Id." AND `Tournois_Voies`.`Type` = 'Voie' AND `Essais`.`Chrono` IS NOT NULL AND `Réussite` IS NULL AND ((SELECT COUNT(*) FROM `Essais` AS `E` WHERE `E`.`Tournoi` = ".$Tournoi->Id." AND `E`.`Voie` = `Essais`.`Voie` AND `Chrono` IS NOT NULL AND `E`.`Utilisateur` = ".$Id." AND `E`.`Date` < `Essais`.`Date`) < `Tournois_Voies`.`Nb_Essais_Evalués` OR `Tournois_Voies`.`Nb_Essais_Evalués` IS NULL) ORDER BY `Voies`.`Cotation` DESC, `Essais`.`Chrono` LIMIT 1";
	$result = $dbh->query($query);
	$Classement = $result->fetchObject();
	$Points = ['Grimpeur' => $Id, 'Points' => $Points, 'Cotation' => $Classement->Cotation, 'Secondes' =>$Classement->Secondes, 'Date' =>$Classement->Heure];
}
array_multisort(array_column($Score_Voie, 'Points'),SORT_DESC,SORT_NUMERIC,array_column($Score_Voie, 'Cotation'),SORT_ASC,SORT_STRING,array_column($Score_Voie, 'Secondes'),SORT_ASC,SORT_NUMERIC,array_column($Score_Voie, 'Date'),SORT_ASC,SORT_NUMERIC,$Score_Voie);
echo '<table style="min-width: unset"><thead><tr><th colspan="3">Classement Voie</th></tr></thead><tbody>';
$Code = '';
foreach ($Score_Voie as $i => $Infos) {
	$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossart`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
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
	$Code .= '<td>'.$Grimpeur->Dossart.' - '.$Grimpeur->Nom;
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

$query = "SELECT `Voie` AS `Id`, `Evaluation`, `Nb_Essais_Evalués` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Type` = 'Vitesse'";
$result = $dbh->query($query);
$Score_Vitesse = [];
while($Voie = $result->fetchObject()) {
	$query = "SELECT `Essais`.`Utilisateur` AS `Id`, MIN(TIME_TO_SEC(`Chrono`)) AS `Secondes`, COUNT(*) AS `Nb_Essais` FROM `Essais` LEFT OUTER JOIN `Tournois_Voies_Prises` ON `Tournois_Voies_Prises`.`Tournoi` = ".$Tournoi->Id." AND `Tournois_Voies_Prises`.`Voie` = ".$Voie->Id." AND `Tournois_Voies_Prises`.`Id` = `Essais`.`Prises_Zones` WHERE `Essais`.`Tournoi` = ".$Tournoi->Id." AND `Essais`.`Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND `Réussite` IS NULL";
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
	$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossart`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
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
	$Code .= '<td>'.$Grimpeur->Dossart.' - '.$Grimpeur->Nom;
	$Code .= '<br>'.$Grimpeur->Genre;
	if ($Grimpeur->Equipe != null) {
		$Code .= ' ('.$Grimpeur->Equipe.')';
	}
	$Code .= '</td>';
	$Code .= '<td>'.date_create_from_format('U.u',sprintf("%0.2f",$Infos['Temps']))->format('i:s.v').'<br>('.$Infos['Nb_Essais'].' essais)</td>';
	$Code .= '</tr>';
	$Equipe['Vitesse'][$Grimpeur->Equipe] += $Infos['Temps'];
}
asort($Equipe['Vitesse']);
$count = 1;
foreach ($Equipe['Vitesse'] as $Eq => $Secondes) {
	echo '<tr>';
	echo '<td>'.$count.'</td>';
	echo '<td>'.$Eq.'</td>';
	echo '<td>'.date_create_from_format('U.u',sprintf("%0.2f",$Secondes))->format('H:i:s.v').'</td>';
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
		$query = "SELECT * FROM `Tournois_Voies_Prises` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id;
		$result1 = $dbh->query($query);
		while($Zone = $result1->fetchObject()) {
			//On vérifie que la zone est qualifiée
			$Nombre = $dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND (CONCAT(',',`Prises_Zones`,',') NOT LIKE '%,".$Zone->Id.",%' OR `Prises_Zones` IS NULL)")->fetchObject()->Nombre;
			if ($Nombre > 0) {
				$query = "SELECT DISTINCT `Utilisateur` AS `Id` FROM `Essais` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." AND `Chrono` IS NOT NULL AND CONCAT(',',`Prises_Zones`,',') LIKE '%,".$Zone->Id.",%'";
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
	$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossart`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
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
	$Code .= '<td>'.$Grimpeur->Dossart.' - '.$Grimpeur->Nom;
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
	$query = "SELECT CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossart`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur";
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
	echo '<td>'.$Grimpeur->Dossart.' - '.$Grimpeur->Nom;
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

echo '<p class="noPrint"><a href="Tournois/Resultats?Actualiser=5">Actualiser ces résultats toutes les 5 secondes</a></p>';

require('Pied de page.inc.php');
require('Bas.inc.php');
?>