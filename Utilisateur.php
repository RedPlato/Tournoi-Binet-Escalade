<?php

require('Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Utilisateur';
require('Inclus/Entête.inc.php');

function afficheInfosGrimpeur($uId) {
    global $dbh, $Mur;
    
    $Voie = voiePréférée($uId, $Mur->Id);
    if ($Voie != false) {
        echo '<h4>Voie préférée</h4>';
        echo '<p>' . afficheVoieFromId($Voie->Id) . ' avec ' . $Voie->nb . ' essais.';
    }
    
    $niveau = niveauMax($uId);
    if ($niveau != false) {
        echo '<h4>Niveau max atteint</h4>';
        echo '<p>' . $niveau->Cotation . ' le  ' . date_create($niveau->Date)->format('d/m/Y \à H\hi') . ' sur la '.afficheVoieFromId($niveau->Id).'</p>';
    }
$niveau = niveauMaxRéel($uId);
    if ($niveau != false) {
        echo '<h4>Niveau réel</h4>';
        echo '<p>' . $niveau->Cotation . ' le  ' . date_create($niveau->Date)->format('d/m/Y \à H\hi') . ' sur la '.afficheVoieFromId($niveau->Id).'</p>';
} 

    $result = idVoiesQuiBloquent($uId, $Mur->Id);
    if ($result->rowCount() > 0) {
        echo '<h4>Voies bloquantes</h4>';
        echo '<ul>';
        while ($Voie = $result->fetchObject()) {
            echo '<li>'.afficheVoieFromId($Voie->Id).' avec au maximum '.$Voie->Max.' dégaines clipées</li>';
        }
        echo '</ul>';
    }

    $result = $dbh->prepare("SELECT `Essais`.`Id`, Voies.Cotation, Essais.Date, Emplacements.Nom AS `Emplacement`, Couleurs.Nom AS 'Couleur', Essais.Mode, Essais.Nb_Pauses, Essais.Nb_Chutes, Essais.Réussite, Emplacements.Inclinaison, Emplacements.`Nb_Dégaines`, Couleurs.Code_1, Couleurs.Code_2  FROM Essais LEFT OUTER JOIN Voies ON Voies.Id=Essais.Voie LEFT OUTER JOIN Emplacements ON Voies.Emplacement=Emplacements.Id LEFT OUTER JOIN Couleurs ON Voies.Couleur=Couleurs.Id WHERE Emplacements.Mur= :Mur AND Essais.Utilisateur= :Utilisateur ORDER BY Essais.Date DESC");
    $result->execute(array(
        'Mur' => $Mur->Id,
        'Utilisateur' => $uId,
    ));
    echo '<div class="table"><table><thead><tr><th>Séance</th><th>Voie</th><th>Mode</th><th>Nombre de pauses</th><th>Nombre de chutes</th><th>Progression</th><th class="noPrint"></th></tr></thead><tbody>';
    if ($result->rowCount() > 0) {
        $seance = null;
        $count = 0;
        $code = '';
        while ($Voie = $result->fetchObject()) {
            if ($seance != null) {
                $Diff = date_create($Voie->Date)->diff($seance,true);
                if ($Diff->h+24*($Diff->d+30*($Diff->m+12*$Diff->y)) > 1) {
					$seance->setTime($seance->format('H'), intdiv($seance->format('i'),30)*30);
                    echo '<tr><td rowspan="'.$count.'">'.$seance->format('d/m/Y \à H\hi').'</td>'.$code;
                    $count = 0;
                    $code = '';
                } else {
                    $code .= '<tr>';
                }
            }
            $count++;
            $seance = date_create($Voie->Date);
            $code .= '<td style="';
            if ($Voie->Code_2 != null) {
                $code .= 'background-image: linear-gradient(to bottom right, #' . $Voie->Code_1 . ' 25%, #' . $Voie->Code_2 . ' 75%);';
            } else {
                $code .= 'background-color: #' . $Voie->Code_1 . ';';
            }
            $code .= ' color: ' . couleur_text('#' . $Voie->Code_1) . '">'.$Voie->Cotation.' sur l’emplacement '.$Voie->Emplacement.'</td>';
            $code .= '<td>'.ucfirst(Modes()[$Voie->Mode]).'</td>';
            $code .= '<td>'.$Voie->Nb_Pauses.'</td>';
            $code .= '<td>'.$Voie->Nb_Chutes.'</td>';
            if ($Voie->Réussite != null) {
                $code .= '<td style="background-color: red; color: white">'.round(100*$Voie->Réussite/$Voie->Nb_Dégaines).' %</td>';
            } elseif ($Voie->Nb_Chutes > 0) {
                $code .= '<td style="background-color: orange; color: white">100 %</td>';
            } elseif ($Voie->Nb_Pauses > 0) {
                $code .= '<td style="background-color: blue; color: white">100 %</td>';
            } else {
                $code .= '<td style="background-color: green; color: white">100 %</td>';
            }
            $code .= '<td class="noPrint">';
            $code .= '<form method="post" class="icon">';
            $code .= '<input type="hidden" name="Action" value="supprimer">';
            $code .= '<input type="hidden" name="Id" value=' . $Voie->Id . '>';
            $code .= '<input type="hidden" name="uId" value='.$_REQUEST['uId'].'>';
            $code .= '<input type="submit" title="Supprimer cet essai" value="🗑️️">';
            $code .= '</form>';
            $code .= '</td>';
            $code .= '</tr>';
        }
        echo '<tr><td rowspan="'.$count.'">'.strftime("%A %d %B %Y à %Hh", $seance->format('U')).'</td>'.$code;
    } else {
        echo '<tr><td colspan="6">Vous n’avez pas encore enregistré de grimpe.</td></tr>';
    }
    echo '</tbody></table></div>';
}

switch ($_REQUEST['Action']) {
    case 'supprimer':
        if ($_REQUEST['Id'] != '') {
            $result = $dbh->prepare("SELECT `Essais`.`Id`, `Essais`.`Utilisateur`, `Essais`.`Voie`, `Essais`.`Date`, `Emplacements`.`Mur` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Essais`.`Id` = :Id");
            $result->execute(['Id' => $_REQUEST['Id']]);
            if ($result->rowCount() > 0) {
                $Essai = $result->fetchObject();
                if ($Essai->Utilisateur == $Utilisateur_Con->Id or (droits('P', $Essai->Mur) and droits('G', $Essai->Mur,$Essai->Utilisateur))) {
                    echo '<h2>Suppression de l’essai du '.date_create($Essai->Date)->format('d/m/Y \à H:i').'</h2>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="Action" value="retirer">';
                    echo '<input type="hidden" name="Id" value="'.$Essai->Id.'">';
                    echo '<input type="hidden" name="uId" value='.$_REQUEST['uId'].'>';
                    if ($Essai->Utilisateur != $Utilisateur_Con->Id) {
                        $Utilisateur = getUserFromId($Essai->Utilisateur);
                        echo '<p>'.$Utilisateur->Prénom.' '.$Utilisateur->Nom.'</p>';
                    }
                    echo '<p>'.afficheVoieFromId($Essai->Voie).'</p>';
                    echo '<p>Êtes-vous sûr de vouloir supprimer cet essai&nbsp?</p>';
                    echo '<p><input type="submit" value="Supprimer cet essai"></p>';
                    echo '</form>';
                } else {
                    echo '<p>Vous n’avez pas le droit de supprimer cet essai.</p>';
                }
            } else {
                echo '<p>Cet essai n’existe pas.</p>';
            }
        } else {
            echo '<p>Vous devez spécifier un essai.</p>';
        }
        break;
    default:
        switch ($_REQUEST['Action']) {
            case 'retirer':
                if ($_REQUEST['Id'] != '') {
                    $result = $dbh->prepare("SELECT `Essais`.`Id`, `Essais`.`Utilisateur`, `Essais`.`Voie`, `Essais`.`Date`, `Emplacements`.`Mur` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Essais`.`Id` = :Id");
                    $result->execute(['Id' => $_REQUEST['Id']]);
                    if ($result->rowCount() > 0) {
                        $Essai = $result->fetchObject();
                        if ($Essai->Utilisateur == $Utilisateur_Con->Id or (droits('P', $Essai->Mur) and droits('G', $Essai->Mur,$Essai->Utilisateur))) {
                            $query = $dbh->prepare("DELETE FROM `Essais` WHERE `Id` = :Id");
                            if ($query->execute(array('Id' => $Essai->Id))) {
                                if ($Essai->Utilisateur == $Utilisateur_Con->Id) {
                                    echo '<p>Votre essai du '.date_create($Essai->Date)->format('d/m/Y \à H:i').' sur la '.afficheVoieFromId($Essai->Voie).' a bien été supprimé.</p>';
                                } else {
                                    $Utilisateur = getUserFromId($Essai->Utilisateur);
                                    echo '<p>L’essai de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.' du '.date_create($Essai->Date)->format('d/m/Y \à H:i').' sur la '.afficheVoieFromId($Essai->Voie).' a bien été supprimé.</p>';
                                }
                                $dbh->query("ALTER TABLE `Essais` auto_increment = 1");
                            } else {
                                echo '<p>Une erreur est survenue.</p>';
                            }
                        } else {
                            echo '<p>Vous n’avez pas le droit de supprimer cet essai.</p>';
                        }
                    } else {
                        echo '<p>Cet essai n’existe pas.</p>';
                    }
                } else {
                    echo '<p>Vous devez spécifier un essai.</p>';
                }
                break;
        }
        if (droits('P', $Mur->Id)) {
            if ($_REQUEST['uId'] != '') {
                $result = $dbh->prepare("SELECT * FROM `Utilisateurs` WHERE `Id` = :Id");
                $result->execute(['Id' => $_REQUEST['uId'] ]);
                if ($result->rowCount() > 0) {
                    $Utilisateur = $result->fetchObject();
                    echo '<h2>Suivi de '.$Utilisateur->Prénom.' '.$Utilisateur->Nom.'</h2>';
                    echo '<p class="noPrint"><a href="Utilisateur">Retour vers la liste des utilisateurs</a></p>';
                    echo afficheInfosGrimpeur($Utilisateur->Id);
                }
            } else {
                echo '<h2>Suivi des grimpeurs</h2>';
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
                echo '<div class="table"><table><thead><tr><th>Groupe</th><th>Nom</th><th>Prénom</th><th>Niveau</th><th>Nombre de voies grimpées en moyenne par jours</th><th class="noPrint">Voir plus</th></thead><tbody>';
				if ($_REQUEST['Groupe'] != '') {
					$result = $dbh->prepare("SELECT Utilisateurs.Id AS uId, Utilisateurs.Prénom,Utilisateurs.Nom, `Mur_Utilisateurs`.`Groupe`, MAX(Voies.Cotation) AS `max` FROM Essais LEFT OUTER JOIN Utilisateurs on Utilisateurs.Id=Essais.Utilisateur LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Mur_Utilisateurs` ON `Mur_Utilisateurs`.`Mur` = `Emplacements`.`Mur` AND `Mur_Utilisateurs`.`Utilisateur` = `Utilisateurs`.`Id` WHERE `Emplacements`.`Mur` = ".$Mur->Id." AND Essais.Réussite is NULL AND `Nb_Pauses` = 0 AND `Nb_Chutes` = 0 AND `Mur_Utilisateurs`.`Groupe` = :Groupe GROUP BY Utilisateurs.Id ORDER BY `Mur_Utilisateurs`.`Groupe`, Utilisateurs.Nom, Utilisateurs.Prénom");
					$result->execute(['Groupe' => $_REQUEST['Groupe']]);
				} else {
					$result = $dbh->query("SELECT Utilisateurs.Id AS uId, Utilisateurs.Prénom,Utilisateurs.Nom, `Mur_Utilisateurs`.`Groupe`, MAX(Voies.Cotation) AS `max` FROM Essais LEFT OUTER JOIN Utilisateurs on Utilisateurs.Id=Essais.Utilisateur LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Mur_Utilisateurs` ON `Mur_Utilisateurs`.`Mur` = `Emplacements`.`Mur` AND `Mur_Utilisateurs`.`Utilisateur` = `Utilisateurs`.`Id` WHERE `Emplacements`.`Mur` = ".$Mur->Id." AND Essais.Réussite is NULL AND `Nb_Pauses` = 0 AND `Nb_Chutes` = 0 GROUP BY Utilisateurs.Id ORDER BY `Mur_Utilisateurs`.`Groupe`, Utilisateurs.Nom, Utilisateurs.Prénom");
				}
                while ($User = $result->fetchObject()) {
                    echo '<tr>';
                    echo '<td>' . $User->Groupe . '</td>';
                    echo '<td>' . $User->Nom . '</td>';
                    echo '<td>' . $User->Prénom . '</td>';
                    echo '<td>' . $User->max . '</td>';
                    echo '<td>'.round($dbh->query("SELECT AVG(`Nb`) AS `Moyenne` FROM (SELECT COUNT(*) AS `Nb` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Emplacements`.`Mur` = ".$Mur->Id." AND `Essais`.`Utilisateur` = ".$User->uId." GROUP BY DATE(`Essais`.`Date`)) AS `Select`")->fetchObject()->Moyenne,1).'</td>';
                    echo '<td class="noPrint"><form method="post">';
                    echo '<input type="hidden" name="uId" value=' . $User->uId . '>';
                    echo '<input type="submit" value="Voir les infos">';
                    echo '</form></td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
                echo '<div>Nombre de grimpeur aujourd\'hui : '.($dbh->query("SELECT count(DISTINCT Utilisateur) AS `Nombre` from Essais WHERE DATE(Date)=DATE(NOW())
")->fetchObject()->Nombre).'</div>';
            }
        } else {
            echo afficheInfosGrimpeur($Utilisateur_Con->Id);
        }
}

require('Inclus/Pied de page.inc.php');
require('Inclus/Bas.inc.php');

?>