<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Emplacements';
require('Entête.inc.php');

if (droits('A',$Mur->Id)) {
	switch ($_REQUEST['Action']) {
		case 'créer':
			//Formulaire de nouvel emplacement
			echo '<h2>Saissisez votre nouvel emplacement</h2>';
			echo '<form method="post">';
			echo '<input type="hidden" name="Action" value="insérer">';
			echo '<h4>Nom</h4>';
			echo '<p><input type="text" name="Nom" required></p>';
			echo '<h4>Ordre</h4>';
			echo '<p><input type="number" name="Ordre" min=1 step=1></p>';
			echo '<h4>Nombre de dégaines</h4>';
			echo '<p><input type="number" name="Nb_Dégaines" min=1 step=1 required></p>';
			echo '<h4>Inclinaison</h4>';
			echo '<p><select name="Inclinaison">';
			$query = "SELECT `Id`, `Nom` FROM `Inclinaison` ORDER BY `Ordre`";
			$result = $dbh->query($query);
			while ($Inclinaison = $result->fetchObject()) {
				echo '<option value="' . $Inclinaison->Id . '">' . $Inclinaison->Nom . '</option>';
			}
			echo '</select></p>';
			echo '<p><input type="submit" value="Valider"></p>';
			echo '</form>';
			break;
		case 'modifier':
			if ($_REQUEST['Id'] != '') {
				$result = $dbh->prepare("SELECT * FROM `Emplacements` WHERE `Emplacements`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
				$result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
				if ($result->rowCount() > 0) {
					$Emplacement = $result->fetchObject();
					echo '<h2>Modification de l’emplacement '.$Emplacement->Nom.'</h2>';
					echo '<form method="post">';
					echo '<input type="hidden" name="Action" value="éditer">';
					echo '<input type="hidden" name="Id" value="'.$Emplacement->Id.'">';
					echo '<h4>Nom</h4>';
					echo '<p><input type="text" name="Nom" value="'.$Emplacement->Nom.'" required></p>';
					echo '<h4>Ordre</h4>';
					echo '<p><input type="number" name="Ordre" value="'.$Emplacement->Ordre.'" min=1 step=1></p>';
					echo '<h4>Nombre de dégaines</h4>';
					echo '<p><input type="number" name="Nb_Dégaines" value="'.$Emplacement->Nb_Dégaines.'" min=1 step=1 required></p>';
					echo '<h4>Inclinaison</h4>';
					echo '<p><select name="Inclinaison">';
					$query = "SELECT `Id`, `Nom` FROM `Inclinaison` ORDER BY `Ordre`";
					$result = $dbh->query($query);
					while ($Inclinaison = $result->fetchObject()) {
						echo '<option value="' . $Inclinaison->Id . '"';
						if ($Emplacement->Inclinaison == $Inclinaison->Id) {
							echo ' selected';
						}
						echo '>' . $Inclinaison->Nom . '</option>';
					}
					echo '</select></p>';
					echo '<p><input type="submit" value="Valider"></p>';
					echo '</form>';
				} else {
					echo '<p>Cet emplacement n’existe pas ou ne fait pas partie de ce mur.</p>';
				}
			} else {
				echo '<p>Vous devez spécifier une emplacement.</p>';
			}
			break;
		case 'supprimer':
			if ($_REQUEST['Id'] != '') {
				$result = $dbh->prepare("SELECT `Emplacements`.`Id`, (SELECT COUNT(*) FROM `Voies` WHERE `Voies`.`Emplacement` = `Emplacements`.`Id`) AS `Nb_Voies` FROM `Emplacements` WHERE `Emplacements`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
				$result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
				if ($result->rowCount() > 0) {
					$Emplacement = $result->fetchObject();
					echo '<h2>Suppression de l’emplacement '.$Emplacement->Nom.'</h2>';
					echo '<form method="post">';
					echo '<input type="hidden" name="Action" value="retirer">';
					echo '<input type="hidden" name="Id" value="'.$Emplacement->Id.'">';
					if ($Emplacement->Nb_Voies > 0) {
						echo '<p>Des grimpeurs ont déjà grimpé cet emplacement. Il ne peut donc pas être supprimé. Vous pouvez uniquement le désactiver.</p>';
						echo '<p><input type="submit" value="Désactiver cet emplacement"></p>';
					} else {
						echo '<p>Êtes-vous sûr de vouloir supprimer cet emplacement&nbsp?</p>';
						echo '<p><input type="submit" value="Supprimer cet emplacement"></p>';
					}
					echo '</form>';
				} else {
					echo '<p>Cet emplacement n’existe pas ou ne fait pas partie de ce mur.</p>';
				}
			} else {
				echo '<p>Vous devez spécifier une emplacement.</p>';
			}
			break;
		default:
			echo '<form method="POST">';
			if ($_REQUEST['Vue'] == 'Ancien') {
				echo '<h2>Gestion des anciens emplacements -';
			} else {
				echo '<h2>Gestion des emplacements -';
			}
			echo '<select name="Mur" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
			$query = "SELECT `Murs`.`Id`, `Murs`.`Nom` FROM `Murs` LEFT OUTER JOIN `Mur_Utilisateurs` ON `Mur_Utilisateurs`.`Mur` = `Murs`.`Id` WHERE `Mur_Utilisateurs`.`Utilisateur` = ".$Utilisateur_Con->Id;
			$result = $dbh->query($query);
			while ($mur = $result->fetchObject()) {
				if (droits('A',$mur->Id)) {
					echo '<option value="'.$mur->Id.'"';
					if ($Mur->Id == $mur->Id) {
						echo ' selected';
					}
					echo '>'.$mur->Nom.'</option>';
				}
			}
			echo '</select></h2>';
			echo '</form>';
			switch ($_REQUEST['Action']) {
				case 'insérer':
					if ($_REQUEST['Nom'] != '' and $_REQUEST['Nb_Dégaines'] != '' and $_REQUEST['Inclinaison'] != '') {
						$result = $dbh->prepare("SELECT `Id` FROM `Inclinaison` WHERE `Id` = :Id");
						$result->execute(['Id' => $_REQUEST['Inclinaison']]);
						if ($result->rowCount() > 0) {
							$Inclinaison = $result->fetchObject();
							$query = $dbh->prepare("INSERT INTO `Emplacements`(`Mur`, `Ordre`, `Nom`, `Nb_Dégaines`, `Inclinaison`) VALUES(:Mur, :Ordre, :Nom, :Nb_Degaines, :Inclinaison)");
							if ($query->execute(array(
								'Mur' => $Mur->Id,
								'Ordre' => $_REQUEST['Ordre'] != '' ? $_REQUEST['Ordre'] : null,
								'Nom' => $_REQUEST['Nom'],
								'Nb_Degaines' => $_REQUEST['Nb_Dégaines'],
								'Inclinaison' => $Inclinaison->Id
							))) {
								echo '<p>Insertion terminée de l’emplacement '.$_REQUEST['Nom'].'</h2>';
							} else {
								echo '<p>Une erreur est survenue lors de l’ajout de cet emplacement.</p>';
							}
						} else {
							echo '<p>Cette inclinaison n’existe pas.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				case 'éditer':
					if ($_REQUEST['Id'] != '' and $_REQUEST['Nom'] != '' and $_REQUEST['Nb_Dégaines'] != '' and $_REQUEST['Inclinaison'] != '') {
						$result = $dbh->prepare("SELECT `Emplacements`.`Id` FROM `Emplacements` WHERE `Emplacements`.`Mur` = :Mur AND `Emplacements`.`Id` = :Id");
						$result->execute(['Mur' => $Mur->Id, 'Id' => $_REQUEST['Id']]);
						if ($result->rowCount() > 0) {
							$Emplacement = $result->fetchObject();
							$result = $dbh->prepare("SELECT `Id` FROM `Inclinaison` WHERE `Id` = :Id");
							$result->execute(['Id' => $_REQUEST['Inclinaison']]);
							if ($result->rowCount() > 0) {
								$Inclinaison = $result->fetchObject();
								$query = $dbh->prepare("UPDATE `Emplacements` SET `Mur`=:Mur, `Ordre`=:Ordre, `Nom`=:Nom, `Nb_Dégaines`=:Nb_Degaines, `Inclinaison`=:Inclinaison WHERE `Id`=:Id");
								if ($query->execute(array(
									'Id' => $Emplacement->Id,
									'Mur' => $Mur->Id,
									'Ordre' => $_REQUEST['Ordre'] != '' ? $_REQUEST['Ordre'] : null,
									'Nom' => $_REQUEST['Nom'],
									'Nb_Degaines' => $_REQUEST['Nb_Dégaines'],
									'Inclinaison' => $Inclinaison->Id
								))) {
									echo '<p>Modification terminée de l’emplacement '.$_REQUEST['Nom'].'</h2>';
								} else {
									echo '<p>Une erreur est survenue lors de la modification de cet emplacement.</p>';
								}
							} else {
								echo '<p>Cette inclinaison n’existe pas.</p>';
							}
						} else {
							echo '<p>Cet emplacement n’existe pas ou ne fait pas parti de votre mur.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				case 'retirer':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT `Emplacements`.`Id`, `Emplacements`.`Nom`, (SELECT COUNT(*) FROM `Voies` WHERE `Voies`.`Emplacement` = `Emplacements`.`Id`) AS `Nb_Voies` FROM `Emplacements` WHERE `Emplacements`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
						$result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
						if ($result->rowCount() > 0) {
							$Emplacement = $result->fetchObject();
							if ($Emplacement->Nb_Voies > 0) {
								$query = $dbh->prepare("UPDATE `Emplacements` SET `Ordre` = NULL WHERE `Emplacements`.`Id` = :Id");
								if ($query->execute(array('Id' => $Emplacement->Id))) {
									echo '<p>L’emplacement '.$Emplacement->Nom.' a bien été désactivé.</p>';
									$dbh->query("UPDATE `Voies` SET `Active`= 0 WHERE `Emplacement` = ".$Emplacement->Id);
								} else {
									echo '<p>Une erreur est survenue.</p>';
								}
							} else {
								$query = $dbh->prepare("DELETE FROM `Emplacements` WHERE `Emplacements`.`Id` = :Id");
								if ($query->execute(array('Id' => $Emplacement->Id))) {
									echo '<p>L’emplacement '.$Emplacement->Nom.' a bien été supprimé.</p>';
									$dbh->query("ALTER TABLE `Emplacements` auto_increment = 1");
								} else {
									echo '<p>Une erreur est survenue.</p>';
								}
							}
						} else {
							echo '<p>Cet emplacement n’existe pas ou ne fait pas parti de ce mur.</p>';
						}
					} else {
						echo '<p>Vous devez spécifier une emplacement.</p>';
					}
					break;
			}
			
			echo '<form method="post" id="créer">';
			echo '<input type="hidden" name="Action" value="créer">';
			echo '</form>';
			
			if ($_REQUEST['Vue'] == 'Ancien') {
				echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter un emplacement"> | <a href="Voies/Emplacements" title="Afficher les emplacements actifs">Emplacements actifs</a></p>';
				$ancien = 'AND `Emplacements`.`Ordre` IS NULL';
			} else {
				echo '<form method="post" id="AnciensEmplacements">';
				echo '<input type="hidden" name="Vue" value="Ancien">';
				echo '</form>';
				echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter un emplacement"> | <input type="submit" form="AnciensEmplacements" class="link" value="Anciens emplacements" title="Afficher les emplacements qui ont été retirées"></p>';
				$ancien = 'AND `Emplacements`.`Ordre` IS NOT NULL';
			}

			//compte des emplacements
			echo '<p>Nombre d’emplacements&nbsp;: '.$dbh->query("SELECT COUNT(*) AS `Nombre` FROM `Emplacements` WHERE `Mur` = ".$Mur->Id." ".$ancien)->fetchObject()->Nombre.'</p>';
			
			echo '<div class="table"><table><thead><tr><th>Nom</th>';
			if ($_REQUEST['Vue'] != 'Ancien') {
				echo '<th>Ordre</th>';
			}
			echo '<th>Nombre de dégaines</th><th>Inclinaison</th><th>Nombre de voies</th><th class="noPrint"></th>';
			echo '</tr></thead><tbody>';
			
			$result = $dbh->query("SELECT `Emplacements`.`Id`, `Emplacements`.`Ordre`, `Emplacements`.`Nom`, `Emplacements`.`Nb_Dégaines`, `Inclinaison`.`Nom` AS `Inclinaison`, (SELECT COUNT(*) FROM `Voies` WHERE `Voies`.`Emplacement` = `Emplacements`.`Id`) AS `Nb_Voies` FROM `Emplacements` LEFT OUTER JOIN `Inclinaison` ON `Inclinaison`.`Id` = `Emplacements`.`Inclinaison` WHERE `Mur` = ".$Mur->Id." ".$ancien." ORDER BY `Emplacements`.`Ordre`, `Emplacements`.`Id`");
			while ($Emplacement = $result->fetchObject()) {
				echo '<tr>';
				echo '<td>'.$Emplacement->Nom.'</td>';
				if ($_REQUEST['Vue'] != 'Ancien') {
					echo '<td>'.$Emplacement->Ordre.'</td>';
				}
				echo '<td>'.$Emplacement->Nb_Dégaines.'</td>';
				echo '<td>'.$Emplacement->Inclinaison.'</td>';
				echo '<td>'.$Emplacement->Nb_Voies.'</td>';
				echo '<td class="noPrint">';
				echo '<form method="post" class="icon">';
				echo '<input type="hidden" name="Action" value="modifier">';
				echo '<input type="hidden" name="Id" value=' . $Emplacement->Id . '>';
				echo '<input type="submit" title="Modifier cet emplacement" value="✏️">';
				echo '</form>';
				echo '<form method="post" class="icon">';
				echo '<input type="hidden" name="Action" value="supprimer">';
				echo '<input type="hidden" name="Id" value=' . $Emplacement->Id . '>';
				echo '<input type="submit" title="Supprimer cet emplacement" value="🗑️️">';
				echo '</form>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '</div>';
			break;
	} 
} else {
	echo '<form method="POST">';
	if ($_REQUEST['Vue'] == 'Ancien') {
		echo '<h2>Gestion des anciens emplacements -';
	} else {
		echo '<h2>Gestion des emplacements -';
	}
	echo '<select name="Mur" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
	$query = "SELECT `Murs`.`Id`, `Murs`.`Nom` FROM `Murs` LEFT OUTER JOIN `Mur_Utilisateurs` ON `Mur_Utilisateurs`.`Mur` = `Murs`.`Id` WHERE `Mur_Utilisateurs`.`Utilisateur` = ".$Utilisateur_Con->Id;
	$result = $dbh->query($query);
	while ($mur = $result->fetchObject()) {
		if (droits('A',$mur->Id) or $Mur->Id == $mur->Id) {
			echo '<option value="'.$mur->Id.'"';
			if ($Mur->Id == $mur->Id) {
				echo ' selected';
			}
			echo '>'.$mur->Nom.'</option>';
		}
	}
	echo '</select></h2>';
	echo '</form>';
	echo '<p>Vous n’êtes pas administrateur de ce mur.</p>';
}

require('Pied de page.inc.php');
require('Bas.inc.php');
?>