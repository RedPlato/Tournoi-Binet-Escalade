<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Suggestions de voies';
require('Entête.inc.php');

echo '<form method="POST">';
echo '<h2>Suggestions de voies -';
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

$vds = voieDeLaSemaine($Mur->Id);
if ($vds) {
    echo '<h4>Voie de la semaine</h4>';
    echo '<p>'.afficheVoieFromId($vds->Id) . ' avec ' .$vds->Nombre. ' essais cumulés par tous les grimpeurs cette semaine.</p>';
}

$vdm = voieMaxChutes($Mur->Id);
if ($vdm) {
    echo '<h4>Voie de la mort</h4>';
    echo '<p>'.afficheVoieFromId($vdm->Id) . ' avec ' .$vdm->Somme. ' chutes cumulées par tous les grimpeurs.</p>';
}

//voie par cotations
$result = $dbh->prepare("SELECT DISTINCT Cotation FROM Voies JOIN Emplacements ON Voies.Emplacement=Emplacements.Id WHERE Emplacements.Mur= :Mur  ORDER BY Cotation");
$result->execute(array('Mur' => $Mur->Id));
echo ' <div class="table"><table><thead><tr><th>Cotation</th><th>Voie la plus grimpée ce mois</th><th>Nombre d’essais</th></tr></thead><tbody>';
while ($Cotation = $result->fetchObject()) {
    $stat = $dbh->prepare("SELECT COUNT(*) AS `Nombre`, `Voies`.`Cotation`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2`, `Emplacements`.`Nom` AS `Emplacement` FROM `Essais` LEFT OUTER JOIN `Voies` ON `Voies`.`Id` = `Essais`.`Voie` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Emplacements`.`Mur`= :Mur AND `Essais`.`Date` > (NOW() - INTERVAL 4 WEEK) AND `Voies`.`Cotation`= :Cotation  GROUP BY Voies.Id ORDER BY `Nombre` DESC LIMIT 1");
    $stat->execute(array('Cotation' => $Cotation->Cotation, 'Mur' => $Mur->Id));
    if ($stat->rowCount() > 0) {
        $Voie = $stat->fetchObject();
        echo '<tr><td>' . $Cotation->Cotation . '</td>';
        echo '<td style="';
        if ($Voie->Couleur_2 != null) {
            echo 'background-image: linear-gradient(to bottom right, #' . $Voie->Couleur_1 . ' 25%, #' . $Voie->Couleur_2 . ' 75%);';
        } else {
            echo 'background-color: #' . $Voie->Couleur_1 . ';';
        }
        echo ' color: ' . couleur_text('#' . $Voie->Couleur_1) . '">'.$Voie->Cotation.' sur l’emplacement '.$Voie->Emplacement.'</td>';
        echo '<td>' . $Voie->Nombre . '</td>';
        echo '</tr>';
    } else {
        echo '<tr><td>'.$Cotation->Cotation.'</td>';
        echo '<td></td>';
        echo '<td></td>';
        echo '</tr>';
    }
}
echo '</tbody>';
echo '</table>';
echo '</div>';

require('Pied de page.inc.php');
require('Bas.inc.php');
?>