<?php

require('../Inclus/Haut.inc.php');
session_write_close();
$Page_Ouv = 'Voies récentes';
require('Entête.inc.php');

echo '<form method="POST">';
echo '<h2>Voies récentes -';
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

echo '<div class="table"><table><thead><tr><th>Cotation</th><th>Voie</th><th>Date de création</th>';
echo '</tr></thead><tbody>';

$stat = $dbh->prepare("SELECT `Voies`.`DateCréation`, `Voies`.`Cotation`, `Couleurs`.`Code_1` AS `Couleur_1`, `Couleurs`.`Code_2` AS `Couleur_2`, `Emplacements`.`Nom` AS `Emplacement` FROM `Voies` LEFT OUTER JOIN `Emplacements` ON `Emplacements`.`Id` = `Voies`.`Emplacement` LEFT OUTER JOIN `Couleurs` ON `Couleurs`.`Id` = `Voies`.`Couleur` WHERE `Emplacements`.`Mur`= :Mur ORDER BY `Voies`.`DateCréation` DESC, RAND() LIMIT 20");
$stat->execute(array('Mur' => $Mur->Id));
while($Voie = $stat->fetchObject()){
    echo '<tr>';
    echo '<td>'.$Voie->Cotation.'</td>';
    echo '<td style="';
    if ($Voie->Couleur_2 != null) {
        echo 'background-image: linear-gradient(to bottom right, #' . $Voie->Couleur_1 . ' 25%, #' . $Voie->Couleur_2 . ' 75%);';
    } else {
        echo 'background-color: #' . $Voie->Couleur_1 . ';';
    }
    echo ' color: ' . couleur_text('#' . $Voie->Couleur_1) . '">'.$Voie->Cotation.' sur l’emplacement '.$Voie->Emplacement.'</td>';
    echo '<td>'.date_create($Voie->DateCréation)->format('d/m/Y').'</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

require('Pied de page.inc.php');
require('Bas.inc.php');
?>