<?php

#test

require('Inclus/Haut.inc.php');
session_write_close();
require('autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
$Page_Ouv = 'Administration';
require('Inclus/Entête.inc.php');

switch ($_REQUEST['Action']) {
    case 'créer':
        if (droits('A', $Mur->Id)) {
            if ($_REQUEST['Type'] == 'excel') {
                echo '<h3>Importer des utilisateurs</h3>';
                echo '<form method="post" enctype="multipart/form-data">';
                echo '<input type="hidden" name="Action" value="insérer">';
                echo '<input type="hidden" name="Type" value="excel">';
                echo '<p>Téléchargez <a href="Importer utilisateurs.xlsx" target="_blank">ce fichier Excel</a>, remplissez-le, puis chargez-le ci-dessous.</p>';
                echo '<h4>Fichier</h4>';
                echo '<p><input type="file" name="Fichier" required></p>';
                echo '<input type="submit" value="Valider">';
            } else {
                echo '<h3>Nouvel utilisateur</h3>';
                echo '<form method="post">';
                echo '<input type="hidden" name="Action" value="insérer">';
                echo '<h4>Nom</h4>';
                echo '<p><input type="text" name="Nom" autocomplete="off" required></p>';
                echo '<h4>Prénom</h4>';
                echo '<p><input type="text" name="Prénom" autocomplete="off" required></p>';
                echo '<h4>Adresse électronique</h4>';
                echo '<p><input type="email" name="Adresse_électronique" autocomplete="off"></p>';
                echo '<h4>Identifiant</h4>';
                echo '<p><input type="text" name="Identifiant" autocomplete="off" required></p>';
                echo '<h4>Groupe</h4>';
                echo '<p><input type="text" name="Groupe" autocomplete="off"></p>';
                echo '<h4>Droits</h4>';
                echo '<p><select name="Droits">';
                foreach (['G' => 'Simple grimpeur', 'V' => 'Peut modifier les voies', 'P' => 'Peut accéder aux performances des grimpeurs', 'A' => 'Peut gérer les utilisateurs'] as $Clé => $Valeur) {
                    echo '<option value="'.$Clé.'">'.$Valeur.'</option>';
                }
                echo '</select></p>';
                echo '<h4>Mot de passe</h4>';
                echo '<p><input type="text" name="Mot_de_passe" autocomplete="off" required></p>';
                echo '<input type="submit" value="Valider">';
				echo '</form>';
            }
        } else {
            echo '<p>Vous n’avez pas les droits suffisants pour créer un utilisateur pour ce mur.</p>';
        }
        break;
    case 'modifier':
        unset($Utilisateur);
        if ($_REQUEST['Id'] == '') {
            $Utilisateur = $Utilisateur_Con->Id;
        } else {
            if (droits('A',$Mur->Id)) {
                if (droits('G', $Mur->Id, $_REQUEST['Id'])) {
                    $Utilisateur = $_REQUEST['Id'];
                } else {
                    echo '<p>Cet utilisateur n’existe pas ou ne fait pas parti de votre mur.</p>';
                }
            } else {
                echo '<p>Vous n’avez pas les droits suffisants pour modifier un utilisateur de ce mur.</p>';
            }
        }
        if (isset($Utilisateur)) {
            $Utilisateur = getUserFromId($Utilisateur);
            echo '<h3>Modification de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.'</h3>';
            if ($_REQUEST['mdp'] != '') {
                echo '<p style="color:red">Merci de changer votre mot de passe !</p>';
            }
            echo '<form method="post">';
            echo '<input type="hidden" name="Action" value="éditer">';
            echo '<input type="hidden" name="Id" value="'.$Utilisateur->Id.'">';
            if (droits('A',$Mur->Id)) {
                echo '<h4>Nom</h4>';
                echo '<p><input type="text" name="Nom" value="'.$Utilisateur->Nom.'" autocomplete="off" required></p>';
                echo '<h4>Prénom</h4>';
                echo '<p><input type="text" name="Prénom" value="'.$Utilisateur->Prénom.'" autocomplete="off" required></p>';
                echo '<h4>Adresse électronique</h4>';
                echo '<p><input type="email" name="Adresse_électronique" value="'.$Utilisateur->Adresse_électronique.'" autocomplete="off"></p>';
                echo '<h4>Identifiant</h4>';
                echo '<p><input type="text" name="Identifiant" value="'.$Utilisateur->Identifiant.'" autocomplete="off" autocapitalize="none" autocorrect="off" required></p>';
                $result = $dbh->query("SELECT * FROM `Mur_Utilisateurs` WHERE `Mur` = ".$Mur->Id." AND `Utilisateur` = ".$Utilisateur->Id);
                $Lien = $result->fetchObject();
                echo '<h4>Groupe</h4>';
                echo '<p><input type="text" name="Groupe" value="'.$Lien->Groupe.'"></p>';
                echo '<h4>Droits</h4>';
                echo '<p><select name="Droits">';
                foreach (['G' => 'Simple grimpeur', 'V' => 'Peut modifier les voies', 'P' => 'Peut accéder aux performances des grimpeurs', 'A' => 'Peut gérer les utilisateurs'] as $Clé => $Valeur) {
                    echo '<option value="'.$Clé.'"';
                    if ($Lien->Droits == $Clé) {
                        echo ' selected';
                    }
                    echo '>'.$Valeur.'</option>';
                }
                echo '</select></p>';
                echo '<h4>Mot de passe</h4>';
                echo '<p><input type="text" name="Mot_de_passe" placeholder="Nouveau mot de passe pour cet utilisateur (optionel)" autocomplete="off"></p>';
            } else {
                echo '<h4>Nom</h4>';
                echo '<p><input type="text" name="Nom" value="'.$Utilisateur->Nom.'" autocomplete="family-name" required></p>';
                echo '<h4>Prénom</h4>';
                echo '<p><input type="text" name="Prénom" value="'.$Utilisateur->Prénom.'" autocomplete="given-name" required></p>';
                echo '<h4>Adresse électronique</h4>';
                echo '<p><input type="email" name="Adresse_électronique" value="'.$Utilisateur->Adresse_électronique.'" autocomplete="email"></p>';
                echo '<h4>Identifiant</h4>';
                echo '<p><input type="text" name="Identifiant" value="'.$Utilisateur->Identifiant.'" autocomplete="username" autocapitalize="none" autocorrect="off" required></p>';
                echo '<h4>Ancien mot de passe</h4>';
                echo '<p><input type="password" name="Mot_de_passe" placeholder="Mot de passe actuel" autocomplete="current-password" required></p>';
                echo '<h4>Nouveau mot de passe</h4>';
                echo '<p><input type="password" name="Mot_de_passe1" placeholder="Uniquement si vous voulez changer de mot de passe" autocomplete="new-password"></p>';
                echo '<h4>Nouveau mot de passe</h4>';
                echo '<p><input type="password" name="Mot_de_passe2" placeholder="Répetez votre nouveau mot de passe" autocomplete="new-password"></p>';
            }
            echo '<input type="submit" value="Valider">';
			echo '</form>';
        }
        break;
    case 'supprimer':
        if (droits('A',$Mur->Id)) {
            if ($_REQUEST['Id'] != '') {
                $result = $dbh->prepare("SELECT `Mur_Utilisateurs`.`Utilisateur` AS `Id`, ((SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Utilisateur` = `Mur_Utilisateurs`.`Utilisateur` OR `Essais`.`Entrée_Utilisateur` = `Mur_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Mur_Utilisateurs` AS `M` WHERE `M`.`Utilisateur`  = `Mur_Utilisateurs`.`Utilisateur` AND `M`.`Mur` != `Mur_Utilisateurs`.`Mur`)+(SELECT COUNT(*) FROM `Tournois_Utilisateurs` WHERE `Tournois_Utilisateurs`.`Utilisateur` = `Mur_Utilisateurs`.`Utilisateur`)) AS `Nb_Essais` FROM `Mur_Utilisateurs` WHERE `Mur_Utilisateurs`.`Utilisateur` = :Id AND `Mur_Utilisateurs`.`Mur` = :Mur");
                $result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
                if ($result->rowCount() > 0) {
                    $Utilisateur = $result->fetchObject();
                    $User = getUserFromId($Utilisateur->Id);
                    echo '<h2>Suppression de '.$User->Prénom.' '.$User->Nom.'</h2>';
                    if ($Utilisateur->Id != $Utilisateur_Con->Id) {
                        echo '<form method="post">';
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
                    echo '<p>Cet utilisateur n’existe pas ou ne fait pas partie de ce mur.</p>';
                }
            } else {
                echo '<p>Vous devez spécifier un utilisateur.</p>';
            }
        } else {
            echo '<p>Vous n’avez pas les droits suffisants pour supprimer un utilisateur de ce mur.</p>';
        }
        break;
    default:
        if (droits('A',$Mur->Id)) {
            echo '<h2>Gestions des utilisateurs</h2>';
        } else {
            echo '<h2>'.$Utilisateur_Con->Prénom.' '.$Utilisateur_Con->Nom.'</h2>';
        }
        switch($_REQUEST['Action']) {
            case 'insérer':
                if (droits('A', $Mur->Id)) {
                    if ($_REQUEST['Type'] == 'excel') {
						if (is_uploaded_file($_FILES['Fichier']['tmp_name'])) {
							$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['Fichier']['tmp_name']);
                	        $worksheet = $spreadsheet->getActiveSheet();
                	        $Données = $worksheet->toArray();
                	        $Entêtes = array_flip($Données[0]);
                	        if (isset($Entêtes['Nom'], $Entêtes['Prénom'], $Entêtes['Adresse électronique'], $Entêtes['Identifiant'], $Entêtes['Mot de passe'], $Entêtes['Groupe'], $Entêtes['Droits'])) {
                    	        echo '<ul>';
                	            array_shift($Données);
                	            $query = "SELECT `Id`, `Nom`, `Prénom`, (SELECT MAX(`Ordre`) + 1 FROM `Mur_Utilisateurs` WHERE `Utilisateur` = `Id`) AS `Ordre`, (SELECT COUNT(*) FROM `Mur_Utilisateurs` WHERE `Utilisateur` = `Id` AND `Mur` = :Mur) AS `Membre` FROM `Utilisateurs` WHERE (`Adresse_électronique` = :Adresse AND `Adresse_électronique` != '') OR (`Identifiant` = :Identifiant AND `Identifiant` != '')";
                                $result = $dbh->prepare($query);
                                foreach ($Données as $i => $Ligne) {
									$Ligne = traiter_inputs($Ligne);
                                    if ($Ligne[$Entêtes['Nom']] != null and $Ligne[$Entêtes['Prénom']] != null and $Ligne[$Entêtes['Droits']] != null) {
                                        $result->execute(['Adresse' => $Ligne[$Entêtes['Adresse électronique']], 'Identifiant' => $Ligne[$Entêtes['Identifiant']], 'Mur' => $Mur->Id]);
                                        if ($result->rowCount() == 0) {
                                            $query = "INSERT INTO `Utilisateurs`(`Nom`, `Prénom`, `Adresse_électronique`, `Identifiant`, `Mot_de_passe`) VALUES (:Nom, :Prenom, :Adresse, :Identifiant, :MotDePasse)";
                                            $result1 = $dbh->prepare($query);
                                            if ($result1->execute(['Nom' => $Ligne[$Entêtes['Nom']], 'Prenom' => $Ligne[$Entêtes['Prénom']], 'Adresse' => $Ligne[$Entêtes['Adresse électronique']] != '' ? $Ligne[$Entêtes['Adresse électronique']] : null, 'Identifiant' => $Ligne[$Entêtes['Identifiant']] != '' ? $Ligne[$Entêtes['Identifiant']] : null, 'MotDePasse' => $Ligne[$Entêtes['Mot de passe']] != '' ? password_hash($Ligne[$Entêtes['Mot de passe']], PASSWORD_DEFAULT) : null])) {
                                                $Id = $dbh->lastInsertId();
                                                $query = "INSERT INTO `Mur_Utilisateurs`(`Mur`, `Utilisateur`, `Groupe`, `Droits`, `Ordre`) VALUES (:Mur, :Utilisateur, :Groupe, :Droits, 1)";
                                                $result2 = $dbh->prepare($query);
                                                $Utilisateur = getUserFromId($Id);
                                                if ($result2->execute(['Mur' => $Mur->Id, 'Utilisateur' => $Id, 'Groupe' => $Ligne[$Entêtes['Groupe']] != '' ? $Ligne[$Entêtes['Groupe']] : null, 'Droits' => $Ligne[$Entêtes['Droits']]])) {
                                                    echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été ajouté à la liste des utilisateurs de votre mur.</li>';
                                                } else {
                                                    echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été créé, mais n’a pu être ajouté à votre mur.</li>';
                                                }
                                            } else {
                                                echo '<li>Impossible de créer l’utilisateur '.$Ligne[$Entêtes['Prénom']].' '.$Ligne[$Entêtes['Nom']].'.</li>';
                                            }
                                        } else {
                                            $Utilisateur = $result->fetchObject();
											if ($Utilisateur->Membre == 0) {
												$query = "INSERT INTO `Mur_Utilisateurs`(`Mur`, `Utilisateur`, `Groupe`, `Droits`, `Ordre`) VALUES (:Mur, :Utilisateur, :Groupe, :Droits, :Ordre)";
												$result2 = $dbh->prepare($query);
												if ($result2->execute(['Mur' => $Mur->Id, 'Utilisateur' => $Utilisateur->Id, 'Groupe' => $Ligne[$Entêtes['Groupe']] != '' ? $Ligne[$Entêtes['Groupe']] : null, 'Droits' => $Ligne[$Entêtes['Droits']], 'Ordre' => $Utilisateur->Ordre])) {
													echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été ajouté à la liste des utilisateurs de votre mur.</li>';
												} else {
													echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' existe déjà, ligne n°'.($i+2).', mais n’a pu être ajouté à votre mur.</li>';
												}
											} else {
												echo '<li>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' existe déjà, ligne n°'.($i+2).', et il est déjà grimpeur sur votre Mur</li>';
											}
                                        }
                                    } else {
                                        echo '<li>Donnée incomplète ligne n°'.($i+2).'</li>';
                                    }
                                }
                                echo '</ul>';
                	        } else {
                	            echo '<p>Le fichier envoyer ne contient pas toutes les colonnes attendues.</p>';
                	        }
                        } else {
                            echo '<p>Vous n’avez pas chargé de fichier.</p>';
                        }
                    } else {
                        if ($_REQUEST['Nom'] != '' and $_REQUEST['Prénom'] != '' and $_REQUEST['Identifiant'] != '' and $_REQUEST['Mot_de_passe'] != '' and $_REQUEST['Droits'] != '') {
                            $query = "SELECT * FROM `Utilisateurs` WHERE `Adresse_électronique` = :Adresse OR `Identifiant` = :Identifiant";
                            $result = $dbh->prepare($query);
                            $result->execute(['Adresse' => $_REQUEST['Adresse_électronique'], 'Identifiant' => $_REQUEST['Identifiant']]);
                            if ($result->rowCount() == 0) {
                                $query = "INSERT INTO `Utilisateurs`(`Nom`, `Prénom`, `Adresse_électronique`, `Identifiant`, `Mot_de_passe`) VALUES (:Nom, :Prenom, :Adresse, :Identifiant, :MotDePasse)";
                                $result = $dbh->prepare($query);
                                if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'], 'MotDePasse' => password_hash($_REQUEST['Mot_de_passe'], PASSWORD_DEFAULT)])) {
                                    $Id = $dbh->lastInsertId();
                                    $query = "INSERT INTO `Mur_Utilisateurs`(`Mur`, `Utilisateur`, `Groupe`, `Droits`, `Ordre`) VALUES (:Mur, :Utilisateur, :Groupe, :Droits, 1)";
                                    $result = $dbh->prepare($query);
                                    $Utilisateur = getUserFromId($Id);
                                    if ($result->execute(['Mur' => $Mur->Id, 'Utilisateur' => $Id, 'Groupe' => $_REQUEST['Groupe'] != '' ? $_REQUEST['Groupe'] : null, 'Droits' => $_REQUEST['Droits']])) {
                                        echo '<p>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été ajouté à la liste des utilisateurs de votre mur.</p>';
                                    } else {
                                        echo '<p>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' a bien été créé, mais n’a pu être ajouté à votre mur.</p>';
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
                } else {
                    echo '<p>Vous n’avez pas les droits suffisants pour créer un utilisateur pour ce mur.</p>';
                }
                break;
            case 'éditer':
                if ($_REQUEST['Id'] != '' and $_REQUEST['Nom'] != '' and $_REQUEST['Prénom'] != '' and $_REQUEST['Identifiant'] != '' and (($_REQUEST['Mot_de_passe'] != '' and !droits('A',$Mur->Id)) or ($_REQUEST['Droits'] != '' and droits('A',$Mur->Id)))) {
                    $Utilisateur = getUserFromId($_REQUEST['Id']);
                    if ($Utilisateur != false and ((!droits('A',$Mur->Id) and $Utilisateur->Id == $Utilisateur_Con->Id and password_verify($_REQUEST['Mot_de_passe'],$Utilisateur->Mot_de_passe)) or (droits('A',$Mur->Id) and droits('G',$Mur->Id, $Utilisateur->Id)))) {
                        unset($Mot_de_passe);
                        if (droits('A',$Mur->Id)) {
                            if ($_REQUEST['Mot_de_passe'] != '') {
                                $Mot_de_passe = password_hash($_REQUEST['Mot_de_passe'], PASSWORD_DEFAULT);
                            }
                        } else {
                            if ($_REQUEST['Mot_de_passe1'] != '') {
                                if ($_REQUEST['Mot_de_passe1'] == $_REQUEST['Mot_de_passe2']) {
                                    $Mot_de_passe = password_hash($_REQUEST['Mot_de_passe1'], PASSWORD_DEFAULT);
                                } else {
                                    echo '<p>Les mots de passe saisis ne sont pas identiques.</p>';
                                }
                            }
                        }
                        if (isset($Mot_de_passe)) {
                            $query = "UPDATE `Utilisateurs` SET `Nom`=:Nom,`Prénom`=:Prenom,`Adresse_électronique`=:Adresse,`Identifiant`=:Identifiant,`Mot_de_passe`=:MotDePasse WHERE `Id` = :Id";
                            $result = $dbh->prepare($query);
                            if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'], 'MotDePasse' => $Mot_de_passe, 'Id' => $Utilisateur->Id])) {
                                if (droits('A',$Mur->Id)) {
                                    $query = "UPDATE `Mur_Utilisateurs` SET `Groupe`=:Groupe,`Droits`=:Droits WHERE `Mur`=:Mur AND `Utilisateur`=:Utilisateur";
                                    $result = $dbh->prepare($query);
                                    if ($result->execute(['Mur' => $Mur->Id, 'Utilisateur' => $Utilisateur->Id, 'Groupe' => $_REQUEST['Groupe'] != '' ? $_REQUEST['Groupe'] : null, 'Droits' => $_REQUEST['Droits']])) {
                                        echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour.</p>';
                                    } else {
                                        echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour, mais pas son groupe ni ses droits.</p>';
                                    }
                                } else {
                                    echo '<p>Vos informations ont bien été mise à jour.</p>';
                                }
                            }
                        } else {
                            $query = "UPDATE `Utilisateurs` SET `Nom`=:Nom,`Prénom`=:Prenom,`Adresse_électronique`=:Adresse,`Identifiant`=:Identifiant WHERE `Id` = :Id";
                            $result = $dbh->prepare($query);
                            if ($result->execute(['Nom' => $_REQUEST['Nom'], 'Prenom' => $_REQUEST['Prénom'], 'Adresse' => $_REQUEST['Adresse_électronique'] != '' ? $_REQUEST['Adresse_électronique'] : null, 'Identifiant' => $_REQUEST['Identifiant'], 'Id' => $Utilisateur->Id])) {
                                if (droits('A',$Mur->Id)) {
                                    $query = "UPDATE `Mur_Utilisateurs` SET `Groupe`=:Groupe,`Droits`=:Droits WHERE `Mur`=:Mur AND `Utilisateur`=:Utilisateur";
                                    $result = $dbh->prepare($query);
                                    if ($result->execute(['Mur' => $Mur->Id, 'Utilisateur' => $Utilisateur->Id, 'Groupe' => $_REQUEST['Groupe'] != '' ? $_REQUEST['Groupe'] : null, 'Droits' => $_REQUEST['Droits']])) {
                                        echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour.</p>';
                                    } else {
                                        echo '<p>Les informations de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' ont bien été mises à jour, mais pas son groupe ni ses droits.</p>';
                                    }
                                } else {
                                    echo '<p>Vos informations ont bien été mise à jour.</p>';
                                }
                            } 
                        }
                    } else {
                        if ($Utilisateur->Id == $Utilisateur_Con->Id) {
                            echo '<p>Le mot de passe saisi n’est pas le bon.</p>';
                        } else {
                            echo '<p>Cet utilisateur n’existe pas ou ne fait pas parti de votre mur.</p>';
                        }
                    }
                } else {
                    echo '<p>Les données saisies sont incomplètes.</p>';
                }
                break;
            case 'retirer':
                if (droits('A',$Mur->Id)) {
                    if ($_REQUEST['Id'] != '') {
                        $result = $dbh->prepare("SELECT `Mur_Utilisateurs`.`Utilisateur` AS `Id`, ((SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Utilisateur` = `Mur_Utilisateurs`.`Utilisateur` OR `Essais`.`Entrée_Utilisateur` = `Mur_Utilisateurs`.`Utilisateur`)+(SELECT COUNT(*) FROM `Mur_Utilisateurs` AS `M` WHERE `M`.`Utilisateur`  = `Mur_Utilisateurs`.`Utilisateur` AND `M`.`Mur` != `Mur_Utilisateurs`.`Mur`)+(SELECT COUNT(*) FROM `Tournois_Utilisateurs` WHERE `Tournois_Utilisateurs`.`Utilisateur` = `Mur_Utilisateurs`.`Utilisateur`)) AS `Nb_Essais` FROM `Mur_Utilisateurs` WHERE `Mur_Utilisateurs`.`Utilisateur` = :Id AND `Mur_Utilisateurs`.`Mur` = :Mur");
                        $result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
                        if ($result->rowCount() > 0) {
                            $Utilisateur = $result->fetchObject();
                            if ($Utilisateur->Id != $Utilisateur_Con->Id) {
                                $query = "DELETE FROM `Mur_Utilisateurs` WHERE `Mur` = ".$Mur->Id." AND `Utilisateur` = ".$Utilisateur->Id;
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
                            echo '<p>Cet utilisateur n’existe pas ou ne fait pas partie de ce mur.</p>';
                        }
                    } else {
                        echo '<p>Vous devez spécifier un utilisateur.</p>';
                    }
                } else {
                    echo '<p>Vous n’avez pas les droits suffisants pour supprimer un utilisateur de ce mur.</p>';
                }
                break;
        }
        if (droits('A',$Mur->Id)) {
            echo '<form method="post" id="créer">';
            echo '<input type="hidden" name="Action" value="créer">';
            echo '</form>';
            echo '<form method="post" id="créerExcel">';
            echo '<input type="hidden" name="Action" value="créer">';
            echo '<input type="hidden" name="Type" value="excel">';
            echo '</form>';
            echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter un utilisateur"> | <input type="submit" form="créerExcel" class="link" value="Importer des utilisateurs"></p>';
			echo '<form method="post">';
			echo '<p><select name="Groupe" onchange="this.parentNode.parentNode.submit()">';
			echo '<option value="">Tous les groupes</option>';
			$result = $dbh->query("SELECT DISTINCT `Groupe` AS `Nom` FROM `Mur_Utilisateurs` WHERE `Mur` = ".$Mur->Id." ORDER BY `Nom`");
			while ($Groupe = $result->fetchObject()) {
				echo '<option value="'.$Groupe->Nom.'"';
				if ($_REQUEST['Groupe'] == $Groupe->Nom) {
					echo ' selected';
				}
				echo '>'.$Groupe->Nom.'</option>';
			}
			echo '</select>';
			echo '</form>';
            echo '<div class="table"><table><thead><tr><th>Nom</th><th>Prénom</th><th>Adresse électronique</th><th>Identifiant</th><th>Groupe</th><th>Droits</th><th class="noPrint"></th></tr></thead><tbody>';
			if ($_REQUEST['Groupe'] != '') {
				$result = $dbh->prepare("SELECT `Utilisateurs`.`Id`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`, `Utilisateurs`.`Adresse_électronique`, `Utilisateurs`.`Identifiant`, `Mur_Utilisateurs`.`Groupe`, `Mur_Utilisateurs`.`Droits` FROM `Mur_Utilisateurs` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Mur_Utilisateurs`.`Utilisateur` WHERE `Mur_Utilisateurs`.`Mur` = :Mur AND `Mur_Utilisateurs`.`Groupe` = :Groupe ORDER BY `Mur_Utilisateurs`.`Groupe`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`");
				$result->execute(array('Mur' => $Mur->Id, 'Groupe' => $_REQUEST['Groupe']));
			} else {
				$result = $dbh->prepare("SELECT `Utilisateurs`.`Id`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`, `Utilisateurs`.`Adresse_électronique`, `Utilisateurs`.`Identifiant`, `Mur_Utilisateurs`.`Groupe`, `Mur_Utilisateurs`.`Droits` FROM `Mur_Utilisateurs` LEFT OUTER JOIN `Utilisateurs` ON `Utilisateurs`.`Id` = `Mur_Utilisateurs`.`Utilisateur` WHERE `Mur_Utilisateurs`.`Mur` = :Mur ORDER BY `Mur_Utilisateurs`.`Groupe`, `Utilisateurs`.`Nom`, `Utilisateurs`.`Prénom`");
				$result->execute(array('Mur' => $Mur->Id));
			}
            while ($Utilisateur = $result->fetchObject()) {
                echo '<tr>';
                echo '<td>'.$Utilisateur->Nom.'</td>';
                echo '<td>'.$Utilisateur->Prénom.'</td>';
                echo '<td>'.$Utilisateur->Adresse_électronique.'</td>';
                echo '<td>'.$Utilisateur->Identifiant.'</td>';
                echo '<td>'.$Utilisateur->Groupe.'</td>';
                echo '<td>'.$Utilisateur->Droits.'</td>';
                echo '<td class="noPrint">';
                echo '<form method="post" class="icon">';
                echo '<input type="hidden" name="Action" value="modifier">';
                echo '<input type="hidden" name="Id" value=' . $Utilisateur->Id . '>';
                echo '<input type="submit" title="Modifier cette voie" value="✏️">';
                echo '</form>';
                echo '<form method="post" class="icon">';
                echo '<input type="hidden" name="Action" value="supprimer">';
                echo '<input type="hidden" name="Id" value=' . $Utilisateur->Id . '>';
                echo '<input type="submit" title="Supprimer cette voie" value="🗑️️">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        } else {
            $Utilisateur = getUserFromId($Utilisateur_Con->Id);
            echo '<form method="post" id="modifier">';
            echo '<input type="hidden" name="Action" value="modifier">';
            echo '</form>';
            echo '<p><input type="submit" form="modifier" class="link" value="Modifier mes informations"></p>';
            echo '<h4>Nom</h4>';
            echo '<p>'.$Utilisateur->Nom.'</p>';
            echo '<h4>Prénom</h4>';
            echo '<p>'.$Utilisateur->Prénom.'</p>';
            echo '<h4>Adresse électronique</h4>';
            echo '<p>'.$Utilisateur->Adresse_électronique.'</p>';
            echo '<h4>Identifiant</h4>';
            echo '<p>'.$Utilisateur->Identifiant.'</p>';
        }
}

require('Inclus/Pied de page.inc.php');
require('Inclus/Bas.inc.php');

?>