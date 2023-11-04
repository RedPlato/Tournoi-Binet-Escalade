<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Voies';
require('Entête.inc.php');

switch ($_REQUEST['Action']) {
    case 'créer':
        //Formulaire de nouvelle voie
        if (droits('V',$Mur->Id)) {
            echo '<h2>Saissisez votre nouvelle voie</h2>';
            echo '<form method="post" enctype="multipart/form-data">';
            echo '<input type="hidden" name="Action" value="insérer">';
            echo '<h4>Emplacement</h4>';
            echo '<p><select name="Emplacement">';
            $query = "SELECT `Id`, `Nom` FROM `Emplacements` WHERE `Mur` = ".$Mur->Id." AND `Ordre` IS NOT NULL ORDER BY `Ordre`";
            $result = $dbh->query($query);
            while ($Emplacement = $result->fetchObject()) {
                echo '<option value="' . $Emplacement->Id . '">' . ucfirst($Emplacement->Nom) . '</option>';
            }
            echo '</select></p>';
            echo '<h4>Couleur</h4>';
            echo '<p><select name="Couleur" id="Couleur">';
            $query = "SELECT `Id`, `Nom` FROM `Couleurs` WHERE 1 ORDER BY `Nom`";
            $result = $dbh->query($query);
            while ($Couleur = $result->fetchObject()) {
                echo '<option value="' . $Couleur->Id . '">' . ucfirst($Couleur->Nom) . '</option>';
            }
            echo '</select></p>';
            echo '<h4>Cotation</h4>';
            echo '<p><input type="text" name="Cotation" list="Cotation" required></p>';
            echo '<datalist id="Cotation">';
            $query = "SELECT DISTINCT `Cotation` FROM `Voies` ORDER BY `Cotation`";
            $result = $dbh->query($query);
            while ($Cotation = $result->fetchObject()) {
                echo '<option>' . $Cotation->Cotation . '</option>';
            }
            echo '</datalist>';
            echo '<h4>Description</h4>';
            echo '<p><textarea name="Description"></textarea></p>';
            echo '<h4>Photo</h4>';
            echo '<p><input type="file" name="Photo" id="Photo" accept="image/*" onchange="previewFile(this,\'imagePreview\')"></p>';
            echo '<p style="text-align: center; display: none;"><img src="" id="imagePreview" style="max-width: 100%; max-height: 20rem;"><br><button type="button" class="link" onclick="document.getElementById(\'Photo\').value=null; previewFile(document.getElementById(\'Photo\'),\'imagePreview\')">Supprimer la photo</button></p>';
            echo '<h4>Vidéo</h4>';
            echo '<p><input type="file" name="Vidéo" id="Vidéo" accept="video/*" onchange="previewFile(this,\'videoPreview\')"></p>';
            echo '<p style="text-align: center; display: none;"><video src="" id="videoPreview" controls style="max-width: 100%; max-height: 20rem;"></video><br><button type="button" class="link" onclick="document.getElementById(\'Vidéo\').value=null; previewFile(document.getElementById(\'Vidéo\'),\'videoPreview\')">Supprimer la vidéo</button></p>';
            echo '<p><input type="submit" value="Valider"></p>';
            echo '</form>';
            echo "<script>
                    function previewFile(f,p) {
                        var file = f.files[0];
                        var preview = document.getElementById(p);
                        var reader = new FileReader();
                    
                        reader.addEventListener('load', function () {
                            preview.src = reader.result;
                        }, false);
                    
                        if (file) {
                            reader.readAsDataURL(file);
                            preview.parentNode.style.display = '';
                        } else {
                            preview.parentNode.style.display = 'none';
                        }
                    }
                </script>";
        } else {
            echo '<p>Vous n’avez pas les droits suffisants pour ajouter une voie sur ce mur.</p>';
        }
        break;
    case 'modifier':
        if (droits('V',$Mur->Id)) {
            if ($_REQUEST['Id'] != '') {
                $result = $dbh->prepare("SELECT `Voies`.`Id`, `Voies`.`Emplacement`, `Voies`.`Couleur`, `Voies`.`Cotation`, `Voies`.`Description`, `Voies`.`Photo`, `Voies`.`Vidéo` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Voies`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
                $result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
                if ($result->rowCount() > 0) {
                    $Voie = $result->fetchObject();
                    echo '<h2>Modification  de '.afficheVoieFromId($Voie->Id,false).'</h2>';
                    echo '<form method="post" enctype="multipart/form-data">';
                    echo '<input type="hidden" name="Action" value="éditer">';
                    echo '<input type="hidden" name="Id" value="'.$Voie->Id.'">';
                    echo '<h4>Emplacement</h4>';
                    echo '<p><select name="Emplacement">';
                    $query = "SELECT `Id`, `Nom` FROM `Emplacements` WHERE `Mur` = ".$Mur->Id." AND `Ordre` IS NOT NULL ORDER BY `Ordre`";
                    $result = $dbh->query($query);
                    while ($Emplacement = $result->fetchObject()) {
                        echo '<option value="'.$Emplacement->Id.'" ';
                        if ($Voie->Emplacement == $Emplacement->Id) {
                            echo 'selected';
                        }
                        echo '>' . ucfirst($Emplacement->Nom) . '</option>';
                    }
                    echo '</select></p>';
                    echo '<h4>Couleur</h4>';
                    echo '<p><select name="Couleur" id="Couleur">';
                    $query = "SELECT `Id`, `Nom` FROM `Couleurs` WHERE 1 ORDER BY `Nom`";
                    $result = $dbh->query($query);
                    while ($Couleur = $result->fetchObject()) {
                        echo '<option value="'.$Couleur->Id.'" ';
                        if ($Voie->Couleur == $Couleur->Id) {
                            echo 'selected';
                        }
                        echo '>' . ucfirst($Couleur->Nom) . '</option>';
                    }
                    echo '</select></p>';
                    echo '<h4>Cotation</h4>';
                    echo '<p><input type="text" name="Cotation" value="'.$Voie->Cotation.'" list="Cotation" required></p>';
                    echo '<datalist id="Cotation">';
                    $query = "SELECT DISTINCT `Cotation` FROM `Voies` ORDER BY `Cotation`";
                    $result = $dbh->query($query);
                    while ($Cotation = $result->fetchObject()) {
                        echo '<option>' . $Cotation->Cotation . '</option>';
                    }
                    echo '</datalist>';
                    echo '<h4>Description</h4>';
                    echo '<p><textarea name="Description">'.$Voie->Description.'</textarea></p>';
                    echo '<h4>Photo</h4>';
                    echo '<p><input type="file" name="Photo" id="Photo" accept="image/*" onchange="previewFile(this,\'imagePreview\')"></p>';
                    if ($Voie->Photo != null) {
                        echo '<p style="text-align: center;"><img src="Medias/'.$Voie->Photo.'" ';
                    } else {
                        echo '<p style="text-align: center; display: none;"><img src="" ';
                    }
                    echo 'id="imagePreview" style="max-width: 100%; max-height: 20rem;"><br><button type="button" class="link" onclick="document.getElementsByName(\'deletePhoto\')[0].value =\'true\'; document.getElementById(\'Photo\').value=null; previewFile(document.getElementById(\'Photo\'),\'imagePreview\')">Supprimer la photo</button></p>';
                    echo '<input type="hidden" name="deletePhoto" value="false">';
                    echo '<h4>Vidéo</h4>';
                    echo '<p><input type="file" name="Vidéo" id="Vidéo" accept="video/*" onchange="previewFile(this,\'videoPreview\')"></p>';
                    if ($Voie->Vidéo != null) {
                        echo '<p style="text-align: center;"><video src="Medias/'.$Voie->Vidéo.'" ';
                    } else {
                        echo '<p style="text-align: center; display: none;"><video src="" ';
                    }
                    echo 'id="videoPreview" controls style="max-width: 100%; max-height: 20rem;"></video><br><button type="button" class="link" onclick="document.getElementsByName(\'deleteVideo\')[0].value =\'true\'; document.getElementById(\'Vidéo\').value=null; previewFile(document.getElementById(\'Vidéo\'),\'videoPreview\')">Supprimer la vidéo</button></p>';
                    echo '<input type="hidden" name="deleteVideo" value="false">';
                    echo '<p><input type="submit" value="Valider"></p>';
                    echo '</form>';
                    echo "<script>
                            function previewFile(f,p) {
                                var file = f.files[0];
                                var preview = document.getElementById(p);
                                var reader = new FileReader();
                            
                                reader.addEventListener('load', function () {
                                    preview.src = reader.result;
                                }, false);
                            
                                if (file) {
                                    reader.readAsDataURL(file);
                                    preview.parentNode.style.display = '';
                                } else {
                                    preview.parentNode.style.display = 'none';
                                }
                            }
                        </script>";
                    echo '</form>';
                } else {
                    echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce mur.</p>';
                }
            } else {
                echo '<p>Vous devez spécifier une voie.</p>';
            }
        } else {
            echo '<p>Vous n’avez pas les droits suffisants pour modifier une voie sur ce mur.</p>';
        }
        break;
    case 'supprimer':
        if (droits('V',$Mur->Id)) {
            if ($_REQUEST['Id'] != '') {
                $result = $dbh->prepare("SELECT `Voies`.`Id`, (SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Voie` = `Voies`.`Id`) AS `Nb_Essais` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Voies`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
                $result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
                if ($result->rowCount() > 0) {
                    $Voie = $result->fetchObject();
                    echo '<h2>Suppression de '.afficheVoieFromId($Voie->Id, false).'</h2>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="Action" value="retirer">';
                    echo '<input type="hidden" name="Id" value="'.$Voie->Id.'">';
                    if ($Voie->Nb_Essais > 0) {
                        echo '<p>Des grimpeurs ont déjà grimpé cette voie. Elle ne peut donc pas être supprimée. Vous pouvez uniquement la désactiver.</p>';
                        echo '<p><input type="submit" value="Désactiver cette voie"></p>';
                    } else {
                        echo '<p>Êtes-vous sûr de vouloir supprimer cette voie&nbsp?</p>';
                        echo '<p><input type="submit" value="Supprimer cette voie"></p>';
                    }
                    echo '</form>';
                } else {
                    echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce mur.</p>';
                }
            } else {
                echo '<p>Vous devez spécifier une voie.</p>';
            }
        } else {
            echo '<p>Vous n’avez pas les droits suffisants pour supprimer une voie sur ce mur.</p>';
        }
        break;
    default:
        echo '<form method="POST">';
		if ($_REQUEST['Vue'] == 'Ancienne') {
            echo '<h2>Gestion des anciennes voies -';
        } else {
            echo '<h2>Gestion des voies -';
        }
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
		echo '</select></h2>';
		echo '</form>';
        switch ($_REQUEST['Action']) {
            case 'insérer':
                if (droits('V',$Mur->Id)) {
                    if ($_REQUEST['Emplacement'] != '' and $_REQUEST['Couleur'] != '' and $_REQUEST['Cotation'] != '') {
                        $result = $dbh->prepare("SELECT `Id` FROM `Emplacements` WHERE `Mur` = :Mur AND `Id` = :Id");
                        $result->execute(array(
                            'Mur' => $Mur->Id,
                            'Id' => $_REQUEST['Emplacement']
                        ));
                        if ($result->rowCount() > 0) {
                            $Emplacement = $result->fetchObject();
                            $result = $dbh->prepare("SELECT `Id` FROM `Couleurs` WHERE `Id` = :Id");
                            $result->execute(array(
                                'Id' => $_REQUEST['Couleur']
                            ));
                            if ($result->rowCount() > 0) {
                                $Couleur = $result->fetchObject();
                                if ($_FILES['Photo']['name'] != '') {
                                    $extansion = array_reverse(explode('.',$_FILES['Photo']['name']))[0];
                                    do {
                                        $Photo = bin2hex(random_bytes(16)).'.'.$extansion;
                                    } while (file_exists('../Medias/'.$Photo));
                                    if (!move_uploaded_file($_FILES['Photo']['tmp_name'], '../Medias/'.$Photo)) {
                                        unset($Photo);
                                        echo '<p>Une erreur est survenue lors du chargement de la photo.</p>';
                                    }
                                }
                                if ($_FILES['Vidéo']['tmp_name'] != '') {
                                    $extansion = array_reverse(explode('.',$_FILES['Vidéo']['name']))[0];
                                    do {
                                        $Video = bin2hex(random_bytes(16)).'.'.$extansion;
                                    } while (file_exists('../Medias/'.$Video));
                                    if (!move_uploaded_file($_FILES['Vidéo']['tmp_name'], '../Medias/'.$Video)) {
                                        unset($Video);
                                        echo '<p>Une erreur est survenue lors du chargement de la video.</p>';
                                    }
                                }
                                $query = $dbh->prepare("INSERT INTO `Voies`(`Emplacement`, `Couleur`, `Cotation`, `Active`, `DateCréation`, `Description`, `Photo`, `Vidéo`) VALUES(:Emplacement, :Couleur, :Cotation, '1', NOW(), :Description, :Photo, :Video)");
                                if ($query->execute(array(
                                    'Emplacement' => $Emplacement->Id,
                                    'Couleur' => $Couleur->Id,
                                    'Cotation' => $_REQUEST['Cotation'],
                                    'Description' => $_REQUEST['Description'] != '' ? $_REQUEST['Description'] : null,
                                    'Photo' => isset($Photo) ? $Photo : null,
                                    'Video' => isset($Video) ? $Video : null
                                ))) {
                                    echo '<p>Insertion terminée de la '.afficheVoieFromId($dbh->lastInsertId(),false).'.</p>';
                                } else {
                                    echo '<p>Une erreur est survenue lors de l’ajout de cette voie.</p>';
                                }
                            } else {
                                echo '<p>Cette couleur n’existe pas.</p>';
                            }
                        } else {
                            echo '<p>Cet emplacement n’existe pas ou ne fait pas parti de votre mur.</p>';
                        }
                    } else {
                        echo '<p>Les données saisies ne sont pas complètes.</p>';
                    }
                } else {
                    echo '<p>Vous n’avez pas les droits suffisants pour ajouter une voie sur ce mur.</p>';
                }
                break;
            case 'éditer':
                if (droits('V',$Mur->Id)) {
                    if ($_REQUEST['Id'] != '' and $_REQUEST['Emplacement'] != '' and $_REQUEST['Couleur'] != '' and $_REQUEST['Cotation'] != '') {
                        $result = $dbh->prepare("SELECT `Voies`.`Id`, `Voies`.`Photo`, `Voies`.`Vidéo` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Emplacements`.`Mur` = :Mur AND `Voies`.`Id` = :Id");
                        $result->execute(array(
                            'Mur' => $Mur->Id,
                            'Id' => $_REQUEST['Id']
                        ));
                        if ($result->rowCount() > 0) {
                            $Voie = $result->fetchObject();
                            $result = $dbh->prepare("SELECT `Id` FROM `Emplacements` WHERE `Mur` = :Mur AND `Id` = :Id");
                            $result->execute(array(
                                'Mur' => $Mur->Id,
                                'Id' => $_REQUEST['Emplacement']
                            ));
                            if ($result->rowCount() > 0) {
                                $Emplacement = $result->fetchObject();
                                $result = $dbh->prepare("SELECT `Id` FROM `Couleurs` WHERE `Id` = :Id");
                                $result->execute(array(
                                    'Id' => $_REQUEST['Couleur']
                                ));
                                if ($result->rowCount() > 0) {
                                    $Couleur = $result->fetchObject();
                                    if ($_FILES['Photo']['tmp_name'] != '') {
                                        $extansion = array_reverse(explode('.',$_FILES['Photo']['name']))[0];
                                        do {
                                            $Photo = bin2hex(random_bytes(16)).'.'.$extansion;
                                        } while (file_exists('../Medias/'.$Photo));
                                        if (!move_uploaded_file($_FILES['Photo']['tmp_name'], '../Medias/'.$Photo)) {
                                            unset($Photo);
                                            echo '<p>Une erreur est survenue lors du chargement de la photo.</p>';
                                        }
                                    }
                                    if ($_FILES['Vidéo']['tmp_name'] != '') {
                                        $extansion = array_reverse(explode('.',$_FILES['Vidéo']['name']))[0];
                                        do {
                                            $Video = bin2hex(random_bytes(16)).'.'.$extansion;
                                        } while (file_exists('../Medias/'.$Video));
                                        if (!move_uploaded_file($_FILES['Vidéo']['tmp_name'], '../Medias/'.$Video)) {
                                            unset($Video);
                                            echo '<p>Une erreur est survenue lors du chargement de la video.</p>';
                                        }
                                    }
                                    if (!isset($Photo)) {
                                        $Photo = $_REQUEST['deletePhoto'] == 'true' ? null : $Voie->Photo;
                                    }
                                    if (!isset($Video)) {
                                        $Video = $_REQUEST['deleteVideo'] == 'true' ? null : $Voie->Vidéo;
                                    }
                                    $query = $dbh->prepare("UPDATE `Voies` SET `Emplacement`=:Emplacement,`Couleur`=:Couleur,`Cotation`=:Cotation,`Description`=:Description, `Photo` = :Photo, `Vidéo` = :Video WHERE `Id`=:Id");
                                    if ($query->execute(array(
                                        'Id' => $Voie->Id,
                                        'Emplacement' => $Emplacement->Id,
                                        'Couleur' => $Couleur->Id,
                                        'Cotation' => $_REQUEST['Cotation'],
                                        'Description' => $_REQUEST['Description'] != '' ? $_REQUEST['Description'] : null,
                                        'Photo' => $Photo,
                                        'Video' => $Video
                                    ))) {
                                        echo '<p>Modification terminée de la '.afficheVoieFromId($Voie->Id,false).'.</p>';
                                        if ($Photo != $Voie->Photo and $Voie->Photo != null) {
                                            unlink('../Medias/'.$Voie->Photo);
                                        }
                                        if ($Video != $Voie->Vidéo and $Voie->Vidéo != null) {
                                            unlink('../Medias/'.$Voie->Vidéo);
                                        }
                                    } else {
                                        echo '<p>Une erreur est survenue lors de la modification de cette voie.</p>';
                                    }
                                } else {
                                    echo '<p>Cette couleur n’existe pas.</p>';
                                }
                            } else {
                                echo '<p>Cet emplacement n’existe pas ou ne fait pas parti de votre mur.</p>';
                            }
                        } else {
                            echo '<p>Cet voie n’existe pas ou ne fait pas partie de votre mur.</p>';
                        }
                    } else {
                        echo '<p>Les données saisies ne sont pas complètes.</p>';
                    }
                } else {
                    echo '<p>Vous n’avez pas les droits suffisants pour modifier une voie sur ce mur.</p>';
                }
                break;
            case 'retirer':
                if (droits('V',$Mur->Id)) {
                    if ($_REQUEST['Id'] != '') {
                        $result = $dbh->prepare("SELECT `Voies`.`Id`, `Voies`.`Photo`, `Voies`.`Vidéo`, (SELECT COUNT(*) FROM `Essais` WHERE `Essais`.`Voie` = `Voies`.`Id`) AS `Nb_Essais` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Voies`.`Id` = :Id AND `Emplacements`.`Mur` = :Mur");
                        $result->execute(['Id' => $_REQUEST['Id'], 'Mur' => $Mur->Id]);
                        if ($result->rowCount() > 0) {
                            $Voie = $result->fetchObject();
                            if ($Voie->Nb_Essais > 0) {
                                $query = $dbh->prepare("UPDATE `Voies` SET `Active` = 0 WHERE `Voies`.`Id` = :Id");
                                if ($query->execute(array('Id' => $Voie->Id))) {
                                    echo '<p>La '.afficheVoieFromId($Voie->Id, false).' a bien été désactivée.</p>';
                                    if ($Voie->Photo != null) {
                                        unlink('../Medias/'.$Voie->Photo);
                                    }
                                    if ($Voie->Vidéo != null) {
                                        unlink('../Medias/'.$Voie->Vidéo);
                                    }
                                } else {
                                    echo '<p>Une erreur est survenue.</p>';
                                }
                            } else {
                                $Message = '<p>La '.afficheVoieFromId($Voie->Id, false).' a bien été supprimée.</p>';
                                if ($Voie->Photo != null) {
                                    unlink('../Medias/'.$Voie->Photo);
                                }
                                if ($Voie->Vidéo != null) {
                                    unlink('../Medias/'.$Voie->Vidéo);
                                }
                                $query = $dbh->prepare("DELETE FROM `Voies` WHERE `Voies`.`Id` = :Id");
                                if ($query->execute(array('Id' => $Voie->Id))) {
                                    echo $Message;
                                    unset($Message);
                                    $dbh->query("ALTER TABLE `Voies` auto_increment = 1");
                                } else {
                                    echo '<p>Une erreur est survenue.</p>';
                                }
                            }
                        } else {
                            echo '<p>Cette voie n’existe pas ou ne fait pas partie de ce mur.</p>';
                        }
                    } else {
                        echo '<p>Vous devez spécifier une voie.</p>';
                    }
                } else {
                    echo '<p>Vous n’avez pas les droits suffisants pour supprimer une voie sur ce mur.</p>';
                }
                break;
        }


        
        if (droits('V',$Mur->Id)) {
            echo '<form method="post" id="créer">';
            echo '<input type="hidden" name="Action" value="créer">';
            echo '</form>';
        }
        if ($_REQUEST['Vue'] == 'Ancienne') {
            if (droits('V',$Mur->Id)) {
                echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter une voie"> | <a href="Voies/Gestion" title="Afficher les voies actives">Voies actives</a></p>';
            } else {
                echo '<p class="noPrint"><a href="Voies/Gestion" title="Afficher les voies actives">Voies actives</a></p>';
            }
            $ancienne = ' AND Voies.Active = 0';
        } else {
            echo '<form method="post" id="AnciennesVoies">';
            echo '<input type="hidden" name="Vue" value="Ancienne">';
            echo '</form>';
            if (droits('V',$Mur->Id)) {
                echo '<p class="noPrint"><input type="submit" form="créer" class="link" value="Ajouter une voie"> | <input type="submit" form="AnciennesVoies" class="link" value="Anciennes voies" title="Afficher les voies qui ont été retirées"></p>';
            } else {
                echo '<p class="noPrint"><input type="submit" form="AnciennesVoies" class="link" value="Anciennes voies" title="Afficher les voies qui ont été retirées"></p>';
            }
            $ancienne = ' AND Voies.Active = 1';
        }

        //compte des voies
        $result = $dbh->prepare("SELECT COUNT(Voies.Id) AS `nb` FROM Voies JOIN Emplacements ON Voies.Emplacement=Emplacements.Id WHERE Emplacements.Mur= :Mur" . $ancienne );
        $result->execute(array('Mur' => $Mur->Id));
        $NombreDeVoie = $result->fetchObject()->nb;
        echo '<p>Nombre de voies&nbsp;: '.$NombreDeVoie.'</p>';

        
        echo ' <div class="table"><table><thead><tr><th colspan="2">Voies</th><th>Nombre d’essais</th><th>Réussite en tête</th><th>Réussites totale</th><th>Chutes cumulées</th><th>Patron de la voie</th>';
        if (droits('P',$Mur->Id)) {
            echo '<th>Grimpeurs bloqués</th>';
        }
        if (droits('V',$Mur->Id)) {
            echo '<th class="noPrint"></th>';
        }
        echo '</tr></thead><tbody>';
        
        //compte des voies par emplacement pour le rowspan
        $result = $dbh->prepare("SELECT COUNT(Voies.Id) AS `nb`, Emplacements.Nom AS `Emplacement` FROM Voies JOIN Emplacements ON Voies.Emplacement=Emplacements.Id WHERE Emplacements.Mur= :Mur" . $ancienne . " GROUP BY Emplacements.Id ORDER BY Emplacements.Ordre");
        $result->execute(array('Mur' => $Mur->Id));
        $result1 = $dbh->prepare("SELECT Voies.Id, Voies.Cotation, Emplacements.Nom AS `Emplacement`, Couleurs.Nom AS 'Couleur', Couleurs.Code_1, Couleurs.Code_2  FROM Voies JOIN Emplacements ON Voies.Emplacement=Emplacements.Id JOIN Couleurs ON Voies.Couleur=Couleurs.Id WHERE Emplacements.Mur= :Mur" . $ancienne . " ORDER BY Emplacements.Ordre");
        $result1->execute(array('Mur' => $Mur->Id));
        while ($Emplacement = $result->fetchObject()) {
            echo '<tr><td  rowspan=' . $Emplacement->nb . '>' . $Emplacement->Emplacement . '</td>';
            for ($i = 1; $i <= $Emplacement->nb; $i++) {
                $Voie = $result1->fetchObject();
                $styleVoie = '<td style="';
                if ($Voie->Code_2 != null) {
                    $styleVoie .= 'background-image: linear-gradient(to bottom right, #' . $Voie->Code_1 . ' 25%, #' . $Voie->Code_2 . ' 75%);';
                } else {
                    $styleVoie .= 'background-color: #' . $Voie->Code_1 . ';';
                }
                $styleVoie .= ' color: ' . couleur_text('#' . $Voie->Code_1) . '">';
                echo $styleVoie;
                echo $Voie->Cotation . '</td>';
                //Stats sur la voie
                echo '<td>' . nbEssaisEnMode('*', $Voie->Id) . '</td>';
                echo '<td>' . nbRéussitesEnMode('1', $Voie->Id) . '</td>';
                echo '<td>' . nbRéussitesEnMode('*', $Voie->Id) . '</td>';
                echo '<td>' . nbChutes($Voie->Id) . '</td>';
                $Patron = getUserFromId(patronDeLaVoie($Voie->Id));
                if ($Patron) {
                    echo '<td>' . $Patron->Prénom . ' '.$Patron->Nom.'</td>';
                } else {
                    echo '<td></td>';
                }
                if (droits('P',$Mur->Id)) {
                    echo '<td>';
                    //echo '<td style="padding:0"><div style="max-height: 4rem; overflow-x: auto; padding:0.5rem">';
                    $Essayeurs = idGrimpeursBloqués($Voie->Id);
                    $Utilisateurs = [];
                    while ($User = $Essayeurs->fetchObject()) {
                        $user = getUserFromId($User->Id);
                        $Utilisateurs[] = $user->Prénom . ' ' . $user->Nom.' ('.niveauMaxRéel($User->Id)->Cotation.')';
                    }
                    echo implode('<br>',$Utilisateurs);
                    //echo '</div>';
                    echo '</td>';
                }
                if (droits('V',$Mur->Id)) {
                    echo '<td class="noPrint">';
                    echo '<form method="post" class="icon">';
                    echo '<input type="hidden" name="Action" value="modifier">';
                    echo '<input type="hidden" name="Id" value=' . $Voie->Id . '>';
                    echo '<input type="submit" title="Modifier cette voie" value="✏️">';
                    echo '</form>';
                    echo '<form method="post" class="icon">';
                    echo '<input type="hidden" name="Action" value="supprimer">';
                    echo '<input type="hidden" name="Id" value=' . $Voie->Id . '>';
                    echo '<input type="submit" title="Supprimer cette voie" value="🗑️️">';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';
        break;
}

require('Pied de page.inc.php');
require('Bas.inc.php');
?>