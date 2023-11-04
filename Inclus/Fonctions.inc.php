<?php

function insertionUtilisateur($nom,$prenom,$identifiant,$mdp,$groupe){
    global $dbh;
    $result = $dbh->prepare("    INSERT INTO `Utilisateurs` (`Id`, `Nom`, `Prénom`, `Identifiant`, `Mot_de_passe`, `Groupe`) VALUES (NULL, :nom, :prenom, :identifiant, :mdp, :groupe )
    ");
        $result->execute(array(
            'nom' => $nom,
            'prenom' => $prenom,
            'identifiant' => $identifiant,
            'groupe' => $groupe,
            'mdp' => password_hash($mdp, PASSWORD_DEFAULT),
        ));
}

function substr_char($str,$offset,$charsets = [' ',',',';']) {
    //retourne la chaine str, coupée au dernier charset trouvé;
    $max = 0;
    foreach ($charsets as $char) {
        $p = strrpos($str,' ',$offset-strlen($str));
        if ($p > $max) {
            $max = $p;
        }
    }
    return substr($str,0,$max);
}

function TrimSpace($str) {
	if (is_array($str)) {
		foreach ($str as $key => $value) {
			$str[$key] = TrimSpace($value);
		}
		return $str;
	} else {
		return trim(preg_replace(["#\s+#","#(\x{200e}|\x{200f}|\x{00a0})#u"]," ",$str));
	}
}

function traiter_inputs($var) {
	if (is_array($var)) {
		foreach ($var as $Clé => $Valeur) {
		    $var[$Clé] = traiter_inputs($Valeur);
		}
		return $var;
	} elseif ($var === null) {
        return null;
    } else {
		return trim(str_replace('&amp;nbsp;','&nbsp;',htmlspecialchars(preg_replace(["#(\S)'(\S)#","#'(\S)#","#(\S)'#",'#"([^"]*)"#'], ["$1’$2","‘$1","$1’","«&nbsp;$1&nbsp;»"], $var),ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
	}
}

function retier_accents($str) {
    if (is_array($str)) {
		foreach ($str as $Clé => $Valeur) {
		    $str[$Clé] = retier_accents($Valeur);
		}
		return $str;
	} elseif ($str === null) {
        return null;
    } else {
        $str = htmlentities($str, ENT_NOQUOTES);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        //$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = html_entity_decode($str, ENT_NOQUOTES);
        return $str;
	}
}

function Modes()
{
    global $dbh, $Modes;
    if (isset($Modes)) {
        return $Modes;
    } else {
        $Modes = [];
        $query = "SELECT DISTINCT * FROM `Modes`  ORDER BY `Id`";
        $result = $dbh->query($query);
        while ($Mode = $result->fetchObject()) {
            $Modes[$Mode->Id] = $Mode->Nom;
            $Modes[$Mode->Nom] = $Mode->Id;
        }
        return $Modes;
    }
}

function droits($DDroit, $Mur, $Utilisateur = null) {
    global $dbh, $Utilisateur_Con;
    if ($Utilisateur == null) {
        $Utilisateur = $Utilisateur_Con->Id;
    }
    if ($Utilisateur != null) {
        $query = "SELECT * FROM `Mur_Utilisateurs` WHERE `Mur` = :Mur and `Utilisateur` = :Utilisateur";
        $result = $dbh->prepare($query);
        $result->execute([
            'Mur' => $Mur,
            'Utilisateur' => $Utilisateur
        ]);
        if ($result->rowCount() > 0) {
            $Droit = $result->fetchObject();
            switch ($DDroit) {
                case 'G':
                    return true;
                    break;
                case 'V':
                    if ($Droit->Droits == 'V' or $Droit->Droits == 'P' or $Droit->Droits == 'A') {
                        return true;
                    }
                    break;
                case 'P':
                    if ($Droit->Droits == 'P' or $Droit->Droits == 'A') {
                        return true;
                    }
                    break;
                case 'A':
                    if ($Droit->Droits == 'A') {
                        return true;
                    }
                    break;
            }
        }
    }
    return false;
}

function niveauMax($uId)
{
    global $dbh;
    $result = $dbh->prepare("SELECT Voies.Cotation, Essais.Date, Voies.Id FROM Essais JOIN Voies ON Voies.Id=Essais.Voie WHERE Essais.Utilisateur= :Utilisateur AND `Essais`.`Réussite` is NULL ORDER BY Voies.Cotation DESC LIMIT 1");
    $result->execute(array(
        'Utilisateur' => $uId,
    ));
    if ($result->rowCount() > 0) {
        return $result->fetchObject();
    } else {
        return false;
    }
}

function niveauMaxRéel($uId)
{
    global $dbh;
    $result = $dbh->prepare("SELECT Voies.Cotation, Essais.Date, Voies.Id FROM Essais JOIN Voies ON Voies.Id=Essais.Voie WHERE Essais.Utilisateur= :Utilisateur AND `Essais`.`Réussite` is NULL and Essais.Nb_Chutes=0 and Essais.Nb_Pauses=0 ORDER BY Voies.Cotation DESC LIMIT 1");
    $result->execute(array(
        'Utilisateur' => $uId,
    ));
    if ($result->rowCount() > 0) {
        return $result->fetchObject();
    } else {
        return false;
    }
}

function nbEssaisEnMode($mode, $vId)
{
    global $dbh;
    if ($mode == '*') {
        $endOfQuery = '';
    } else {
        $endOfQuery = "AND Essais.Mode= " . $mode;
    }
    $stat = $dbh->prepare("SELECT COUNT(Essais.Id) AS 'nb' FROM `Essais` WHERE Essais.Voie= :id  " . $endOfQuery);
    $stat->execute(array('id' => $vId));
    while ($Nombre = $stat->fetchObject()) {
        return $Nombre->nb;
    }
}


function patronDeLaVoie($vId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT Essais.Utilisateur FROM `Essais` WHERE `Voie` = :id AND `Réussite` IS NULL AND `Nb_Pauses` = 0 AND `Nb_Chutes` = 0 GROUP BY Essais.Utilisateur ORDER BY COUNT(*) DESC, MIN(`Date`) LIMIT 1");
    $stat->execute(array('id' => $vId));
    $res = false;
    while ($Patron = $stat->fetchObject()) {
        $res = $Patron->Utilisateur;
    }
    return $res;
}
function voieMaxChutes($mId) {
    global $dbh;
    $stat = $dbh->prepare("SELECT `Voies`.`Id`, SUM(`Essais`.`Nb_Chutes`) AS `Somme` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Emplacements`.`Mur` = :Mur GROUP BY Voies.Id ORDER BY `Somme` DESC LIMIT 1");
    $stat->execute(array('Mur' => $mId));
    if ($stat->rowCount() > 0) {
        return $stat->fetchObject();
    } else {
        return $false;
    }
}

//retourne un tableau d'objet sur 
function idGrimpeursDeLaVoie($vId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT Utilisateur, COUNT(Id) AS 'nbEssais' FROM `Essais` WHERE Essais.Voie= :id GROUP BY Essais.Utilisateur ORDER BY 'nbEssais' DESC ");
    $res = [];
    $stat->execute(array('id' => $vId));
    return $stat;
}
function idGrimpeursBloqués($vId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT `Essais`.`Utilisateur` AS `Id`, MAX(`Réussite`) AS `Max` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` WHERE `Voies`.`Id` = :Id GROUP BY `Essais`.`Utilisateur` HAVING MIN(IFNULL(`Réussite`,-1)) > -1 ORDER BY `Max` DESC");
    $stat->execute(array('Id' => $vId));
    return $stat;
}
function essaisDeGrimpeurSurVoie($vId, $uId)
{
    //retourne tous les essais du grimpeur $uId sur la voie $vId par date décroissante
    global $dbh;
    $stat = $dbh->prepare("SELECT `Essais`.`Id`, `Essais`.`Date`, `Essais`.`Mode`, `Essais`.`Nb_Pauses`, `Essais`.`Nb_Chutes`, `Essais`.`Réussite`, `Emplacements`.`Nb_Dégaines` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Voie`= :vId AND `Utilisateur`= :uId ORDER BY `Date` DESC ");
    $stat->execute(array(
        'vId' => $vId,
        'uId' => $uId
    ));
    return $stat;
}

function idVoiesQuiBloquent($uId, $mId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT `Voies`.`Id`, MAX(`Réussite`) AS `Max` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Essais`.`Utilisateur` = :Utilisateur AND `Emplacements`.`Mur` = :Mur GROUP BY `Voies`.`Id` HAVING MIN(IFNULL(`Réussite`,-1)) > -1 ORDER BY `Max` DESC");
    $stat->execute(['Utilisateur' => $uId, 'Mur' => $mId]);
    return $stat;
}

function nbChutes($vId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT SUM(Essais.Nb_Chutes) AS `somme` FROM Essais Where Essais.Voie= :voie  ORDER BY `somme` DESC LIMIT 1");
    $stat->execute(array('voie' => $vId));
    while ($Patron = $stat->fetchObject()) {
        return $Patron->somme;
    }

}

function voieDeLaSemaine($mId) {
    global $dbh;
    $stat = $dbh->prepare("SELECT `Voies`.`Id`, COUNT(*) AS `Nombre` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Emplacements`.`Mur` = :Mur GROUP BY Voies.Id ORDER BY `Nombre` DESC LIMIT 1");
    $stat->execute(array('Mur' => $mId));
    while ($Obj = $stat->fetchObject()) {
        return $Obj;
    }
    return false;
}

function getUserFromId($uId) {
    global $dbh;
    $stat = $dbh->prepare("SELECT * FROM `Utilisateurs` WHERE `Id` = :Id");
    $stat->execute(array('Id' => $uId));
    while ($Obj = $stat->fetchObject()) {
        return $Obj;
    }
    return false;
}

function dateSéances($uId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT DISTINCT date(Essais.Date) AS `Jour`from Essais WHERE Essais.Utilisateur= :id ORDER BY  `Jour` DESC");
    $stat->execute(array('id' => $uId));
    $seances;
    while ($Patron = $stat->fetchObject()) {
        $seances[] = $Patron->Jour;
    }
    return $seances;

}
function getVoieFromId($vId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT Voies.Id, Voies.Cotation, Emplacements.Nom AS `Emplacement`, Couleurs.Nom AS 'Couleur', SUM(Essais.Nb_Chutes) AS `somme` FROM Essais JOIN Voies ON Voies.Id=Essais.Voie  JOIN Emplacements ON Voies.Emplacement=Emplacements.Id JOIN Couleurs ON Voies.Couleur=Couleurs.Id WHERE Voies.Id= :id ");
    $stat->execute(array('id' => $vId));
    if ($stat->rowCount() > 0) {
        return $stat->fetchObject();
    } else {
        return $false;
    }
}
function afficheVoieFromId($vId, $Capital = true) {
    global $dbh;
    $stat = $dbh->prepare("SELECT Voies.Id, Voies.Cotation, Emplacements.Nom AS `Emplacement`, Couleurs.Nom AS 'Couleur'FROM  Voies  JOIN Emplacements ON Voies.Emplacement=Emplacements.Id JOIN Couleurs ON Voies.Couleur=Couleurs.Id WHERE Voies.Id= :id ");
    $stat->execute(array('id' => $vId));
    while ($details = $stat->fetchObject()) {
        if ($details->Cotation != null and $details->Cotation > 0) {
            if ($Capital) {
                return 'Voie ' . $details->Cotation . ' ' . $details->Couleur . ' de l’emplacement ' . $details->Emplacement;
            } else {
                return 'voie ' . $details->Cotation . ' ' . $details->Couleur . ' de l’emplacement ' . $details->Emplacement;
            }
        } else {
            if ($Capital) {
                return 'Voie ' . $details->Couleur . ' de l’emplacement ' . $details->Emplacement;
            } else {
                return 'voie ' . $details->Couleur . ' de l’emplacement ' . $details->Emplacement;
            }
        }
    }

}

function voiePréférée($uId, $mId)
{
    global $dbh;
    $stat = $dbh->prepare("SELECT `Voies`.`Id`, Count(Essais.Id) AS `nb` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` WHERE `Essais`.`Utilisateur` = :Utilisateur AND `Emplacements`.`Mur` = :Mur GROUP BY `Voies`.`Id` ORDER BY `nb` DESC, `Voies`.`Cotation` DESC LIMIT 1");
    $stat->execute(['Utilisateur' => $uId, 'Mur' => $mId]);
    if ($stat->rowCount() > 0) {
        return $stat->fetchObject();
    } else {
        return $false;
    }
}

function nbRéussitesEnMode($mode, $vId)
{
    global $dbh;
    if ($mode == '*') {
        $endOfQuery = '';
    } else {
        $endOfQuery = "AND Essais.Mode= " . $mode;
    }
    $stat = $dbh->prepare("SELECT COUNT(Essais.Id) AS 'nb' FROM `Essais` WHERE Essais.Voie= :id AND Essais.Réussite is NULL " . $endOfQuery);
    $stat->execute(array('id' => $vId));
    while ($Nombre = $stat->fetchObject()) {
        return $Nombre->nb;
    }
}

function couleur_text($Couleur)
{
    $r = hexdec(substr($Couleur, 1, 2)) / 255;
    $g = hexdec(substr($Couleur, 3, 2)) / 255;
    $b = hexdec(substr($Couleur, 5, 2)) / 255;

    $l = (max($r, $g, $b) + min($r, $g, $b)) / 2;
    
    if ($l < 0.5) {
        return '#FFFFFF';
    } else {
        if ($b == 1&&$r==0) {
        return '#FFFFFF';
    }
        return '#000000';
    }

}

function initialiser_Tournoi() {
	global $dbh, $Utilisateur_Con;
	if ($Utilisateur_Con->Id != null and !isset($_SESSION['Tournoi'])) {
		$query = "SELECT `Tournois`.`Id` FROM `Tournois_Utilisateurs` LEFT OUTER JOIN `Tournois` ON `Tournois`.`Id` = `Tournois_Utilisateurs`.`Tournoi` WHERE `Tournois_Utilisateurs`.`Utilisateur` = ".$Utilisateur_Con->Id." ORDER BY `Tournois`.`Date` DESC LIMIT 1";
		$_SESSION['Tournoi'] = $dbh->query($query)->fetchObject()->Id;
	}
	
	if ($Utilisateur_Con->Id == null and !isset($_SESSION['Tournoi'])) {
		$query = "SELECT `Id` FROM `Tournois` ORDER BY `Date` DESC LIMIT 1";
		$_SESSION['Tournoi'] = $dbh->query($query)->fetchObject()->Id;
	}

	// Changement tournoi selectioné
	if (isset($_REQUEST['Tournoi']) and $_REQUEST['Tournoi'] != $_SESSION['Tournoi']) {
		if ($_REQUEST['Tournoi'] != '') {
			$query =  "SELECT `Id` FROM `Tournois` WHERE `Id` = ?";
			$result = $dbh->prepare($query);
			$result->execute(array($_REQUEST['Tournoi']));
			if ($result->rowCount() > 0) {
				$_SESSION['Tournoi'] = $result->fetchObject()->Id;
			}
		} else {
			unset($_SESSION['Tournoi']);
		}
	}
	
	// Récupération des informations sur la tournoi
	if (isset($_SESSION['Tournoi']) and $_SESSION['Tournoi'] != 0) {
		$query =  "SELECT COUNT(*) FROM `Tournois` WHERE `Id` = ".$_SESSION['Tournoi'];
		if ($dbh->query($query)->fetchColumn() > 0) {
			$query =  "SELECT * FROM `Tournois` WHERE `Id` = ".$_SESSION['Tournoi'];
			$result = $dbh->query($query);
			$Tournoi = $result->fetchObject();
		} else {
			// Cet tournoi n'existe pas donc suppresssion de la variable session
			unset($_SESSION['Tournoi']);
		}
	}
	if (!isset($Tournoi)) {
		$Tournoi = new stdClass();
		$Tournoi->Id = null;
	}
	
	return $Tournoi;
}

function initialiser_Mur() {
	global $dbh, $Utilisateur_Con;
	//Mur par défaut pour un utilisateur connecté dont le mur n'a pas été forcée
	if ($Utilisateur_Con->Id != null and !isset($_SESSION['Mur'])) {
		$query = "SELECT `Mur` FROM `Mur_Utilisateurs` WHERE `Utilisateur` = ".$Utilisateur_Con->Id." ORDER BY `Ordre` LIMIT 1";
		$result = $dbh->query($query);
		if ($result->rowCount() > 0) {
			$_SESSION['Mur'] = $dbh->query($query)->fetchObject()->Mur;
		}
	}

	// Changement mur selectioné
	if (isset($_REQUEST['Mur']) and $_REQUEST['Mur'] != $_SESSION['Mur']) {
		if ($_REQUEST['Mur'] != '') {
			$query =  "SELECT `Id` FROM `Murs` WHERE `Id` = ?";
			$result = $dbh->prepare($query);
			$result->execute(array($_REQUEST['Mur']));
			if ($result->rowCount() > 0) {
				$_SESSION['Mur'] = $result->fetchObject()->Id;
			}
		} else {
			unset($_SESSION['Mur']);
		}
	}

	// Récupération des informations sur le mur
	if (isset($_SESSION['Mur']) and $_SESSION['Mur'] != 0) {
		$query =  "SELECT COUNT(*) FROM `Murs` WHERE `Id` = ".$_SESSION['Mur'];
		if ($dbh->query($query)->fetchColumn() > 0) {
			$query =  "SELECT * FROM `Murs` WHERE `Id` = ".$_SESSION['Mur'];
			$result = $dbh->query($query);
			$Mur = $result->fetchObject();
		} else {
			// Cet mur n'existe pas donc suppresssion de la variable session
			unset($_SESSION['Mur']);
		}
	}
	if (!isset($Mur)) {
		$Mur = new stdClass();
		$Mur->Id = null;
	}
	
	return $Mur;
}

?>
