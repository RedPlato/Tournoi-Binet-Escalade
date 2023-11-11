<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Evaluation - ' . $Tournoi->Nom;
require('Entête.inc.php');
$query = "SELECT * FROM `Tournois_Utilisateurs` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Utilisateur` = " . $Utilisateur_Con->Id . " AND (`Type` = 'Juge' OR `Type` = 'Administrateur')";
$result = $dbh->query($query);
if ($result->rowCount() > 0) {
	switch ($_REQUEST['Action']) {
		case 'créer':
			if ($_REQUEST['Voie'] != '' and $_REQUEST['Grimpeur'] != '') {
				$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués`, `Tournois_Voies`.`Chronométrée`, `Tournois_Voies`.`Nb_Points_Absolu`, `Tournois_Voies`.`Nb_Points_Relatif` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi AND `Voies`.`Id` = :Voie");
				$result->execute(array('Tournoi' => $Tournoi->Id, 'Voie' => $_REQUEST['Voie']));
				if ($result->rowCount() > 0) {
					$Voie = $result->fetchObject();
					$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Prénom`,`Utilisateurs`.`Nom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` = `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur AND `Type` = 'Grimpeur'";
					$result = $dbh->prepare($query);
					$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $_REQUEST['Grimpeur']]);
					if ($result->rowCount() > 0) {
						$Grimpeur = $result->fetchObject();
						
						//Information sur la voie
						echo '<h3>' . $Voie->Mur . ' - ' . afficheVoieFromId($Voie->Id) . '</h3>';
						echo '<p>Type&nbsp;: ' . $Voie->Type;
						$Points = [];
						if ($Voie->Nb_Points_Absolu != null) {
							$Points[] = $Voie->Nb_Points_Absolu." points";
						}
						if ($Voie->Nb_Points_Relatif != null) {
							$Points[] = $Voie->Nb_Points_Relatif." points partagés entre les grimpeurs";
						}
						if (count($Points) > 0) {
							echo ' ('.implode(', ',$Points).')';
						}
						
						//Information sur les zones
						$result = $dbh->query("SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." ORDER BY `Id`");
						if ($result->rowCount() > 0) {
							$Zones = [];
							while ($Zone = $result->fetchObject()) {
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
								$Zones[] = $Zone->Nom." (".implode(", ",$Zone_info).")";
							}
							echo '<br/>Zones&nbsp;: '.implode(", ",$Zones);
						}
						echo '</p>';
						
						//Information sur le grimpeur
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
						$query = "SELECT IFNULL(SUM(`Evalué`),0) AS `Evalué`, IFNULL(COUNT(*)-SUM(`Evalué`),0) AS `Libre` FROM `Essais` WHERE `Utilisateur` = " . $Grimpeur->Id . " AND `Voie` = " . $Voie->Id . " AND `Tournoi` = " . $Tournoi->Id;
						$result = $dbh->query($query);
						$Count = $result->fetchObject();
						echo 'Nombre d’essais libres&nbsp;: ' . $Count->Libre . '/' . ($Voie->Nb_Essais_Libres != null ? $Voie->Nb_Essais_Libres : 'illimité');
						echo '<br>Nombre d’essais évalués&nbsp;: ' . $Count->Evalué . '/' . ($Voie->Nb_Essais_Evalués != null ? $Voie->Nb_Essais_Evalués : 'illimité');
						echo '</p>';

						//Evaluation
						echo '<form method="post">';
						echo '<input type="hidden" name="Action" value="insérer">';
						echo '<input type="hidden" name="Voie" value="' . $Voie->Id . '">';
						echo '<input type="hidden" name="Grimpeur" value="' . $Grimpeur->Id . '">';
						if ($Voie->Nb_Essais_Libres === '0') {
							echo '<input type="hidden" name="Essai" value="Evalué">';
						} else {
							echo '<h4>Essai</h4>';
							echo '<p><label><input type="radio" name="Essai" value="Libre" onchange="afficher_other()">Libre</label><label><input type="radio" name="Essai" id="Evalué" value="Evalué" checked onchange="afficher_other()">Evalué</label></p>';
						}
						echo '<div id="Réussite_Block">';
						echo '<h4>Réussite</h4>';
						$query = "SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " ORDER BY `Id`";
						$result = $dbh->query($query);
						if ($result->rowCount() > 0) {
							echo '<p>';
							echo '<label><input type="radio" name="Progression" value="top" id="Réussite" onchange="afficher_other();" checked>Voie terminée</label>';
							while ($Zone = $result->fetchObject()) {
								echo '<br/><label><input type="radio" name="Progression" value="' . $Zone->Id . '" onchange="afficher_other();">' . $Zone->Nom . '</label>';
							}
							echo '<br/><label><input type="radio" name="Progression" value="" onchange="afficher_other();">Chute avant la première zone</label>';
							echo '</p>';
						} else {
							echo '<p><label><input type="checkbox" name="Réussite" id="Réussite" checked onchange="afficher_other();">Voie terminée</label></p>';
						}
						echo '</div>';
						
						if ($Voie->Evaluation != 'Top') {
							echo '<div id="Nb_Dégaines" style="display: none;">';
							if ($Voie->Evaluation == 'Prise') {
								echo '<h4>Dernière prise validée</h4>';
							} elseif ($Voie->Evaluation == 'Dégaine') {
								echo '<h4>Dernière dégaine validée</h4>';
							}
							echo '<p><input type="number" name="Nb_Dégaines" value="0" step="0.1"></p>';

							// Si la dernière prise est tenue ou non
							if ($Voie->Evaluation == 'Prise') {
								echo '<p><label><input type="checkbox" name="Prise_tenue" id="Prise_tenue">Bonus</label></p>';
							}
							echo '</div>';
						}
						if ($Voie->Chronométrée === '1') {
							echo '<div id="ChronoDiv" style="display: block;">';
							echo '<h4>Chronomètre</h4>';
							echo '<p><input type="time" name="Chrono" id="Chrono" value="00:00:00" step="0.001"></p>';
							echo '<p style="display: flex;"><input type="button" value="Réinitialiser" onclick="temp = Date.now(); chrono = 0; document.getElementById(\'Chrono\').value=&quot;00:00:00&quot;;"><input type="button" value="Démarrer" id="chrono_button" onclick="chronomètre(this);"></p>';
							echo '</div>';
							echo '<script>
										var temp, f;
										var chrono = 0;
										function chronomètre(e) {
											var t = Date.now();
											if (e.value == \'Démarrer\') {
												temp = t;
												f = setInterval(function(){
													var d = new Date(chrono+Date.now()-temp);
													document.getElementById(\'Chrono\').value = d.toISOString().substring(11,23); 
													}, 13); 
												e.value=\'Arrêter\';
											} else {
												chrono += t-temp;
												clearInterval(f);
												var d = new Date(chrono);
												document.getElementById(\'Chrono\').value = d.toISOString().substring(11,23);
												e.value=\'Démarrer\';
												
											}
										}
									</script>';
						}
						echo '<p><input type="submit" value="Valider"></p>';
						echo '<script>
							function afficher_other() {
								if (document.getElementById(\'Evalué\') === null || document.getElementById(\'Evalué\').checked) {
									document.getElementById(\'Réussite_Block\').style.display = \'block\';
								} else {
									document.getElementById(\'Réussite_Block\').style.display = \'none\';
								}
								if ((document.getElementById(\'Evalué\') === null || document.getElementById(\'Evalué\').checked) && !document.getElementById(\'Réussite\').checked) {
									document.getElementById(\'Nb_Dégaines\').style.display = \'block\';
								} else {
									document.getElementById(\'Nb_Dégaines\').style.display = \'none\';
								}
								//if ((document.getElementById(\'Evalué\') === null || document.getElementById(\'Evalué\').checked) && document.getElementById(\'Réussite\').checked) {
								if (document.getElementById(\'Evalué\') === null || document.getElementById(\'Evalué\').checked) {
									document.getElementById(\'ChronoDiv\').style.display = \'block\';
								} else {
									document.getElementById(\'ChronoDiv\').style.display = \'none\';
								}
							}
						</script>'; // la ligne 158 remplace la ligne 157 commenté afin d'afficher le chrono même quand la voie n'est pas réussie
						echo '</form>';
						
						$query = "SELECT `Id`, `Date`, `Réussite`, `Evalué`, `Chrono` FROM `Essais` WHERE `Voie`= " . $Voie->Id . " AND `Utilisateur` = " . $Grimpeur->Id . " AND `Tournoi` = " . $Tournoi->Id . " AND `Entrée_Utilisateur` = " . $Utilisateur_Con->Id;
						$result = $dbh->query($query);
						if ($result->rowCount() > 0) {
							echo '<h3>Liste des essais évalués de ce grimpeur sur cette voie</h3>';
							echo '<ul>';
							while ($Essai = $result->fetchObject()) {
								echo '<li><form method="POST"><input type="hidden" name="Action" value="supprimer"><input type="hidden" name="Id" value="' . $Essai->Id . '">' . date_create($Essai->Date)->format('H\hi') . '&nbsp;: essai ' . ($Essai->Evalué ? 'évalué' . ($Essai->Réussite == null ? ' réussi'.($Voie->Chronométrée === '1' ? ' en ' . date_create($Essai->Chrono)->format('H:i:s') : '') : ' non réussi'.($Voie->Evaluation != 'Top' ? ' ('.$Voie->Evaluation.' '.$Essai->Réussite.')' : '')) : 'non évalué') . ' (<input class="link" type="submit" value="supprimer">)</form></li>';
							}
							echo '</ul>';
						}
					} else {
						echo '<p>Cet utilisateur n’existe pas ou ne concourt pas dans ce tournoi.</p>';
					}
				} else {
					echo '<p>Cette voie n’existe pas ou n’est pas utilisée pour ce tournoi.</p>';
				}
			} else {
				echo '<p>Les données envoyées sont incomplètes.</p>';
			}
			echo '<script>history.replaceState({Evaluation: "Tournois/Evaluation"}, "Evaluation", "Tournois/Evaluation");</script>';
			break;
		case 'supprimer':
			if ($_REQUEST['Id'] != '') {
				$result = $dbh->prepare("SELECT CONCAT_WS(' ', `Utilisateurs`.`Prénom`, `Utilisateurs`.`Nom`) AS `Grimpeur`, `Essais`.`Id`, `Essais`.`Tournoi`, `Essais`.`Entrée_Utilisateur`, `Essais`.`Voie`, `Essais`.`Date`, `Essais`.`Réussite`, `Essais`.`Evalué`, `Essais`.`Chrono` FROM `Essais` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur` WHERE `Essais`.`Id` = :Id");
				$result->execute(['Id' => $_REQUEST['Id']]);
				if ($result->rowCount() > 0) {
					$Essai = $result->fetchObject();
					if ($Essai->Tournoi == $Tournoi->Id and $Essai->Entrée_Utilisateur == $Utilisateur_Con->Id) {
						echo '<h3>Suppression de l’essai de ' . $Essai->Grimpeur . '</h3>';
						echo '<form method="post">';
						echo '<input type="hidden" name="Action" value="retirer">';
						echo '<input type="hidden" name="Voie" value="' . $Essai->Voie . '">';
						echo '<input type="hidden" name="Id" value="' . $Essai->Id . '">';
						echo '<p>Êtes-vous sûr de vouloir supprimer son essai ' . ($Essai->Evalué ? 'évalué' : 'non évalué') . ' de ' . date_create($Essai->Date)->format('H\hi') . ' sur la ' . afficheVoieFromId($Essai->Voie, false) . '.</p>';
						echo '<p><input type="submit" value="Supprimer cet essai"></p>';
						echo '</form>';
					} else {
						echo '<p>Vous n’avez pas évalué cet essai ou il ne fait pas partie de ce tournoi.</p>';
					}
				} else {
					echo '<p>Cet essai n’existe pas.</p>';
				}
			} else {
				echo '<p>Les données envoyées sont incomplètes.</p>';
			}
			echo '<script>history.replaceState({Evaluation: "Tournois/Evaluation"}, "Evaluation", "Tournois/Evaluation");</script>';
			break;
		default:
			switch ($_REQUEST['Action']) {
				case 'insérer':
					if ($_REQUEST['Voie'] != '' and $_REQUEST['Grimpeur'] != '') {
						$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués`, `Tournois_Voies`.`Chronométrée` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi AND `Voies`.`Id` = :Voie");
						$result->execute(array('Tournoi' => $Tournoi->Id, 'Voie' => $_REQUEST['Voie']));
						if ($result->rowCount() > 0) {
							$Voie = $result->fetchObject();
							$query = "SELECT `Utilisateur` AS `Id` , `Dossard` FROM `Tournois_Utilisateurs` WHERE `Tournoi` = :Tournoi AND `Tournois_Utilisateurs`.`Utilisateur` = :Grimpeur AND `Type` = 'Grimpeur'";
							$result = $dbh->prepare($query);
							$result->execute(['Tournoi' => $Tournoi->Id, 'Grimpeur' => $_REQUEST['Grimpeur']]);
							if ($result->rowCount() > 0) {
								//Réussite ou échec
								$Grimpeur = $result->fetchObject();
								if (isset($_REQUEST['Réussite']) and $_REQUEST['Réussite'] == 'on') {
									$Réussite = null;
									$Zones = null;
								} elseif (isset($_REQUEST['Réussite']) and $_REQUEST['Réussite'] == 'off') {
									if ($Voie->Evaluation != 'Top') {
										$Réussite = $_REQUEST['Nb_Dégaines'];
										
										// On ajoute 0.5 points si la prise à été tenue
										if (isset($_REQUEST['Prise_tenue'])) {
											$Réussite += 0.5;
										}
									} else {
										$Réussite = 0;
									}
									$Zones = null;
								} else {
									if (isset($_REQUEST['Progression']) and $_REQUEST['Progression'] == 'top') {
										$Réussite = null;
										$Zones = null;
									} elseif (isset($_REQUEST['Progression']) and $_REQUEST['Progression'] == 'chute') {
										if ($Voie->Evaluation != 'Top') {
											$Réussite = $_REQUEST['Nb_Dégaines'];

											// On ajoute 0.5 points si la prise à été tenue
											if (isset($_REQUEST['Prise_tenue'])) {
												$Réussite += 0.5;
											}
										} else {
											$Réussite = 0;
										}
										$Zones = null;
									} else {
										if ($Voie->Evaluation != 'Top') {
											$Réussite = $_REQUEST['Nb_Dégaines'];

											// On ajoute 0.5 points si la prise à été tenue
											if (isset($_REQUEST['Prise_tenue'])) {
												$Réussite += 0.5;
											}
										} else {
											$Réussite = 0;
										}
										$Zones = $_REQUEST['Progression'];
									}
								}

								$query = $dbh->prepare("INSERT INTO `Essais` (`Utilisateur`, `Voie`, `Date`, `Mode`, `Réussite`, `Tournoi`, `Evalué`, `Chrono`, `Zones`, `Entrée_Utilisateur`) VALUES (:Utilisateur , :Voie, NOW(), :Mode, :Reussite, :Tournoi, :Evalue, :Chrono, :Zones, :EntreeUtilisateur)");
								$data = [
									'Utilisateur' => $Grimpeur->Id,
									'Voie' => $Voie->Id,
									'Mode' => array('Difficulté' => 1, 'Bloc' => 3, 'Vitesse' => 2)[$Voie->Type],
									'Reussite' => $Réussite,
									'Tournoi' => $Tournoi->Id,
									'Evalue' => ($_REQUEST['Essai'] == 'Evalué'),
									'Chrono' => ($Voie->Chronométrée and $_REQUEST['Essai'] == 'Evalué') ? $_REQUEST['Chrono'] : null, // on supprime la condition de réussite de la voie pour tenir compte du chrono
									//'Chrono' => ($Voie->Chronométrée and $_REQUEST['Réussite'] == 'on' and $_REQUEST['Essai'] == 'Evalué') ? $_REQUEST['Chrono'] : null,
									'Zones' => $Zones != '' ? $Zones : null,
									'EntreeUtilisateur' => $Utilisateur_Con->Id
								];
								if ($query->execute($data)) {
									echo '<p class="succes">L\'essai du dossard '.$Grimpeur->Dossard.' sur '.afficheVoieFromId($Voie->Id).' a bien été pris en compte</p>';
									echo "<script>window.onload = function() {location.hash = '#Grimpeur_".$Grimpeur->Id."';}</script>";
								} else {
									echo '<p>Une erreur s’est produite lors de l’enregistrement.</p>';
								}
							} else {
								echo '<p>Cet utilisateur n’existe pas ou ne concourt pas dans ce tournoi.</p>';
							}
						} else {
							echo '<p>Cette voie n’existe pas ou n’est pas utilisée pour ce tournoi.</p>';
						}
					} else {
						echo '<p>Les données envoyées sont incomplètes.</p>';
					}
					echo '<script>history.replaceState({Evaluation: "Tournois/Evaluation?Voie=' . $Voie->Id . '"}, "Evaluation", "Tournois/Evaluation?Voie=' . $Voie->Id . '");</script>';
					break;
				case 'retirer':
					if ($_REQUEST['Id'] != '') {
						$result = $dbh->prepare("SELECT CONCAT_WS(' ', `Utilisateurs`.`Prénom`, `Utilisateurs`.`Nom`) AS `Grimpeur`, `Essais`.`Id`, `Essais`.`Tournoi`, `Essais`.`Entrée_Utilisateur`, `Essais`.`Voie` FROM `Essais` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Essais`.`Utilisateur` WHERE `Essais`.`Id` = :Id");
						$result->execute(['Id' => $_REQUEST['Id']]);
						if ($result->rowCount() > 0) {
							$Essai = $result->fetchObject();
							if ($Essai->Tournoi == $Tournoi->Id and $Essai->Entrée_Utilisateur == $Utilisateur_Con->Id) {
								if ($dbh->query("DELETE FROM `Essais` WHERE `Id` = " . $Essai->Id)) {
									echo '<p>L’essai de ' . $Essai->Grimpeur . ' a bien été supprimé.</p>';
									$dbh->query("ALTER TABLE `Essais` auto_increment = 1");
								} else {
									echo '<p>L’essai de ' . $Essai->Grimpeur . ' n’a pas été supprimé.</p>';
								}
							} else {
								echo '<p>Vous n’avez pas évalué cet essai ou il ne fait pas parti de ce tournoi.</p>';
							}
							echo '<script>history.replaceState({Evaluation: "Tournois/Evaluation?Voie=' . $Essai->Voie . '"}, "Evaluation", "Tournois/Evaluation?Voie=' . $Essai->Voie . '");</script>';
						} else {
							echo '<p>Cet essai n’existe pas.</p>';
						}
					} else {
						echo '<p>Les données envoyées sont incomplètes.</p>';
					}
					break;
			}
			if (isset($_REQUEST['Voie']) and $_REQUEST['Voie'] != '') {
				$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués`, `Tournois_Voies`.`Chronométrée`, `Tournois_Voies`.`Nb_Points_Absolu`, `Tournois_Voies`.`Nb_Points_Relatif` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi AND `Voies`.`Id` = :Voie");
				$result->execute(array('Tournoi' => $Tournoi->Id, 'Voie' => $_REQUEST['Voie']));
				if ($result->rowCount() > 0) {
					//Info sur la voie
					$Voie = $result->fetchObject();
					echo '<h3>' . $Voie->Mur . ' - ' . afficheVoieFromId($Voie->Id) . '</h3>';
					echo '<p>Type&nbsp;: ' . $Voie->Type;
					$Points = [];
					if ($Voie->Nb_Points_Absolu != null) {
						$Points[] = $Voie->Nb_Points_Absolu." points";
					}
					if ($Voie->Nb_Points_Relatif != null) {
						$Points[] = $Voie->Nb_Points_Relatif." points partagés entre les grimpeurs";
					}
					if (count($Points) > 0) {
						echo ' ('.implode(', ',$Points).')';
					}
					$result = $dbh->query("SELECT * FROM `Tournois_Voies_Zones` WHERE `Tournoi` = ".$Tournoi->Id." AND `Voie` = ".$Voie->Id." ORDER BY `Id`");
					if ($result->rowCount() > 0) {
						$Zones = [];
						while ($Zone = $result->fetchObject()) {
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
							$Zones[] = $Zone->Nom." (".implode(", ",$Zone_info).")";
						}
						echo '<br/>Zones&nbsp;: '.implode(", ",$Zones);
					}
					echo '<br>Nombre d’essais libres&nbsp;: ' . ($Voie->Nb_Essais_Libres != null ? $Voie->Nb_Essais_Libres . ' essais' : 'illimité');
					echo '<br>Nombre d’essais évalués&nbsp;: ' . ($Voie->Nb_Essais_Evalués != null ? $Voie->Nb_Essais_Evalués . ' essais' : 'illimité');
					echo '</p>';

					//Liste des grimpeurs
					$query = "SELECT `Utilisateurs`.`Id`, CONCAT_WS(' ',`Utilisateurs`.`Nom`,`Utilisateurs`.`Prénom`) AS `Nom`, `Utilisateurs`.`Genre`, `Tournois_Utilisateurs`.`Dossard`, `Tournois_Utilisateurs`.`Equipe` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN  `Utilisateurs` ON  `Utilisateurs`.`Id` =  `Tournois_Utilisateurs`.`Utilisateur` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Type` = 'Grimpeur' ORDER BY CAST(`Dossard` AS int), `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`"; //CAST(`01` AS SIGNED)
					$result = $dbh->query($query);
					echo '<div class="table"><table><thead><tr><th>Grimpeur</th><th>Essais</th></tr></thead><tbody>';
					while ($Grimpeur = $result->fetchObject()) {
						echo '<tr id="Grimpeur_'.$Grimpeur->Id.'" onclick="window.location.href = \'Tournois/Evaluation?Action=créer&Voie='.$Voie->Id.'&Grimpeur='.$Grimpeur->Id.'\';" style="cursor: pointer">';
						echo '<td>';
						if ($Grimpeur->Dossard != '') {
							echo $Grimpeur->Dossard . ' - ';
						}
						echo $Grimpeur->Nom;
						echo '<br>' . $Grimpeur->Genre;
						if ($Grimpeur->Equipe != null) {
							echo ' (' . $Grimpeur->Equipe . ')';
						}
						echo '</td>';
						echo '<td>';
						$query = "SELECT * FROM `Essais` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " AND `Utilisateur` = " . $Grimpeur->Id . " ORDER BY `Date`";
						$result1 = $dbh->query($query);
						while ($Essai = $result1->fetchObject()) {
							if ($Essai->Entrée_Utilisateur == $Utilisateur_Con->Id) {
								echo '<span style="font-weight: bold">';
							}
							echo date_create($Essai->Date)->format('H\hi') . '&nbsp;: essai ' . ($Essai->Réussite == null ? 'réussi' : 'raté');
							if ($Voie->Chronométrée === "1" and $Essai->Chrono != null) {
								echo ' (' . date_create($Essai->Chrono)->format('i:s.v') . ')';
							}
							if ($Essai->Zones != null) {
								$query = "SELECT GROUP_CONCAT(`Nom` ORDER BY `Id` SEPARATOR ', ') AS `Zones` FROM `Tournois_Voies_Zones` WHERE `Tournoi` = " . $Tournoi->Id . " AND `Voie` = " . $Voie->Id . " AND `Id` IN (" . $Essai->Zones . ")";
								$result2 = $dbh->query($query);
								echo ' - ' . $result2->fetchObject()->Zones;
							}
							if ($Essai->Entrée_Utilisateur == $Utilisateur_Con->Id) {
								echo '</span>';
							}
							echo '<br>';
						}
						echo '</td>';
						echo '</tr>';
					}
					echo '</tbody></table></div>';
				} else {
					echo '<p>Cette voie n’existe pas ou n’est pas utilisée pour ce tournoi.</p>';
				}
			} else {
				echo '<form method="POST">';
				echo '<h2>Evaluation -';
				echo '<select name="Tournoi" style="width: auto; border: none; background: none; color: inherit; font-weight: inherit;" onchange="this.parentNode.parentNode.submit();">';
				$query = "SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Tournois` ON `Tournois`.`Id` = `Tournois_Utilisateurs`.`Tournoi` WHERE `Tournois_Utilisateurs`.`Utilisateur` = " . $Utilisateur_Con->Id . " UNION SELECT `Tournois`.`Id`, `Tournois`.`Nom`, `Tournois`.`Date` FROM `Tournois` WHERE `Tournois`.`Id` = " . $Tournoi->Id . " ORDER BY `Date` DESC";
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
				$Nombre_de_murs = $dbh->query("SELECT COUNT(DISTINCT `Emplacements`.`Mur`) AS `NombreMur` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Tournois_Voies`.`Tournoi`= " . $Tournoi->Id)->fetchObject()->NombreMur;
				echo '<div class="table"><table><thead><tr>';
				if ($Nombre_de_murs > 1) {
					echo '<th>Mur</th>';
				}
				echo '<th>Voie</th><th>Type</th><th>Mode d’évaluation</th><th>Nombre d’essais autorisés</th>';
				echo '</tr></thead><tbody>';
				$result = $dbh->prepare("SELECT `Murs`.`Nom` AS `Mur`, `Voies`.`Id`, `Voies`.`Cotation`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2`, `Emplacements`.`Nom` AS `Emplacement`, `Tournois_Voies`.`Type`, `Tournois_Voies`.`Evaluation`, `Tournois_Voies`.`Nb_Essais_Libres`, `Tournois_Voies`.`Nb_Essais_Evalués`, `Tournois_Voies`.`Chronométrée`, `Tournois_Voies`.`Nb_Points_Absolu`, `Tournois_Voies`.`Nb_Points_Relatif` FROM `Tournois_Voies` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Tournois_Voies`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Murs` ON `Murs`.`Id` = `Emplacements`.`Mur` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Tournois_Voies`.`Tournoi`= :Tournoi ORDER BY `Murs`.`Nom`, `Emplacements`.`Ordre`, `Voies`.`Cotation`");
				$result->execute(array('Tournoi' => $Tournoi->Id));
				while ($Voie = $result->fetchObject()) {
					echo '<tr onclick="window.location.href = \'Tournois/Evaluation?Voie='.$Voie->Id.'\';" style="cursor: pointer">';
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
					echo '</tr>';
				}
				echo '</tbody></table></div>';
			}
	}
} else {
	echo '<p>Vous n’êtes pas juge sur ce tournoi.</p>';
}
require('Pied de page.inc.php');
require('Bas.inc.php');
