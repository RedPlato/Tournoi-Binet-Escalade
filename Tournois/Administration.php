<?php

require('../Inclus/Haut.inc.php');
session_write_close();
require('autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
$Page_Ouv = 'Administration - '.$Tournoi->Nom;
require('Entête.inc.php');

echo '<form method="POST">';
echo '<h2>Administration - ';
echo '<select name="Tournoi" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
$query = "SELECT DISTINCT `Id`, `Nom` FROM (SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Tournois` ON `Tournois`.`Id` = `Tournois_Utilisateurs`.`Tournoi` WHERE `Tournois_Utilisateurs`.`Utilisateur` = ".$Utilisateur_Con->Id." AND `Tournois_Utilisateurs`.`Type` = 'Administrateur' UNION SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois` WHERE `Tournois`.`Id` = ".$Tournoi->Id.") AS `Select` ORDER BY `Date` DESC";
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

echo '<form method="post" id="Utilisateurs">';
echo '<input type="hidden" name="Vue" value="Utilisateurs">';
echo '</form>';
echo '<form method="post" id="Voies">';
echo '<input type="hidden" name="Vue" value="Voies">';
echo '</form>';
echo '<p class="noPrint"><a href="Tournois/Administration">Paramètres du tournoi</a> | <input type="submit" form="Utilisateurs" class="link" value="Gestion des utilisateurs"> | <input type="submit" form="Voies" class="link" value="Gestion des voies"></p>';
					
$query = "SELECT COUNT(*) AS `Administrateur` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Utilisateur` = ".$Utilisateur_Con->Id." AND `Type` = 'Administrateur'";
$result = $dbh->query($query);
if ($result->fetchObject()->Administrateur > 0) {
	switch($_REQUEST['Vue']) {
		case 'Utilisateurs':
			switch ($_REQUEST['Action']) {
				case 'créer':
					if ($_REQUEST['Type'] == 'excel') {
						echo '<h3>Importer des utilisateurs</h3>';
						echo '<form method="post" enctype="multipart/form-data">';
						echo '<input type="hidden" name="Vue" value="Utilisateurs">';
						echo '<input type="hidden" name="Action" value="insérer">';
						echo '<input type="hidden" name="Type" value="excel">';
						echo '<p>Téléchargez <a href="Tournois/Importer utilisateurs tournoi.xlsx" target="_blank">ce fichier Excel</a>, remplissez-le, puis chargez-le ci-dessous.</p>';
						echo '<h4>Fichier</h4>';
						echo '<p><input type="file" name="Fichier" required></p>';
						echo '<input type="submit" value="Valider">';
					} else {
						echo '<h3>Nouvel utilisateur</h3>';
						echo '<form method="post">';
						echo '<input type="hidden" name="Vue" value="Utilisateurs">';
						echo '<input type="hidden" name="Action" value="insérer">';
						echo '<h4>Nom</h4>';
						echo '<p><input type="text" name="Nom" autocomplete="off" required></p>';
						echo '<h4>Prénom</h4>';
						echo '<p><input type="text" name="Prénom" autocomplete="off" required></p>';
						echo '<h4>Genre</h4>';
						echo '<p><select name="Genre" autocomplete="off">';
						foreach (['Homme' => 'Homme', 'Femme' => 'Femme'] as $Clé => $Valeur) {
							echo '<option value="'.$Clé.'">'.$Valeur.'</option>';
						}
						echo '</select></p>';
						echo '<h4>Adresse électronique</h4>';
						echo '<p><input type="email" name="Adresse_électronique" autocomplete="off"></p>';
						echo '<h4>Identifiant</h4>';
						echo '<p><input type="text" name="Identifiant" autocomplete="off"></p>';
						echo '<h4>Dossard</h4>';
						echo '<p><input type="text" name="Dossard" autocomplete="off"></p>';
						echo '<h4>Catégorie</h4>';
						echo '<p><input type="text" name="Catégorie" autocomplete="off"></p>';
						echo '<h4>Equipe</h4>';
						echo '<p><input type="text" name="Equipe" list="Equipe"  autocomplete="off"></p>';
						echo '<datalist id="Equipe">';
						$query = "SELECT DISTINCT `Equipe` AS `Nom` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Equipe` IS NOT NULL ORDER BY `Nom`";
						$result = $dbh->query($query);
						while ($Equipe = $result->fetchObject()) {
							echo '<option>' . $Equipe->Nom . '</option>';
						}
						echo '</datalist>';
						echo '<h4>Type</h4>';
						echo '<p><select name="Type">';
						foreach (['Grimpeur' => 'Grimpeur', 'Juge' => 'Juge', 'Administrateur' => 'Administrateur'] as $Clé => $Valeur) {
							echo '<option value="'.$Clé.'">'.$Valeur.'</option>';
						}
						echo '</select></p>';
						echo '<h4>Mot de passe</h4>';
						echo '<p><input type="text" name="Mot_de_passe" autocomplete="off"></p>';
						echo '<input type="submit" value="Valider">';
						echo '</form>';
					}
					break;
				case 'modifier':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT `Utilisateurs`.`Id`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`, `Utilisateurs`.`Genre`, `Utilisateurs`.`Adresse_électronique`, `Utilisateurs`.`Identifiant`, `Tournois_Utilisateurs`.`Type`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Catégorie`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournois_Utilisateurs`.`Utilisateur` = :Id AND `Tournois_Utilisateurs`.`Tournoi` = :Tournoi");
						$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
						if ($result->rowCount() > 0) {
							$Utilisateur = $result->fetchObject();
							echo '<h3>Modification de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.'</h3>';
							echo '<form method="post">';
							echo '<input type="hidden" name="Vue" value="Utilisateurs">';
							echo '<input type="hidden" name="Action" value="éditer">';
							echo '<input type="hidden" name="Id" value="'.$Utilisateur->Id.'">';
							echo '<h4>Nom</h4>';
							echo '<p><input type="text" name="Nom" value="'.$Utilisateur->Nom.'" autocomplete="off" required></p>';
							echo '<h4>Prénom</h4>';
							echo '<p><input type="text" name="Prénom" value="'.$Utilisateur->Prénom.'" autocomplete="off" required></p>';
							echo '<h4>Genre</h4>';
							echo '<p><select name="Genre" autocomplete="off">';
							foreach (['Homme' => 'Homme', 'Femme' => 'Femme'] as $Clé => $Valeur) {
								echo '<option value="'.$Clé.'"';
								if ($Clé == $Utilisateur->Genre) {
									echo ' selected';
								}
								echo '>'.$Valeur.'</option>';
							}
							echo '</select></p>';
							echo '<h4>Adresse électronique</h4>';
							echo '<p><input type="email" name="Adresse_électronique" value="'.$Utilisateur->Adresse_électronique.'" autocomplete="off"></p>';
							echo '<h4>Identifiant</h4>';
							echo '<p><input type="text" name="Identifiant" value="'.$Utilisateur->Identifiant.'" autocomplete="off"></p>';
							echo '<h4>Dossard</h4>';
							echo '<p><input type="text" name="Dossard" value="'.$Utilisateur->Dossard.'" autocomplete="off"></p>';
							echo '<h4>Catégorie</h4>';
							echo '<p><input type="text" name="Catégorie" value="'.$Utilisateur->Catégorie.'" autocomplete="off"></p>';
							echo '<h4>Equipe</h4>';
							echo '<p><input type="text" name="Equipe" value="'.$Utilisateur->Equipe.'" list="Equipe"  autocomplete="off"></p>';
							echo '<datalist id="Equipe">';
							$query = "SELECT DISTINCT `Equipe` AS `Nom` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Equipe` IS NOT NULL ORDER BY `Nom`";
							$result = $dbh->query($query);
							while ($Equipe = $result->fetchObject()) {
								echo '<option>' . $Equipe->Nom . '</option>';
							}
							echo '</datalist>';
							echo '<h4>Type</h4>';
							echo '<p><select name="Type">';
							foreach (['Grimpeur' => 'Grimpeur', 'Juge' => 'Juge', 'Administrateur' => 'Administrateur'] as $Clé => $Valeur) {
								echo '<option value="'.$Clé.'"';
								if ($Clé == $Utilisateur->Type) {
									echo ' selected';
								}
								echo '>'.$Valeur.'</option>';
							}
							echo '</select></p>';
							echo '<h4>Mot de passe</h4>';
							echo '<p><input type="text" name="Mot_de_passe" placeholder="Nouveau mot de passe pour cet utilisateur (optionel)" autocomplete="off"></p>';
							echo '<input type="submit" value="Valider">';
							echo '</form>';
						} else {
							echo '<p>Cet utilisateur n’existe pas ou ne fait pas parti de votre tournoi.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				case 'supprimer':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id`, ((SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur` OR `Essais`.`Entrée_Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Mur_Utilisateurs` WHERE `Mur_Utilisateurs`.`Utilisateur`  = `Tournois_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Tournois_Utilisateurs` AS `T` WHERE `T`.`Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur` AND `T`.`Tournoi` != `Tournois_Utilisateurs`.`Tournoi`)) AS `Nb_Essais` FROM `Tournois_Utilisateurs` WHERE `Tournois_Utilisateurs`.`Utilisateur` = :Id AND `Tournois_Utilisateurs`.`Tournoi` = :Tournoi");
						$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
						if ($result->rowCount() > 0) {
							$Utilisateur = $result->fetchObject();
							$User = getUserFromId($Utilisateur->Id);
							echo '<h2>Suppression de '.$User->Prénom.' '.$User->Nom.'</h2>';
							if ($Utilisateur->Id != $Utilisateur_Con->Id) {
								echo '<form method="post">';
								echo '<input type="hidden" name="Vue" value="Utilisateurs">';
								echo '<input type="hidden" name="Action" value="retirer">';
								echo '<input type="hidden" name="Id" value="'.$Utilisateur->Id.'">';
								if ($Utilisateur->Nb_Essais > 0) {
									echo '<p>Ce grimpeur a déjà monté des voies. Il ne peut donc pas être supprimé. Vous pouvez uniquement le retirer de la liste de vos utilisateur.</p>';
									echo '<p><input type="submit" value="Retirer cet utilisateur"></p>';
								} else {
									echo '<p>Êtes-vous sûr de vouloir supprimer cet utilisateur.</p>';
									echo '<p><input type="submit" value="Supprimer cet utilisateur"></p>';
								}
								echo '</form>';
							} else {
								echo '<p>Vous ne pouvez pas vous supprimer vous-même.</p>';
							}
						} else {
							echo '<p>Cet utilisateur n’existe pas ou ne fait pas partie de ce tournoi.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				default:
					echo '<h3>Gestion des utilisateurs</h3>';
					switch($_REQUEST['Action']) {
						case 'insérer':
							if ($_REQUEST['Type'] == 'excel') {
								if (is_uploaded_file($_FILES['Fichier']['tmp_name'])) {
									$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['Fichier']['tmp_name']);
									$worksheet = $spreadsheet->getActiveSheet();
									$Données = $worksheet->toArray();
									$Entêtes = array_flip($Données[0]);
									array_shift($Données);
									if (isset($Entêtes['Nom'], $Entêtes['Prénom'], $Entêtes['Genre'], $Entêtes['Adresse électronique'], $Entêtes['Identifiant'], $Entêtes['Mot de passe'], $Entêtes['Dossard'], $Entêtes['Catégorie'], $Entêtes['Equipe'], $Entêtes['Type'])) {
										echo '<ul>';
										$query = "SELECT `Id`, `Nom`, `Prénom`, (SELECT COUNT(*) FROM `Tournois_Utilisateurs` WHERE `Utilisateur` = `Id` AND `Tournoi` = :Tournoi) AS `Membre` FROM `Utilisateurs` WHERE (`Adresse_électronique` = :Adresse AND `Adresse_électronique` IS NOT NULL) OR (`Identifiant` = :Identifiant AND `Identifiant` IS NOT NULL)";
										$result = $dbh->prepare($query);
										foreach ($Données as $i => $Ligne) {
											// On parcourt les lignes du fichier donc les utilisateurs à ajouter

											$Ligne = traiter_inputs($Ligne);
											if ($Ligne[$Entêtes['Nom']] != null and $Ligne[$Entêtes['Prénom']] != null and $Ligne[$Entêtes['Genre']] != null and $Ligne[$Entêtes['Type']] != null) {
												$result->execute(['Adresse' => $Ligne[$Entêtes['Adresse électronique']], 'Identifiant' => $Ligne[$Entêtes['Identifiant']], 'Tournoi' => $Tournoi->Id]);
												if ($result->rowCount() == 0) {
													// Si l’utilisateur n’existe pas, on le crée

													$query = "INSERT INTO `Utilisateurs`(`Nom`, `Prénom`, `Genre`, `Adresse_électronique`, `Identifiant`, `Mot_de_passe`) VALUES (:Nom, :Prenom, :Genre, :Adresse, :Identifiant, :MotDePasse)";
													$result1 = $dbh->prepare($query);
													if ($result1->execute(['Nom' => $Ligne[$Entêtes['Nom']], 'Prenom' => $Ligne[$Entêtes['Prénom']], 'Genre' => $Ligne[$Entêtes['Genre']], 'Adresse' => $Ligne[$Entêtes['Adresse électronique']] != '' ? $Ligne[$Entêtes['Adresse électronique']] : null, 'Identifiant' => $Ligne[$Entêtes['Identifiant']] != '' ? $Ligne[$Entêtes['Identifiant']] : null, 'MotDePasse' => $Ligne[$Entêtes['Mot de passe']] != '' ? password_hash($Ligne[$Entêtes['Mot de passe']], PASSWORD_DEFAULT) : null])) {
														$Id = $dbh->lastInsertId();
														$query = "INSERT INTO `Tournois_Utilisateurs`(`Tournoi`, `Utilisateur`, `Type`, `Dossard`, `Catégorie`, `Equipe`) VALUES (:Tournoi, :Utilisateur, :Type, :Dossard, :Categorie, :Equipe)";
														$result2 = $dbh->prepare($query);
														$Utilisateur = getUserFromId($Id);
														if ($result2->execute(['Tournoi' => $Tournoi->Id, 'Utilisateur' => $Id, 'Type' => $Ligne[$Entêtes['Type']], 'Dossard' => $Ligne[$Entêtes['Dossard']] != '' ? $Ligne[$Entêtes['Dossard']] : null, 'Categorie' => $Ligne[$Entêtes['Catégorie']] != '' ? $Ligne[$Entêtes['Catégorie']] : null, 'Equipe' => $Ligne[$Entêtes['Equipe']] != '' ? $Ligne[$Entêtes['Equipe']] : null])) {
															echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été ajouté à la liste des utilisateurs de votre tournoi.</li>';
														} else {
															echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été créé, mais n’a pu être ajouté à votre tournoi.</li>';
														}
													} else {
														echo '<li>Impossible de créer l’utilisateur '.$Ligne[$Entêtes['Prénom']].' '.$Ligne[$Entêtes['Nom']].'.</li>';
													}
												} else {
													// Si l’utilisateur existe, on vérifie qu’il n’est pas déjà dans le tournoi

													$Utilisateur = $result->fetchObject();
													if ($Utilisateur->Membre == 0) {
														$query = "INSERT INTO `Tournois_Utilisateurs`(`Tournoi`, `Utilisateur`, `Type`, `Dossard`, `Catégorie`, `Equipe`) VALUES (:Tournoi, :Utilisateur, :Type, :Dossard, :Categorie :Equipe)";
														$result2 = $dbh->prepare($query);
														if ($result2->execute(['Tournoi' => $Tournoi->Id, 'Utilisateur' => $Utilisateur->Id, 'Type' => $Ligne[$Entêtes['Type']], 'Dossard' => $Ligne[$Entêtes['Dossard']] != '' ? $Ligne[$Entêtes['Dossard']] : null, 'Categorie' => $Ligne[$Entêtes['Catégorie']] != '' ? $Ligne[$Entêtes['Catégorie']] : null, 'Equipe' => $Ligne[$Entêtes['Equipe']] != '' ? $Ligne[$Entêtes['Equipe']] : null])) {
															echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' existe déjà, ligne n°'.($i+2).', et il a bien été ajouté à la liste des utilisateurs de votre tournoi.</li>';
														} else {
															echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' existe déjà, ligne n°'.($i+2).', mais n’a pu être ajouté à votre tournoi.</li>';
														}
													} else {
														echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' existe déjà, ligne n°'.($i+2).', et il est déjà grimpeur sur votre Tournoi</li>';
													}
												}
											} else {
												echo '<li>Donnée incomplète ligne n°'.($i+2).'</li>';
											}
										}
										echo '</ul>';
									} else {
										echo '<p>Le fichier envoyé ne contient pas toutes les colonnes attendues.</p>';
									}
								} else {
									echo '<p>Vous n’avez pas chargé de fichier.</p>';
								}
							} else {
								if ($_REQUEST['Nom'] != '' and $_REQUEST['Prénom'] != '' and $_REQUEST['Genre'] != '' and $_REQUEST['Type'] != '') {
									$query = "SELECT * FROM `Utilisateurs` WHERE `Adresse_électronique` = :Adresse OR (`Identifiant` IS NOT NULL AND `Identifiant` = :Identifiant)";
									$result = $dbh->prepare($query);
									$result->execute(['Adresse' => $_REQUEST['Adresse_électronique'], 'Identifiant' => $_REQUEST['Identifiant']]);
									if ($result->rowCount() == 0) {
										$query = "INSERT INTO `Utilisateurs`(`Nom`, `Prénom`, `Genre`, `Adresse_électronique`, `Identifiant`, `Mot_de_passe`) VALUES (:Nom, :Prenom, :Genre, :Adresse, :Identifiant, :MotDePasse)";
										$result = $dbh->prepare($query);
										if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Genre' => $_REQUEST['Genre'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'] != '' ? $_REQUEST['Identifiant'] : null, 'MotDePasse' => $_REQUEST['Mot_de_passe'] != '' ? password_hash($_REQUEST['Mot_de_passe'], PASSWORD_DEFAULT) : null])) {
											$Id = $dbh->lastInsertId();
											$query = "INSERT INTO `Tournois_Utilisateurs`(`Tournoi`, `Utilisateur`, `Type`, `Dossard`, `Catégorie`, `Equipe`) VALUES (:Tournoi, :Utilisateur, :Type, :Dossard, :Categorie, :Equipe)";
											$result = $dbh->prepare($query);
											$Utilisateur = getUserFromId($Id);
											if ($result->execute(['Tournoi' => $Tournoi->Id, 'Utilisateur' => $Id, 'Type' => $_REQUEST['Type'], 'Dossard' => $_REQUEST['Dossard'] != '' ? $_REQUEST['Dossard'] : null, 'Categorie' => $_REQUEST['Catégorie'] != '' ? $_REQUEST['Catégorie'] : null, 'Equipe' => $_REQUEST['Equipe'] != '' ? $_REQUEST['Equipe'] : null])) {
												echo '<p>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été ajouté à la liste des utilisateurs de votre tournoi.</p>';
											} else {
												echo '<p>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été créé, mais n’a pu être ajouté à votre tournoi.</p>';
											}
										} else {
											echo '<p>Impossible de créer cet utilisateur.</p>';
										}
									} else {
										echo '<p>Un utilisateur avec cet identifiant ou avec cette adresse électronique existe déjà.</p>';
									}
								} else {
									echo '<p>Les données saisies sont incomplètes.</p>';
								}
							}
							break;
						case 'éditer':
							if ($_REQUEST['Id'] != '' and $_REQUEST['Nom'] != '' and $_REQUEST['Prénom'] != '' and $_REQUEST['Genre'] != '' and $_REQUEST['Type'] != '') {
								$result = $dbh->prepare("SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id` FROM `Tournois_Utilisateurs` WHERE `Tournois_Utilisateurs`.`Utilisateur` = :Id AND `Tournois_Utilisateurs`.`Tournoi` = :Tournoi");
								$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
								if ($result->rowCount() > 0) {
									$Utilisateur = getUserFromId($result->fetchObject()->Id);
									if ($_REQUEST['Mot_de_passe'] != '') {
										$query = "UPDATE `Utilisateurs` SET `Nom`=:Nom,`Prénom`=:Prenom,`Genre`=:Genre,`Adresse_électronique`=:Adresse,`Identifiant`=:Identifiant,`Mot_de_passe`=:MotDePasse WHERE `Id` = :Id";
										$result = $dbh->prepare($query);
										if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Genre' => $_REQUEST['Genre'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'] != '' ? $_REQUEST['Identifiant'] : null, 'MotDePasse' => $_REQUEST['Mot_de_passe'] != '' ? password_hash($_REQUEST['Mot_de_passe'], PASSWORD_DEFAULT) : null, 'Id' => $Utilisateur->Id])) {
											$query = "UPDATE `Tournois_Utilisateurs` SET `Type`=:Type, `Dossard`=:Dossard, `Catégorie`=:Categorie, `Equipe`=:Equipe WHERE `Tournoi`=:Tournoi AND `Utilisateur`=:Utilisateur";
											$result = $dbh->prepare($query);
											if ($result->execute(['Tournoi' => $Tournoi->Id, 'Utilisateur' => $Utilisateur->Id, 'Type' => $_REQUEST['Type'], 'Dossard' => $_REQUEST['Dossard'] != '' ? $_REQUEST['Dossard'] : null, 'Categorie' => $_REQUEST['Catégorie'] != '' ? $_REQUEST['Catégorie'] : null, 'Equipe' => $_REQUEST['Equipe'] != '' ? $_REQUEST['Equipe'] : null])) {
												echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour.</p>';
											} else {
												echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour, mais pas son equipe ni ses droits.</p>';
											}
										} else {
											echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' n’ont pas été mises à jour.</p>';
										}
									} else {
										$query = "UPDATE `Utilisateurs` SET `Nom`=:Nom,`Prénom`=:Prenom,`Genre`=:Genre,`Adresse_électronique`=:Adresse,`Identifiant`=:Identifiant WHERE `Id` = :Id";
										$result = $dbh->prepare($query);
										if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Genre' => $_REQUEST['Genre'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'] != '' ? $_REQUEST['Identifiant'] : null, 'Id' => $Utilisateur->Id])) {
											$query = "UPDATE `Tournois_Utilisateurs` SET `Type`=:Type, `Dossard`=:Dossard, `Catégorie`=:Categorie, `Equipe`=:Equipe WHERE `Tournoi`=:Tournoi AND `Utilisateur`=:Utilisateur";
											$result = $dbh->prepare($query);
											if ($result->execute(['Tournoi' => $Tournoi->Id, 'Utilisateur' => $Utilisateur->Id, 'Type' => $_REQUEST['Type'], 'Dossard' => $_REQUEST['Dossard'] != '' ? $_REQUEST['Dossard'] : null, 'Categorie' => $_REQUEST['Catégorie'] != '' ? $_REQUEST['Catégorie'] : null, 'Equipe' => $_REQUEST['Equipe'] != '' ? $_REQUEST['Equipe'] : null])) {
												echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour.</p>';
											} else {
												echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour, mais pas son equipe ni ses droits.</p>';
											}
										} else {
											echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' n’ont pas été mises à jour.</p>';
										}
									}
								} else {
									echo '<p>Cet utilisateur n’existe pas ou ne fait pas parti de votre tournoi.</p>';
								}
							} else {
								echo '<p>Les données saisies sont incomplètes.</p>';
							}
							break;
						case 'retirer':
							if ($_REQUEST['Id'] != '') {
								$result = $dbh->prepare("SELECT `Tournois_Utilisateurs`.`Utilisateur` AS `Id`, ((SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur` OR `Essais`.`Entrée_Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Mur_Utilisateurs` WHERE `Mur_Utilisateurs`.`Utilisateur`  = `Tournois_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Tournois_Utilisateurs` AS `T` WHERE `T`.`Utilisateur` = `Tournois_Utilisateurs`.`Utilisateur` AND `T`.`Tournoi` != `Tournois_Utilisateurs`.`Tournoi`)) AS `Nb_Essais` FROM `Tournois_Utilisateurs` WHERE `Tournois_Utilisateurs`.`Utilisateur` = :Id AND `Tournois_Utilisateurs`.`Tournoi` = :Tournoi");
								$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
								if ($result->rowCount() > 0) {
									$Utilisateur = $result->fetchObject();
									$User = getUserFromId($Utilisateur->Id);
									echo '<h2>Suppression de '.$User->Prénom.' '.$User->Nom.'</h2>';
									if ($Utilisateur->Id != $Utilisateur_Con->Id) {
										$query = "DELETE FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Utilisateur` = ".$Utilisateur->Id;
										if ($dbh->query($query)) {
											$User = getUserFromId($Utilisateur->Id);
											if ($Utilisateur->Nb_Essais > 0) {
												echo '<p>'.$User->Prénom.' '.$User->Nom.' a bien été retiré de la liste de vos utilisateurs.</p>';
											} else {
												if ($dbh->query("DELETE FROM `Utilisateurs` WHERE `Id` = ".$Utilisateur->Id)) {
													echo '<p>'.$User->Prénom.' '.$User->Nom.' a bien été supprimé.</p>';
													$dbh->query("ALTER TABLE `Utilisateurs` auto_increment = 1");
												} else {
													echo '<p>'.$User->Prénom.' '.$User->Nom.' a bien été retiré de la liste de vos utilisateurs, mais n’a pu être supprimé.</p>';
												}
											}
										} else {
											echo '<p>Une erreur est survenue, cet utilisateur ne peut pas être retiré de la liste de vos utilisateurs.</p>';
										}
								} else {
										echo '<p>Vous ne pouvez pas vous supprimer vous-même.</p>';
									}
								} else {
									echo '<p>Cet utilisateur n’existe pas ou ne fait pas partie de ce tournoi.</p>';
								}
							} else {
								echo '<p>Les données saisies ne sont pas complètes.</p>';
							}
							break;
					}

					//Liste des utilisateurs
					echo '<form method="post" id="créer">';
					echo '<input type="hidden" name="Vue" value="Utilisateurs">';
					echo '<input type="hidden" name="Action" value="créer">';
					echo '</form>';
					echo '<form method="post" id="créerExcel">';
					echo '<input type="hidden" name="Vue" value="Utilisateurs">';
					echo '<input type="hidden" name="Action" value="créer">';
					echo '<input type="hidden" name="Type" value="excel">';
					echo '</form>';
					echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter un utilisateur"> | <input type="submit" form="créerExcel" class="link" value="Importer des utilisateurs"></p>';
					$result = $dbh->query("SELECT DISTINCT `Equipe` AS `Nom` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Equipe` IS NOT NULL ORDER BY `Nom`");
					if ($result->rowCount() > 0) {
						echo '<form method="post">';
						echo '<input type="hidden" name="Vue" value="Utilisateurs">';
						echo '<p><select name="Equipe" onchange="this.parentNode.parentNode.submit()">';
						echo '<option value="">Tous les équipes</option>';
						$result = $dbh->query("SELECT DISTINCT `Equipe` AS `Nom` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = ".$Tournoi->Id." AND `Equipe` IS NOT NULL ORDER BY `Nom`");
						while ($Equipe = $result->fetchObject()) {
							echo '<option value="'.$Equipe->Nom.'"';
							if ($_REQUEST['Equipe'] == $Equipe->Nom) {
								echo ' selected';
							}
							echo '>'.$Equipe->Nom.'</option>';
						}
						echo '</select>';
						echo '</form>';
					}
					echo '<div class="table"><table><thead><tr><th>Nom</th><th>Prénom</th><th>Genre</th><th>Adresse électronique</th><th>Identifiant</th><th>Type</th><th>Equipe</th><th>Dossard</th><th>Catégorie</th><th class="noPrint"></th></tr></thead><tbody>';
					if (isset($_REQUEST['Equipe']) and $_REQUEST['Equipe'] != '') {
						$result = $dbh->prepare("SELECT `Utilisateurs`.`Id`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`, `Utilisateurs`.`Genre`, `Utilisateurs`.`Adresse_électronique`, `Utilisateurs`.`Identifiant`, `Tournois_Utilisateurs`.`Equipe`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Catégorie`, `Tournois_Utilisateurs`.`Type` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournois_Utilisateurs`.`Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Equipe` = :Equipe ORDER BY CAST(`Tournois_Utilisateurs`.`Dossard` AS int), `Tournois_Utilisateurs`.`Equipe`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`");
						$result->execute(array('Tournoi' => $Tournoi->Id, 'Equipe' => $_REQUEST['Equipe']));
					} else {
						$result = $dbh->prepare("SELECT `Utilisateurs`.`Id`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`, `Utilisateurs`.`Genre`, `Utilisateurs`.`Adresse_électronique`, `Utilisateurs`.`Identifiant`, `Tournois_Utilisateurs`.`Equipe`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Catégorie`, `Tournois_Utilisateurs`.`Type` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournois_Utilisateurs`.`Tournoi` = :Tournoi ORDER BY  CAST(`Tournois_Utilisateurs`.`Dossard` AS int), `Tournois_Utilisateurs`.`Equipe`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`");
						$result->execute(array('Tournoi' => $Tournoi->Id));
					}
					while ($Utilisateur = $result->fetchObject()) {
						echo '<tr>';
						echo '<td>'.$Utilisateur->Nom.'</td>';
						echo '<td>'.$Utilisateur->Prénom.'</td>';
						echo '<td>'.$Utilisateur->Genre.'</td>';
						echo '<td>'.$Utilisateur->Adresse_électronique.'</td>';
						echo '<td>'.$Utilisateur->Identifiant.'</td>';
						echo '<td>'.$Utilisateur->Type.'</td>';
						echo '<td>'.$Utilisateur->Equipe.'</td>';
						echo '<td>'.$Utilisateur->Dossard.'</td>';
						echo '<td>'.$Utilisateur->Catégorie.'</td>';
						echo '<td class="noPrint">';
						echo '<form method="post" class="icon">';
						echo '<input type="hidden" name="Vue" value="Utilisateurs">';
						echo '<input type="hidden" name="Action" value="modifier">';
						echo '<input type="hidden" name="Id" value=' . $Utilisateur->Id . '>';
						echo '<input type="submit" title="Modifier cette voie" value="✏️">';
						echo '</form>';
						echo '<form method="post" class="icon">';
						echo '<input type="hidden" name="Vue" value="Utilisateurs">';
						echo '<input type="hidden" name="Action" value="supprimer">';
						echo '<input type="hidden" name="Id" value=' . $Utilisateur->Id . '>';
						echo '<input type="submit" title="Supprimer cette voie" value="🗑️">';
						echo '</form>';
						echo '</td>';
						echo '</tr>';
					}
					echo '</tbody></table>';
					echo '</div>';
			}
		break;
		case 'Voies':
			switch ($_REQUEST['Action']) {
				case 'créer':
					echo '<h3>Nouvelle voie</h3>';
					echo '<form method="post">';
					echo '<input type="hidden" name="Vue" value="Voies">';
					echo '<input type="hidden" name="Action" value="insérer">';
					echo '<h4>Voie</h4>';
					echo '<p><select name="Voie" autocomplete="off">';
					$query = "SELECT CONCAT_WS(' - ',`Murs`.`Nom`, `Emplacements`.`Nom`, `Voies`.`Cotation`, `Couleurs`.`Nom`) AS `Nom`, `Voies`.`Id` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` ORDER BY `Murs`.`Nom`, `Emplacements`.`Ordre`, `Voies`.`Cotation`";
					$result = $dbh->query($query);
					while($Voie = $result->fetchObject()) {
						echo '<option value="'.$Voie->Id.'">'.$Voie->Nom.'</option>';
					}
					echo '</select></p>';
					echo '<h4>Type</h4>';
					echo '<p><select name="Type" autocomplete="off">';
					foreach (['Difficulté' => 'Difficulté', 'Vitesse' => 'Vitesse', 'Bloc' => 'Bloc'] as $Clé => $Valeur) {
						echo '<option value="'.$Clé.'">'.$Valeur.'</option>';
					}
					echo '</select></p>';
					echo '<h4>Evaluation</h4>';
					echo '<p><select name="Evaluation" autocomplete="off">';
					foreach (['Top' => 'Pas d’évaluation intermédiaire', 'Dégaine' => 'Dégaine', 'Prise' => 'Prise'] as $Clé => $Valeur) {
						echo '<option value="'.$Clé.'">'.$Valeur.'</option>';
					}
					echo '</select></p>';
					echo '<h4>Chronométrée</h4>';
					echo '<p><label><input type="radio" name="Chronométrée" value="0" autocomplete="off" checked>Non</label><label><input type="radio" name="Chronométrée" value="1" autocomplete="off">Oui</label></p>';
					echo '<h4>Nombre d’essais libres</h4>';
					echo '<p><input type="number" name="Nb_Essais_Libres" min="0" step="1" autocomplete="off"></p>';
					echo '<h4>Nombre d’essais évalués</h4>';
					echo '<p><input type="number" name="Nb_Essais_Evalués" min="0" step="1" autocomplete="off"></p>';
					echo '<h4>Nombre de points donné à chaque grimpeur	</h4>';
					echo '<p><input type="number" name="Nb_Points_Absolu" min="0" step="1" autocomplete="off"></p>';
					echo '<h4>Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur</h4>';
					echo '<p><input type="number" name="Nb_Points_Relatif" min="0" step="1" autocomplete="off"></p>';
					echo '<h4>Phase</h4>';
					echo '<p><input type="text" name="Phase" list="listePhase" autocomplete="off"></p>';
					echo '<datalist id="listePhase">';
					$result = $dbh->query("SELECT DISTINCT `Phase` AS `Nom` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." ORDER BY `Phase`");
					while ($Phase = $result->fetchObject()) {
						echo '<option value="'.$Phase->Nom.'">';
					}
					echo '</datalist>';
					//Zones
					echo '<h4>Zones</h4>';
					echo '<table><thead><tr><th>Nom</th><th>Nombre de points donné à chaque grimpeur</th><th>Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur</th><th>Cotation</th></tr></thead><tbody>';
					echo '<tr>';
					echo '<td><input type="text" name="Zone_1_Nom" autocomplete="off" onChange="add_row();"></td>';
					echo '<td><input type="number" name="Zone_1_Nb_Points_Absolu" min="0" step="1" autocomplete="off"></td>';
					echo '<td><input type="number" name="Zone_1_Nb_Points_Relatif" min="0" step="1" autocomplete="off"></td>';
					echo '<td><input type="text" name="Zone_1_Cotation" list="Cotation" autocomplete="off"></td>';
					echo '</tr>';
					echo '</tbody></table>';
					echo '<datalist id="Cotation">';
					$query = "SELECT DISTINCT `Cotation` FROM `Voies` ORDER BY `Cotation`";
					$result = $dbh->query($query);
					while ($Cotation = $result->fetchObject()) {
						echo '<option>' . $Cotation->Cotation . '</option>';
					}
					echo '</datalist>';
					echo '<script>
						function add_row() {
							let i = parseInt(document.getElementById(\'Zone_Max_Id\').value);
							let lastNom = document.getElementsByName(\'Zone_\'+i+\'_Nom\').item(0);
							if (lastNom.value != \'\') {
								let node = document.createElement(\'tr\');
								node.innerHTML = lastNom.parentNode.parentNode.innerHTML.replace(new RegExp(\'Zone_\'+i,\'g\'),\'Zone_\'+(i+1));
								lastNom.parentNode.parentNode.parentNode.appendChild(node);
								document.getElementById(\'Zone_Max_Id\').value = i+1;
							}
						}
					</script>';
					echo '<input type="hidden" name="Zone_Max_Id" id="Zone_Max_Id" value="1">';
					echo '<input type="submit" value="Valider">';
					echo '</form>';
					break;
				case 'modifier':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT * FROM `Tournois_Voies` WHERE `Tournoi` = :Tournoi AND `Voie` = :Id");
						$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
						if ($result->rowCount() > 0) {
							$Voie = $result->fetchObject();
							echo '<h2>Modification  de '.afficheVoieFromId($Voie->Voie,false).'</h2>';
							echo '<form method="post">';
							echo '<input type="hidden" name="Vue" value="Voies">';
							echo '<input type="hidden" name="Action" value="éditer">';
							echo '<input type="hidden" name="Id" value="'.$Voie->Voie.'">';
							echo '<h4>Voie</h4>';
							$query = "SELECT CONCAT_WS(' - ',`Murs`.`Nom`, `Emplacements`.`Nom`, `Voies`.`Cotation`, `Couleurs`.`Nom`) AS `Nom` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Voies`.`Id` = ".$Voie->Voie;
							$result = $dbh->query($query);
							$Mur = $result->fetchObject();
							echo '<p>'.$Mur->Nom.'</p>';
							echo '<h4>Type</h4>';
							echo '<p><select name="Type" autocomplete="off">';
							foreach (['Difficulté' => 'Difficulté', 'Vitesse' => 'Vitesse', 'Bloc' => 'Bloc'] as $Clé => $Valeur) {
								echo '<option value="'.$Clé.'"';
								if ($Clé == $Voie->Type) {
									echo ' selected';
								}
								echo '>'.$Valeur.'</option>';
							}
							echo '</select></p>';
							echo '<h4>Evaluation</h4>';
							echo '<p><select name="Evaluation" autocomplete="off">';
							foreach (['Top' => 'Pas d’évaluation intermédiaire', 'Dégaine' => 'Dégaine', 'Prise' => 'Prise'] as $Clé => $Valeur) {
								echo '<option value="'.$Clé.'"';
								if ($Clé == $Voie->Evaluation) {
									echo ' selected';
								}
								echo '>'.$Valeur.'</option>';
							}
							echo '</select></p>';
							echo '<h4>Chronométrée</h4>';
							echo '<p><label><input type="radio" name="Chronométrée" value="0" autocomplete="off"'.($Voie->Chronométrée == '0' ? ' checked': '').'>Non</label><label><input type="radio" name="Chronométrée" value="1" autocomplete="off"'.($Voie->Chronométrée == '1' ? ' checked': '').'>Oui</label></p>';
							echo '<h4>Nombre d’essais libres</h4>';
							echo '<p><input type="number" name="Nb_Essais_Libres" value="'.$Voie->Nb_Essais_Libres.'" min="0" step="1" autocomplete="off"></p>';
							echo '<h4>Nombre d’essais évalués</h4>';
							echo '<p><input type="number" name="Nb_Essais_Evalués" value="'.$Voie->Nb_Essais_Evalués.'" min="0" step="1" autocomplete="off"></p>';
							echo '<h4>Nombre de points donné à chaque grimpeur</h4>';
							echo '<p><input type="number" name="Nb_Points_Absolu" value="'.$Voie->Nb_Points_Absolu.'" min="0" step="1" autocomplete="off"></p>';
							echo '<h4>Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur</h4>';
							echo '<p><input type="number" name="Nb_Points_Relatif" value="'.$Voie->Nb_Points_Relatif.'" min="0" step="1" autocomplete="off"></p>';
							echo '<h4>Phase</h4>';
							echo '<p><input type="text" name="Phase" value="'.$Voie->Phase.'" list="listePhase" autocomplete="off"></p>';
							echo '<datalist id="listePhase">';
							$result = $dbh->query("SELECT DISTINCT `Phase` AS `Nom` FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." ORDER BY `Phase`");
							while ($Phase = $result->fetchObject()) {
								echo '<option value="'.$Phase->Nom.'">';
							}
							echo '</datalist>';
							//Zones
							echo '<h4>Zones</h4>';
							echo '<table><thead><tr><th>Nom</th><th>Nombre de points donné à chaque grimpeur</th><th>Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur</th><th>Cotation</th></tr></thead><tbody>';
							$result = $dbh->query("SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Voie." ORDER BY `Id`");
							$ZoneId = 0;
							if ($result->rowCount() > 0) {
								while ($Zone = $result->fetchObject()) {
									$ZoneId = max($ZoneId,$Zone->Id);
									echo '<tr>';
									echo '<td><input type="text" name="Zone_'.$Zone->Id.'_Nom" value="'.$Zone->Nom.'" autocomplete="off"></td>';
									echo '<td><input type="number" name="Zone_'.$Zone->Id.'_Nb_Points_Absolu" value="'.$Zone->Nb_Points_Absolu.'" min="0" step="1" autocomplete="off"></td>';
									echo '<td><input type="number" name="Zone_'.$Zone->Id.'_Nb_Points_Relatif" value="'.$Zone->Nb_Points_Relatif.'" min="0" step="1" autocomplete="off"></td>';
									echo '<td><input type="text" name="Zone_'.$Zone->Id.'_Cotation" value="'.$Zone->Cotation.'" list="Cotation" autocomplete="off"></td>';
									echo '</tr>';
								}
							}
							$ZoneId += 1;
							echo '<tr>';
							echo '<td><input type="text" name="Zone_'.$ZoneId.'_Nom" autocomplete="off" onChange="add_row();"></td>';
							echo '<td><input type="number" name="Zone_'.$ZoneId.'_Nb_Points_Absolu" min="0" step="1" autocomplete="off"></td>';
							echo '<td><input type="number" name="Zone_'.$ZoneId.'_Nb_Points_Relatif" min="0" step="1" autocomplete="off"></td>';
							echo '<td><input type="text" name="Zone_'.$ZoneId.'_Cotation" list="Cotation" autocomplete="off"></td>';
							echo '</tr>';
							echo '</tbody></table>';
							echo '<datalist id="Cotation">';
							$query = "SELECT DISTINCT `Cotation` FROM `Voies` ORDER BY `Cotation`";
							$result = $dbh->query($query);
							while ($Cotation = $result->fetchObject()) {
								echo '<option>' . $Cotation->Cotation . '</option>';
							}
							echo '</datalist>';
							echo '<script>
								function add_row() {
									let i = parseInt(document.getElementById(\'Zone_Max_Id\').value);
									let lastNom = document.getElementsByName(\'Zone_\'+i+\'_Nom\').item(0);
									if (lastNom.value != \'\') {
										let node = document.createElement(\'tr\');
										node.innerHTML = lastNom.parentNode.parentNode.innerHTML.replace(new RegExp(\'Zone_\'+i,\'g\'),\'Zone_\'+(i+1));
										lastNom.parentNode.parentNode.parentNode.appendChild(node);
										document.getElementById(\'Zone_Max_Id\').value = i+1;
									}
								}
							</script>';
							echo '<input type="hidden" name="Zone_Max_Id" id="Zone_Max_Id" value="'.$ZoneId.'">';

							echo '<input type="submit" value="Valider">';
							echo '</form>';
						} else {
							echo '<p>Cette voie n’existe pas ou ne fait pas parti de votre tournoi.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				case 'supprimer':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT `Tournois_Voies`.`Voie` AS `Id`, (SELECT COUNT(*) FROM `Essais` WHERE `Tournoi` = `Tournois_Voies`.`Tournoi` AND `Voie` = `Tournois_Voies`.`Voie`) AS `Nb_Essais` FROM `Tournois_Voies` WHERE `Tournois_Voies`.`Voie` = :Id AND `Tournois_Voies`.`Tournoi` = :Tournoi");
						$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
						if ($result->rowCount() > 0) {
							$Voie = $result->fetchObject();
							echo '<h2>Suppression de la '.afficheVoieFromId($Voie->Id,false).'</h2>';
							if ($Voie->Nb_Essais == 0) {
								echo '<form method="post">';
								echo '<input type="hidden" name="Vue" value="Voies">';
								echo '<input type="hidden" name="Action" value="retirer">';
								echo '<input type="hidden" name="Id" value="'.$Voie->Id.'">';
								echo '<p>Êtes-vous sûr de vouloir supprimer cet voie.</p>';
								echo '<p><input type="submit" value="Supprimer cette voie"></p>';
								echo '</form>';
							} else {
								echo '<p>Vous ne pouvez pas vous supprimer cette voie car des grimpeurs l’ont déjà montée.</p>';
							}
						} else {
							echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce tournoi.</p>';
						}
					} else {
						echo '<p>Les données saisies ne sont pas complètes.</p>';
					}
					break;
				default:
					echo '<h3>Gestion des voies</h3>';
					switch($_REQUEST['Action']) {
						case 'insérer':
							if ($_REQUEST['Voie'] != '' and $_REQUEST['Type'] != '') {
								$query = "SELECT `Id` FROM `Voies` WHERE `Id` = :Id";
								$result = $dbh->prepare($query);
								$result->execute(['Id' => $_REQUEST['Voie']]);
								if ($result->rowCount() > 0) {
									$Voie = $result->fetchObject();
									$query = "INSERT INTO `Tournois_Voies`(`Tournoi`, `Voie`, `Type`, `Evaluation`, `Nb_Essais_Libres`, `Nb_Essais_Evalués`, `Chronométrée`, `Nb_Points_Absolu`, `Nb_Points_Relatif`, `Phase`) VALUES (:Tournoi, :Voie, :Type, :Evaluation, :NbEssaisLibres, :NbEssaisEvalues, :Chronometree, :NbPointsAbsolu, :NbPointsRelatif, :Phase)";
									$result = $dbh->prepare($query);
									if ($result->execute([
										'Tournoi' => $Tournoi->Id,
										'Voie' => $Voie->Id,
										'Type' => $_REQUEST['Type'],
										'Evaluation' => $_REQUEST['Evaluation'] != '' ? $_REQUEST['Evaluation'] : null,
										'NbEssaisLibres' => $_REQUEST['Nb_Essais_Libres'] != '' ? $_REQUEST['Nb_Essais_Libres'] : null,
										'NbEssaisEvalues' => $_REQUEST['Nb_Essais_Evalués'] != '' ? $_REQUEST['Nb_Essais_Evalués'] : null,
										'Chronometree' => $_REQUEST['Chronométrée'] == '1' ? 1 : 0,
										'NbPointsAbsolu' => $_REQUEST['Nb_Points_Absolu'] != '' ? $_REQUEST['Nb_Points_Absolu'] : null,
										'NbPointsRelatif' => $_REQUEST['Nb_Points_Relatif'] != '' ? $_REQUEST['Nb_Points_Relatif'] : null,
										'Phase' => $_REQUEST['Phase'] != '' ? $_REQUEST['Phase'] : null
									])) {
										echo '<p>La '.afficheVoieFromId($Voie->Id,false).' a bien été ajouté à votre tournoi.</p>';
										
										//Zones
										$result5 = $dbh->prepare("INSERT INTO `Tournois_Voies_Zones`(`Tournoi`, `Voie`, `Id`, `Nom`, `Nb_Points_Absolu`, `Nb_Points_Relatif`, `Cotation`) VALUES (:Tournoi, :Voie, :Id, :Nom, :NbPointsAbsolu, :NbPointsRelatif, :Cotation)");
										for($i = 1; $i <= $_REQUEST['Zone_Max_Id']; $i+=1) {
											if (isset($_REQUEST['Zone_'.$i.'_Nom']) and $_REQUEST['Zone_'.$i.'_Nom'] != '') {
												$result5->execute([
													'Tournoi' => $Tournoi->Id,
													'Voie' => $Voie->Id,
													'Id' => $i,
													'Nom' => $_REQUEST['Zone_'.$i.'_Nom'],
													'NbPointsAbsolu' => $_REQUEST['Zone_'.$i.'_Nb_Points_Absolu'] != '' ? $_REQUEST['Zone_'.$i.'_Nb_Points_Absolu'] : null,
													'NbPointsRelatif' => $_REQUEST['Zone_'.$i.'_Nb_Points_Relatif'] != '' ? $_REQUEST['Zone_'.$i.'_Nb_Points_Relatif'] : null,
													'Cotation' => $_REQUEST['Zone_'.$i.'_Cotation'] != '' ? $_REQUEST['Zone_'.$i.'_Cotation'] : null
												]);
											}
										}
									} else {
										echo '<p>Impossible d’ajouter la '.afficheVoieFromId($Voie->Id,false).'.</p>';
									}
								} else {
									echo '<p>Cette voie n’existe pas.</p>';
								}
							} else {
								echo '<p>Les données saisies sont incomplètes.</p>';
							}
							break;
						case 'éditer':
							if ($_REQUEST['Id'] != '' and $_REQUEST['Type'] != '') {
								$result = $dbh->prepare("SELECT `Tournois_Voies`.`Voie` AS `Id` FROM `Tournois_Voies` WHERE `Tournois_Voies`.`Voie` = :Id AND `Tournois_Voies`.`Tournoi` = :Tournoi");
								$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
								if ($result->rowCount() > 0) {
									$Voie = $result->fetchObject();
									$query = "UPDATE `Tournois_Voies` SET `Type`=:Type,`Evaluation`=:Evaluation,`Nb_Essais_Libres`=:NbEssaisLibres,`Nb_Essais_Evalués`=:NbEssaisEvalues, `Chronométrée` = :Chronometree, `Nb_Points_Absolu` = :NbPointsAbsolu, `Nb_Points_Relatif` = :NbPointsRelatif, `Phase` = :Phase WHERE `Tournoi` = :Tournoi AND `Voie` = :Voie";
									$result = $dbh->prepare($query);
									if ($result->execute([
										'Tournoi' => $Tournoi->Id,
										'Voie' => $Voie->Id,
										'Type' => $_REQUEST['Type'],
										'Evaluation' => $_REQUEST['Evaluation'] != '' ? $_REQUEST['Evaluation'] : null,
										'NbEssaisLibres' => $_REQUEST['Nb_Essais_Libres'] != '' ? $_REQUEST['Nb_Essais_Libres'] : null,
										'NbEssaisEvalues' => $_REQUEST['Nb_Essais_Evalués'] != '' ? $_REQUEST['Nb_Essais_Evalués'] : null,
										'Chronometree' => $_REQUEST['Chronométrée'] == '1' ? 1 : 0,
										'NbPointsAbsolu' => $_REQUEST['Nb_Points_Absolu'] != '' ? $_REQUEST['Nb_Points_Absolu'] : null,
										'NbPointsRelatif' => $_REQUEST['Nb_Points_Relatif'] != '' ? $_REQUEST['Nb_Points_Relatif'] : null,
										'Phase' => $_REQUEST['Phase'] != '' ? $_REQUEST['Phase'] : null
									])) {
										echo '<p>Les informations de la '.afficheVoieFromId($Voie->Id,false).' ont bien été mises à jour.</p>';
									} else {
										echo '<p>Les informations de la '.afficheVoieFromId($Voie->Id,false).' n’ont pas été mises à jour.</p>';
									}

									//Zones
									$result1 = $dbh->query("SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." ORDER BY `Id`");
									$Zones = [];
									$result2 = $dbh->prepare("UPDATE `Tournois_Voies_Zones` SET `Nom`=:Nom,`Nb_Points_Absolu`=:NbPointsAbsolu,`Nb_Points_Relatif`=:NbPointsRelatif,`Cotation`=:Cotation WHERE `Tournoi` = :Tournoi AND `Voie` = :Voie AND `Id` = :Id");
									$result3 = $dbh->prepare("DELETE FROM `Tournois_Voies_Zones` WHERE `Tournoi` = :Tournoi AND `Voie` = :Voie AND `Id` = :Id");
									$result4 = $dbh->prepare("SELECT * FROM `Essais` WHERE `Tournoi` = :Tournoi AND `Voie` = :Voie AND `Zones` = :Id");
									while ($Zone = $result1->fetchObject()) {
										if (isset($_REQUEST['Zone_'.$Zone->Id.'_Nom']) and $_REQUEST['Zone_'.$Zone->Id.'_Nom'] != '') {
											$result2->execute([
												'Tournoi' => $Tournoi->Id,
												'Voie' => $Voie->Id,
												'Id' => $Zone->Id,
												'Nom' => $_REQUEST['Zone_'.$Zone->Id.'_Nom'],
												'NbPointsAbsolu' => $_REQUEST['Zone_'.$Zone->Id.'_Nb_Points_Absolu'] != '' ? $_REQUEST['Zone_'.$Zone->Id.'_Nb_Points_Absolu'] : null,
												'NbPointsRelatif' => $_REQUEST['Zone_'.$Zone->Id.'_Nb_Points_Relatif'] != '' ? $_REQUEST['Zone_'.$Zone->Id.'_Nb_Points_Relatif'] : null,
												'Cotation' => $_REQUEST['Zone_'.$Zone->Id.'_Cotation'] != '' ? $_REQUEST['Zone_'.$Zone->Id.'_Cotation'] : null
											]);
										} else {
											$result4->execute([
												'Tournoi' => $Tournoi->Id,
												'Voie' => $Voie->Id,
												'Id' => $Zone->Id
											]);
											if ($result4->rowCount() == 0) {
												$result3->execute([
													'Tournoi' => $Tournoi->Id,
													'Voie' => $Voie->Id,
													'Id' => $Zone->Id
												]);
											} else {
												echo '<p>La zone «&nbsp;'.$Zone->Nom.'&nbsp;» ne peut pas être supprimée car elle a déjà été grimpée.</p>';
											}
										}
										$Zones[] = $Zone->Id;
									}
									$result5 = $dbh->prepare("INSERT INTO `Tournois_Voies_Zones`(`Tournoi`, `Voie`, `Id`, `Nom`, `Nb_Points_Absolu`, `Nb_Points_Relatif`, `Cotation`) VALUES (:Tournoi, :Voie, :Id, :Nom, :NbPointsAbsolu, :NbPointsRelatif, :Cotation)");
									for($i = 1; $i <= $_REQUEST['Zone_Max_Id']; $i+=1) {
										if (!in_array($i,$Zones)) {
											if (isset($_REQUEST['Zone_'.$i.'_Nom']) and $_REQUEST['Zone_'.$i.'_Nom'] != '') {
												$result5->execute([
													'Tournoi' => $Tournoi->Id,
													'Voie' => $Voie->Id,
													'Id' => $i,
													'Nom' => $_REQUEST['Zone_'.$i.'_Nom'],
													'NbPointsAbsolu' => $_REQUEST['Zone_'.$i.'_Nb_Points_Absolu'] != '' ? $_REQUEST['Zone_'.$i.'_Nb_Points_Absolu'] : null,
													'NbPointsRelatif' => $_REQUEST['Zone_'.$i.'_Nb_Points_Relatif'] != '' ? $_REQUEST['Zone_'.$i.'_Nb_Points_Relatif'] : null,
													'Cotation' => $_REQUEST['Zone_'.$i.'_Cotation'] != '' ? $_REQUEST['Zone_'.$i.'_Cotation'] : null
												]);
											}
										}
									}
								} else {
									echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce tournoi.</p>';
								}
							} else {
								echo '<p>Les données saisies sont incomplètes.</p>';
							}
							break;
						case 'retirer':
						
							if ($_REQUEST['Id'] != '') {
								$result = $dbh->prepare("SELECT `Tournois_Voies`.`Voie` AS `Id`, (SELECT COUNT(*) FROM `Essais` WHERE `Tournoi` = `Tournois_Voies`.`Tournoi` AND `Voie` = `Tournois_Voies`.`Voie`) AS `Nb_Essais` FROM `Tournois_Voies` WHERE `Tournois_Voies`.`Voie` = :Id AND `Tournois_Voies`.`Tournoi` = :Tournoi");
								$result->execute(['Id' => $_REQUEST['Id'], 'Tournoi' => $Tournoi->Id]);
								if ($result->rowCount() > 0) {
									$Voie = $result->fetchObject();
									if ($Voie->Nb_Essais == 0) {
										if ($dbh->query("DELETE FROM `Tournois_Voies` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id)) {
											echo '<p>La '.afficheVoieFromId($Voie->Id,false).' a bien été supprimée.</p>';
										} else {
											echo '<p>La '.afficheVoieFromId($Voie->Id,false).' n’a pas été supprimée</p>';
										}
									} else {
										echo '<p>Vous ne pouvez pas vous supprimer cette voie car des grimpeurs l’ont déjà montée.</p>';
									}
								} else {
									echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce tournoi.</p>';
								}
							} else {
								echo '<p>Les données saisies ne sont pas complètes.</p>';
							}
							break;
					}

					//Liste des voies
					echo '<form method="post" id="créer">';
					echo '<input type="hidden" name="Vue" value="Voies">';
					echo '<input type="hidden" name="Action" value="créer">';
					echo '</form>';
					echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter une voie"></p>';
					
					$Nombre_de_murs = $dbh->query("SELECT COUNT(DISTINCT `Emplacements`.`Mur`) AS `NombreMur` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Tournois_Voies`.`Tournoi`= ".$Tournoi->Id)->fetchObject()->NombreMur;
					echo '<div class="table"><table><thead><tr>';
					if ($Nombre_de_murs > 1) {
						echo '<th>Mur</th>';
					}
					echo '<th>Voie</th><th>Type</th><th>Mode d’évaluation</th><th>Nombre d’essais autorisés</th><th>Phase</th><th class="noPrint"></th></tr></thead><tbody>';
					$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Voies`.`Cotation`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2`, `Emplacements`.`Nom` AS `Emplacement`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués`, `Tournois_Voies`.`Chronométrée`, `Tournois_Voies`.`Nb_Points_Absolu`, `Tournois_Voies`.`Nb_Points_Relatif`, `Tournois_Voies`.`Phase` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi ORDER BY `Voies`.`Emplacement`");
					$result->execute(array('Tournoi' => $Tournoi->Id));
					while($Voie = $result->fetchObject()){
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
						if ($Voie->Cotation != null and $Voie->Cotation > 0) {
							echo ' color: ' . couleur_text('#' . $Voie->Couleur_1) . '">' . $Voie->Cotation . ' sur l’emplacement ' . $Voie->Emplacement . '</td>';
						} else {
							echo ' color: ' . couleur_text('#' . $Voie->Couleur_1) . '">Voie sur l’emplacement ' . $Voie->Emplacement . '</td>';
						}
						echo '<td>' . $Voie->Type . '</td>';
						$Evaluation = [];
						switch ($Voie->Evaluation) {
							case 'Prise':
								$Evaluation[] = 'Prise max atteinte';
							break;
							case 'Dégaine':
								$Evaluation[] = 'Dégaine max atteinte';
							break;
							case 'Top':
								$Evaluation[] = 'Top atteint';
							break;
						}
						if ($Voie->Chronométrée) {
							$Evaluation[] = "Chronométrée";
						}
						if ($Voie->Nb_Points_Absolu != null) {
							$Evaluation[] = $Voie->Nb_Points_Absolu." points";
						}
						if ($Voie->Nb_Points_Relatif != null) {
							$Evaluation[] = $Voie->Nb_Points_Relatif." points partagés entre les grimpeurs";
						}
						$result1 = $dbh->query("SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." ORDER BY `Id`");
						if ($result1->rowCount() > 0) {
							$Zones = [];
							while ($Zone = $result1->fetchObject()) {
								$Zone_info = [];
								if ($Zone->Cotation != null) {
									$Zone_info[] = $Zone->Cotation;
								}
								if ($Zone->Nb_Points_Absolu != null) {
									$Zone_info[] = $Zone->Nb_Points_Absolu." points";
								}
								if ($Zone->Nb_Points_Relatif != null) {
									$Zone_info[] = $Zone->Nb_Points_Relatif." points partagés";
								}
								$Zones[] = $Zone->Nom.(count($Zone_info) > 0 ? " (".implode(", ",$Zone_info).")" : '');
							}
							$Evaluation[] = "Zones&nbsp;: ".implode(", ",$Zones);
						}
						echo '<td>' . implode('<br/>',$Evaluation) . '</td>';
	
						if ($Voie->Nb_Essais_Libres == null and $Voie->Nb_Essais_Evalués == null) {
							echo '<td>illimités</td>';
						} else {
							echo '<td>';
							if ($Voie->Nb_Essais_Libres != null) {
								echo $Voie->Nb_Essais_Libres . ' essais libres';
							}
							if ($Voie->Nb_Essais_Libres != null and $Voie->Nb_Essais_Evalués != null) {
								echo '<br>';
							}
							if ($Voie->Nb_Essais_Evalués != null) {
								echo $Voie->Nb_Essais_Evalués . ' essais évalués';
							}
							echo '</td>';
						}
						echo '<td>' . $Voie->Phase . '</td>';
						echo '<td class="noPrint">';
						echo '<form method="post" class="icon">';
						echo '<input type="hidden" name="Vue" value="Voies">';
						echo '<input type="hidden" name="Action" value="modifier">';
						echo '<input type="hidden" name="Id" value=' . $Voie->Id . '>';
						echo '<input type="submit" title="Modifier cette voie" value="✏️">';
						echo '</form>';
						echo '<form method="post" class="icon">';
						echo '<input type="hidden" name="Vue" value="Voies">';
						echo '<input type="hidden" name="Action" value="supprimer">';
						echo '<input type="hidden" name="Id" value=' . $Voie->Id . '>';
						echo '<input type="submit" title="Supprimer cette voie" value="🗑️">';
						echo '</form>';
						echo '</td>';
						echo '</tr>';
					}
					echo '</tbody></table></div>';
			}
		break;
		default:
			echo '<h3>Paramètres du tournoi</h3>';
			switch($_REQUEST['Action']) {
				case 'modifier':
					if ($_REQUEST['Nom'] != '' and $_REQUEST['Date'] != '') {
						$query = "UPDATE `Tournois` SET `Nom`= :Nom,`Date`=:Date WHERE `Id` = :Id";
						$result = $dbh->prepare($query);
						if ($result->execute(['Id' => $Tournoi->Id, 'Nom' => $_REQUEST['Nom'], 'Date' => $_REQUEST['Date']])) {
							echo '<p>Les informations du tournoi '.$_REQUEST['Nom'].' ont bien été mises à jour.</p>';
							$Tournoi = initialiser_Tournoi();
						} else {
							echo '<p>Les informations du tournoi '.$_REQUEST['Nom'].' n’ont pas été mises à jour.</p>';
						}
					} else {
						echo '<p>Les données saisies sont incomplètes.</p>';
					}
					break;
					case 'créer':
						if ($_REQUEST['Nom'] != '' and $_REQUEST['Date'] != '') {
							$query = " INSERT INTO `Tournois` (`Id`, `Nom`, `Date`, `Options_Résultats`) VALUES (NULL, :Nom, :Date, '')";
							$result = $dbh->prepare($query);
							$result->execute(['Nom' => $_REQUEST['Nom'], 'Date' => $_REQUEST['Date']]);

							$indexTournois=$dbh->lastInsertId();

							$query = " INSERT INTO `Tournois_Utilisateurs` (`Tournoi`, `Utilisateur`, `Type`, `Dossard`, `Catégorie`, `Equipe`) VALUES (" .$indexTournois.", ".$Utilisateur_Con->Id.", 'Administrateur', NULL, NULL)";
							
							$result->execute($query);
								echo "<p>Le tournoi ".$_REQUEST['Nom']." a été créé, vous pouvez l'administrer.</p>";
							
						} else {
							echo '<p>Les données saisies sont incomplètes.</p>';
						}
						break;
			}
			echo '<form method="post">';
			echo '<input type="hidden" name="Action" value="modifier">';
			echo '<h4>Nom</h4>';
			echo '<p><input type="text" name="Nom" value="'.$Tournoi->Nom.'" autocomplete="off" required></p>';
			echo '<h4>Date</h4>';
			echo '<p><input type="date" name="Date" value="'.$Tournoi->Date.'" autocomplete="off" required></p>';
			echo '<input type="submit" value="Valider">';
			echo '</form>';
	}
	
} else {
	echo '<p>Vous n’êtes pas administrateur sur ce tournoi.</p>';
}
require('Pied de page.inc.php');
require('Bas.inc.php');
