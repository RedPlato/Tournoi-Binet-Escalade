<?php
require('Inclus/Haut.inc.php');
if ($_REQUEST['ticket'] != '') {
	$ch = curl_init();
	$doc = new DOMDocument();
	curl_setopt($ch, CURLOPT_URL, 'https://metacas.binets.fr/serviceValidate?service='.rawurlencode('https://escalade.binets.fr/CAS').'&ticket='.$_REQUEST['ticket']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$doc->loadXML(curl_exec($ch));
	curl_close($ch);
	if ($doc->getElementsByTagName('authenticationFailure')->length == 0) {
		$User = $doc->getElementsByTagName('user')->item(0)->textContent;
		$query = "SELECT * FROM `Utilisateurs` WHERE `CAS_Id` = :id";
		$result = $dbh->prepare($query);
		$result->execute(['id' => $User]);
		if ($result->rowCount() > 0) {
			$Utilisateur = $result->fetchObject();
			$_SESSION['Utilisateur_Con'] = $Utilisateur->Id;
			header('Location: https://escalade.binets.fr');
		} else {
			$Promo = $doc->getElementsByTagName('promo');
			if ($Promo->length > 0) {
				$Groupe = 'X'.$Promo->item(0)->textContent;
			} else {
				$Groupe = $doc->getElementsByTagName('departement')->item(0)->textContent;
			}
			$_SESSION['CAS'] = [
				'User' => $User,
				'Nom' => $doc->getElementsByTagName('nom')->item(0)->textContent,
				'Prénom' => $doc->getElementsByTagName('prenom')->item(0)->textContent,
				'Adresse' => $doc->getElementsByTagName('mail')->item(0)->textContent,
				'Groupe' => $Groupe
			];
			require('Inclus/Entête.inc.php');
			echo '<h2>Création d’un compte</h2>';
			echo '<p>Nous vous demandons de vous créer un compte pour faciliter votre accès au site.</p>';
			echo '<form method="post" action="CAS">';
			echo '<input type="hidden" name="Action" value="ajouter">';
			echo '<h4>Nom</h4>';
			echo '<p>'.$_SESSION['CAS']['Prénom'].' '.$_SESSION['CAS']['Nom'].'</p>';
			echo '<h4>Adresse électronique</h4>';
			echo '<p>'.$_SESSION['CAS']['Adresse'].'</p>';
			echo '<h4>Groupe</h4>';
			echo '<p>'.$_SESSION['CAS']['Groupe'].'</p>';
			echo '<h4>Identifiant</h4>';
			echo '<p><input type="text" name="Identifiant" autocomplete="username" autocapitalize="none" autocorrect="off" required></p>';
			echo '<h4>Nouveau mot de passe</h4>';
			echo '<p><input type="password" name="Mot_de_passe1" autocomplete="new-password" required></p>';
			echo '<h4>Nouveau mot de passe</h4>';
			echo '<p><input type="password" name="Mot_de_passe2" placeholder="Répetez votre nouveau mot de passe" autocomplete="new-password" required></p>';
			echo '<input type="submit" value="Créer un compte">';
			echo '</form>';
			require('Inclus/Pied de page.inc.php');
		}
	} else {
		header('Location: https://escalade.binets.fr?message=Une erreur est survenue lors du dialogue avec le serveur CAS.');
	}
} elseif ($_REQUEST['Action'] == 'ajouter') {
	require('Inclus/Entête.inc.php');
	echo '<h2>Création d’un compte</h2>';
	if ($_REQUEST['Identifiant'] != '' and $_REQUEST['Mot_de_passe1'] != '' and $_REQUEST['Mot_de_passe2'] != '' and $_SESSION['CAS']['Prénom'] != '' and $_SESSION['CAS']['Nom'] != '' and $_SESSION['CAS']['Adresse'] != '') {
		if ($_REQUEST['Mot_de_passe1'] == $_REQUEST['Mot_de_passe2']) {
			$Mot_de_passe = password_hash($_REQUEST['Mot_de_passe1'], PASSWORD_DEFAULT);
			$query = "SELECT * FROM `Utilisateurs` WHERE `Adresse_électronique` = :Adresse OR `Identifiant` = :Identifiant";
			$result = $dbh->prepare($query);
			$result->execute(['Adresse' => $_SESSION['CAS']['Adresse'], 'Identifiant' => $_REQUEST['Identifiant']]);
			if ($result->rowCount() == 0) {
				$query = "INSERT INTO `Utilisateurs`(`Nom`, `Prénom`, `Adresse_électronique`, `Identifiant`, `Mot_de_passe`, `CAS_Id`) VALUES (:Nom, :Prenom, :Adresse, :Identifiant, :MotDePasse, :CASId)";
				$result = $dbh->prepare($query);
				if ($result->execute(['Nom' => $_SESSION['CAS']['Nom'], 'Prenom' => $_SESSION['CAS']['Prénom'], 'Adresse' => $_SESSION['CAS']['Adresse'], 'Identifiant' => $_REQUEST['Identifiant'], 'MotDePasse' => $Mot_de_passe, 'CASId' => $_SESSION['CAS']['User']])) {
					$Id = $dbh->lastInsertId();
					$query = "INSERT INTO `Mur_Utilisateurs`(`Mur`, `Utilisateur`, `Groupe`, `Droits`, `Ordre`) VALUES (:Mur, :Utilisateur, :Groupe, :Droits, 1)";
					$result = $dbh->prepare($query);
					$Utilisateur = getUserFromId($Id);
					if ($result->execute(['Mur' => 1, 'Utilisateur' => $Id, 'Groupe' => $_SESSION['CAS']['Groupe'] != '' ? $_SESSION['CAS']['Groupe'] : null, 'Droits' => 'G'])) {
						$_SESSION['Utilisateur_Con'] = $Id;
						header('Location: https://escalade.binets.fr');
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
			echo '<p>Les mots de passe saisis ne sont pas identiques.</p>';
		}
	} else {
		echo '<p>Les données saisies sont incomplètes.</p>';
	}
	echo '<p>Nous vous demandons de vous créer un compte pour faciliter votre accès au site.</p>';
	echo '<form method="post" action="CAS">';
	echo '<input type="hidden" name="Action" value="ajouter">';
	echo '<h4>Nom</h4>';
	echo '<p>'.$_SESSION['CAS']['Prénom'].' '.$_SESSION['CAS']['Nom'].'</p>';
	echo '<h4>Adresse électronique</h4>';
	echo '<p>'.$_SESSION['CAS']['Adresse'].'</p>';
	if ($_SESSION['CAS']['Promo'] != '') {
		echo '<h4>Promo</h4>';
		echo '<p>X'.$_SESSION['CAS']['Promo'].'</p>';
	}
	echo '<h4>Identifiant</h4>';
	echo '<p><input type="text" name="Identifiant" autocomplete="username" autocapitalize="none" autocorrect="off" required></p>';
	echo '<h4>Nouveau mot de passe</h4>';
	echo '<p><input type="password" name="Mot_de_passe1" autocomplete="new-password" required></p>';
	echo '<h4>Nouveau mot de passe</h4>';
	echo '<p><input type="password" name="Mot_de_passe2" placeholder="Répetez votre nouveau mot de passe" autocomplete="new-password" required></p>';
	echo '<input type="submit" value="Créer un compte">';
	echo '</form>';
	require('Inclus/Pied de page.inc.php');
} else {
	header('Location: https://metacas.binets.fr/login?service='.rawurlencode('https://escalade.binets.fr/CAS').'&renew=true');
}
require('Inclus/Bas.inc.php');
?>