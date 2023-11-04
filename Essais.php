<?php


require('Inclus/Haut.inc.php');
session_write_close();

$Page_Ouv = 'Essais';

if (password_verify('mdp',$Utilisateur_Con->Mot_de_passe)) {
	header('Location: Administration?Action=modifier&mdp=1');
}

require('Inclus/Entête.inc.php');
switch ($_REQUEST['Action']) {
	case 'créer':
		$query = "SELECT * FROM `Voies` WHERE `Id` = :Id";
		$result = $dbh->prepare($query);
		$result->execute(['Id' => $_REQUEST['Voie']]);
		if ($result->rowCount() > 0) {
			$Voie = $result->fetchObject();
			echo '<h2>Ajouter un essai</h2>';
			echo afficheVoieFromId($Voie->Id);
			if ($Voie->Description != null) {
				echo '<h5>Description</h5>';
				echo '<p>'.$Voie->Description.'</p>';
			}
			if ($Voie->Photo != null) {
				echo '<p style="text-align: center;"><img src="Medias/'.$Voie->Photo.'" style="max-width: 100%; max-height: 20rem;"></p>';
			} 
			if ($Voie->Vidéo != null) {
				echo '<p style="text-align: center;"><video src="Medias/'.$Voie->Vidéo.'" controls style="max-width: 100%; max-height: 20rem;"></p>';
			}
			echo '<form method="post">';
			echo '<input type="hidden" name="Action" value="insérer">';
			echo '<input type="hidden" name="Voie" value='.$Voie->Id.'>';
			echo '<h4>Mode</h4>';
			echo '<p><select name="Mode">';
			$query = "SELECT DISTINCT * FROM `Modes` ORDER BY `Id`";
			$result = $dbh->query($query);
			while ($Mode = $result->fetchObject()) {
				echo '<option value="'.$Mode->Id.'">'.ucfirst($Mode->Nom).'</option>';
			}
			echo '</select></p>';
			echo '<h4>Réussite</h4>';
			echo '<p><input type="checkbox" name="Réussite" id="Réussite" checked onchange="if(this.checked) {document.getElementById(\'Nb_Dégaines\').style.display = \'none\';} else {document.getElementById(\'Nb_Dégaines\').style.display = \'block\';}"><label for="Réussite">Voie terminée</label></p>';
			echo '<div id="Nb_Dégaines" style="display: none;">';
			echo '<h4>Nombre de dégaines montées (ou nombre de prises touchées en bloc)</h4>';
			echo '<p><input type="number" name="Nb_Dégaines" value="0" ></p>';
			echo '</div>';
			echo '<h4>Nombre de pauses</h4>';
			echo '<p><input type="number" name="Nb_Pauses" value="0" ></p>';
			echo '<h4>Nombre de chute</h4>';
			echo '<p><input type="number" name="Nb_Chutes" value="0" ></p>';
			echo '<p><input type="submit" value="Valider"></p>';
			$result = essaisDeGrimpeurSurVoie($Voie->Id, $Utilisateur_Con->Id);
			if ($result->rowCount() > 0) {
				echo '<h3>Essais précédents sur cette voie</h3>';
				echo '<ul>';
				while ($Essai = $result->fetchObject()) {
					echo '<li style="';
					if ($Essai->Réussite != null) {
						echo 'color: red;';
					} elseif ($Essai->Nb_Chutes > 0) {
						echo 'color: orange;';
					} elseif ($Essai->Nb_Pauses > 0) {
						echo 'color: blue;';
					} else {
						echo 'color: green;';
					}
					echo '">le '.date_create($Essai->Date)->format('d/m/Y à H:i').'&nbsp;:';
					if ($Essai->Réussite == null) {
						echo ' réussie';
					} else {
						
						echo ' réussie à '.round(100*$Essai->Réussite/$Essai->Nb_Dégaines,1).'%';
					}
					echo ' en '.Modes()[$Essai->Mode];
					if ($Essai->Nb_Chutes > 0) {
						echo ' avec '.$Essai->Nb_Chutes.' chutes';
					} else {
						echo ' sans chutes';
					}
					if ($Essai->Nb_Pauses > 0) {
						echo ' et avec '.$Essai->Nb_Pauses.' pauses';
					} else {
						echo ' et sans pauses';
					}
					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo '<p>Cette voie n’existe pas&nbsp;!</p>';
		}
		break;
	default:
		echo '<form method="POST">';
		echo '<h2>';
		echo '<select name="Mur" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
		$query = "SELECT `Murs`.`Id`, `Murs`.`Nom` FROM `Murs` LEFT OUTER JOIN `Mur_Utilisateurs` ON `Mur_Utilisateurs`.`Mur` = `Murs`.`Id` WHERE `Mur_Utilisateurs`.`Utilisateur` = ".$Utilisateur_Con->Id;
		$result = $dbh->query($query);
		while ($mur = $result->fetchObject()) {
			echo '<option value="'.$mur->Id.'"';
			if ($Mur->Id == $mur->Id) {
				echo ' selected';
			}
			echo '>'.$mur->Nom.'</option>';
		}
		echo '</select> - '.$Utilisateur_Con->Prénom.' '.$Utilisateur_Con->Nom.'</h2>';
		echo '</form>';
		switch ($_REQUEST['Action']) {
			case 'insérer':
				if ($_REQUEST['Réussite'] == 'on') {
					$Réussite = null;
				} else {
					$Réussite = $_REQUEST['Nb_Dégaines'];
				}
				$query = "SELECT * FROM `Voies` WHERE `Id` = :Id";
				$result = $dbh->prepare($query);
				$result->execute(['Id' => $_REQUEST['Voie']]);
				if ($result->rowCount() > 0) {
					$Voie = $result->fetchObject();
					$query = "SELECT * FROM `Modes` WHERE `Id` = :Id";
					$result = $dbh->prepare($query);
					$result->execute(['Id' => $_REQUEST['Mode']]);
					if ($result->rowCount() > 0) {
						$Mode = $result->fetchObject();
						$query = $dbh->prepare("INSERT INTO `Essais` (`Utilisateur`, `Voie`, `Date`, `Mode`, `Nb_Pauses`, `Nb_Chutes`, `Réussite`,`Entrée_Utilisateur`) VALUES (:Utilisateur , :Voie, NOW(), :Mode, :Nb_Pauses, :NbChutes, :Reussite, :Utilisateur)");
						$data = [
							'Utilisateur' => $Utilisateur_Con->Id,
							'Voie' => $Voie->Id,
							'Mode' => $Mode->Id,
							'Nb_Pauses' => $_REQUEST['Nb_Pauses'],
							'NbChutes' => $_REQUEST['Nb_Chutes'],
							'Reussite' => $Réussite
							];
						if ($query->execute($data)) {
							if ($Réussite == null) {
								echo '<p>Bravo pour cette '.$Voie->Cotation.' en '.$Mode->Nom.'&nbsp;!</p>';
							} else {
								echo '<p>Ta tentative a bien été enregistrée pour cette '.$Voie->Cotation.' en '.$Mode->Nom.', tu feras mieux la prochaine fois&nbsp;!</p>';
							}
							echo '<script>history.pushState({foo:"Essais"}, "Essais", "Essais");</script>';
						} else {
							echo '<p>Une erreur s’est produite lors de l’enregistrement.</p>';
						}
					} else {
						echo '<p>Ce mode de grimpe n’existe pas&nbsp;!</p>';
					}
				} else {
					echo '<p>Cette voie n’existe pas&nbsp;!</p>';
				}
				break;
		}
		
		if ($Mur->Id != null) {
		
    		$Emplacements = [];
    		$Cotations = [];
    		$query = "SELECT * FROM `Emplacements` WHERE `Mur` = ".$Mur->Id." AND `Ordre` IS NOT NULL ORDER BY `Ordre`";
    		$result = $dbh->query($query);
    		$query = "SELECT `Voies`.`Id`, `Voies`.`Cotation`, REGEXP_SUBSTR(`Voies`.`Cotation`, '^[0-9]+') AS `Cot`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2` FROM `Voies` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Emplacement` = :Emplacement AND `Active` = 1 ORDER BY `Cotation`";
    		$result1 = $dbh->prepare($query);
    		while ($Emplacement = $result->fetchObject()) {
    			$result1->execute(['Emplacement' => $Emplacement->Id]);
    			$Voies = [];
    			while ($Voie = $result1->fetch()) {
    				$Voies[$Voie['Cot']][] = $Voie;
    			}
    			foreach ($Voies as $Cot => $V) {
    				if (count($V) > $Cotations[$Cot]) {
    					$Cotations[$Cot] = count($V);
    				}
    			}
    			$Emplacements[] = [$Emplacement, $Voies];
    		}
    		ksort($Cotations);
    		echo '<div class="table"><table style="font-size: 1.5rem"><thead><tr>';
    		foreach ($Emplacements as $Emplacement) {
    			echo '<th>'.$Emplacement[0]->Nom.'</th>';
    		}
    		echo '</tr></thead><tbody>';
    		foreach ($Cotations as $Cot => $Num) {
    			for ($i = 0; $i < $Num; $i++) {
    				echo '<tr>';
    				foreach ($Emplacements as $Emplacement) {
    					if (isset($Emplacement[1][$Cot][$i])) {
    						echo '<td style="';
    						if ($Emplacement[1][$Cot][$i]['Couleur_2'] != null) {
    							echo 'background-image: linear-gradient(to bottom right, #'.$Emplacement[1][$Cot][$i]['Couleur_1'].' 25%, #'.$Emplacement[1][$Cot][$i]['Couleur_2'].' 75%);';
    						} else {
    							echo 'background-color: #'.$Emplacement[1][$Cot][$i]['Couleur_1'].';';
    						}
    						echo ' color: '.couleur_text('#'.$Emplacement[1][$Cot][$i]['Couleur_1']).'; cursor: pointer;" onclick="document.getElementById(\'Voie_'.$Emplacement[1][$Cot][$i]['Id'].'\').submit();">';
    						echo '<form method="post" id="Voie_'.$Emplacement[1][$Cot][$i]['Id'].'">';
    						echo '<input type="hidden" name="Action" value="créer">';
    						echo '<input type="hidden" name="Voie" value='.$Emplacement[1][$Cot][$i]['Id'].'>';
    						echo '</form>';
    						echo $Emplacement[1][$Cot][$i]['Cotation'];
    						$query = "SELECT * FROM (SELECT `Essais`.`Date`, `Essais`.`Nb_Chutes`, `Essais`.`Nb_Pauses`, `Essais`.`Réussite`, `Modes`.`Icône` FROM `Essais` LEFT OUTER JOIN `Modes` ON `Modes`.`Id` = `Essais`.`Mode` WHERE `Utilisateur` = ".$Utilisateur_Con->Id." AND `Voie` = ".$Emplacement[1][$Cot][$i]['Id']." ORDER BY `Date` DESC LIMIT 5) AS `Table` ORDER BY `Date`";
    						$result = $dbh->query($query);
    						if ($result->rowCount() > 0) {
    							echo '<br><span style="white-space: nowrap; font-size: 0.7rem;">';
    							while ($Essai = $result->fetchObject()) {
    								echo ' <span style="text-shadow: #FFFFFF 1px 1px, #FFFFFF -1px 1px, #FFFFFF -1px -1px, #FFFFFF 1px -1px;';
    								if ($Essai->Réussite != null) {
    									echo 'color: red;';
    								} elseif ($Essai->Nb_Chutes > 0) {
    									echo 'color: orange;';
    								} elseif ($Essai->Nb_Pauses > 0) {
    									echo 'color: blue;';
    								} else {
    									echo 'color: green;';
    								}
    								echo '" title="'.date_create($Essai->Date)->format('d/m/Y à H:i').'">'.$Essai->Icône.'</span>';
    							}
    							echo '</span>';
    						}
    						echo '</td>';
    					} else {
    						echo '<td style="background-color: #FFFFFF;"></td>';
    					}
    				}
    				echo '</tr>';
    			}
    		}
    		if ($Mur->Photo != null) {
    			echo '<tr style="background-color: #FFFFFF;"><td colspan="'.count($Emplacements).'" style="padding: 0;"><img src="Medias/'.$Mur->Photo.'" style="width: 100%"></td></tr>';
    		}
    		echo '</tbody></table></div>';
    		echo '<p>';
    		$query = "SELECT `Icône`, `Nom` FROM `Modes` ORDER BY `Id`";
    		$result = $dbh->query($query);
    		while($Mode = $result->fetchObject()) {
    			echo $Mode->Icône.'&nbsp;: '.$Mode->Nom.'<br>';
    		}
    		echo '<span style="color: green;">Réussite</span> – <span style="color: blue;">Réussite avec pauses</span> – <span style="color: orange;">Réussite avec chutes</span> – <span style="color: red;">Voie non terminée</span>';
		    echo '</p>';
		}
}

require('Inclus/Pied de page.inc.php');
require('Inclus/Bas.inc.php');

?>