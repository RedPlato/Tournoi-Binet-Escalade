-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  sam. 16 nov. 2019 à 11:16
-- Version du serveur :  10.2.27-MariaDB-cll-lve
-- Version de PHP :  7.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données :  `x9p31ij2_escalade`
--

-- --------------------------------------------------------

--
-- Structure de la table `Couleurs`
--

CREATE TABLE `Couleurs` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(64) NOT NULL,
  `Code_1` char(6) NOT NULL,
  `Code_2` char(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `Couleurs`
--

INSERT INTO `Couleurs` (`Id`, `Nom`, `Code_1`, `Code_2`) VALUES
(1, 'bleue', '0000FF', NULL),
(2, 'rouge', 'FF0000', NULL),
(3, 'noire', '000000', NULL),
(4, 'verte', '00C853', NULL),
(5, 'rose', 'FF6AA4', NULL),
(6, 'orange', 'FF9A00', NULL),
(7, 'blanche', 'FFFFFF', NULL),
(8, 'bleue marbrée', '0000FF', 'FFFFFF'),
(9, 'orange marbrée', 'FF9A00', 'FFFFFF'),
(10, 'jaune', 'FFFF00', NULL),
(11, 'jaune-verte', 'CEFF00', NULL),
(12, 'beige', 'FFE4B5', NULL),
(13, 'grise', '808080', NULL),
(14, 'violette', 'B23AC4', NULL),
(15, 'marron', '582900', NULL),
(16, 'violette marbrée', 'B23AC4', 'FFFFFF');

-- --------------------------------------------------------

--
-- Structure de la table `Emplacements`
--

CREATE TABLE `Emplacements` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Mur` int(1) UNSIGNED NOT NULL,
  `Ordre` int(1) UNSIGNED DEFAULT NULL,
  `Nom` varchar(256) NOT NULL,
  `Nb_Dégaines` int(10) UNSIGNED DEFAULT NULL,
  `Inclinaison` int(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Essais`
--

CREATE TABLE `Essais` (
  `Id` int(1) NOT NULL,
  `Utilisateur` int(1) UNSIGNED NOT NULL,
  `Voie` int(1) UNSIGNED NOT NULL,
  `Date` datetime NOT NULL,
  `Mode` int(1) UNSIGNED NOT NULL,
  `Nb_Pauses` int(1) UNSIGNED NOT NULL DEFAULT 0,
  `Nb_Chutes` int(1) UNSIGNED NOT NULL DEFAULT 0,
  `Réussite` decimal(4,1) UNSIGNED DEFAULT NULL COMMENT 'null = réussite ; int = nombre de dégaines',
  `Tournoi` int(1) UNSIGNED DEFAULT NULL,
  `Evalué` tinyint(1) DEFAULT NULL,
  `Chrono` time(3) DEFAULT NULL,
  `Zones` int(1) UNSIGNED DEFAULT NULL COMMENT 'Id de la Prise ou de la Zone en tournoi',
  `Entrée_Utilisateur` int(1) UNSIGNED NOT NULL COMMENT 'Utilisateur ayant saisi l''essai (juge pour un tournoi)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Inclinaison`
--

CREATE TABLE `Inclinaison` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(32) DEFAULT NULL,
  `Ordre` int(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `Inclinaison`
--

INSERT INTO `Inclinaison` (`Id`, `Nom`, `Ordre`) VALUES
(1, 'Forte dalle', 1),
(2, 'Dalle', 2),
(3, 'Légère dalle', 3),
(4, 'Vertical', 4),
(5, 'Léger devers', 5),
(6, 'Devers', 6),
(7, 'Fort devers', 7);

-- --------------------------------------------------------

--
-- Structure de la table `Modes`
--

CREATE TABLE `Modes` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(256) NOT NULL,
  `Icône` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `Modes`
--

INSERT INTO `Modes` (`Id`, `Nom`, `Icône`) VALUES
(1, 'tête', '■'),
(2, 'moulinette', '●'),
(3, 'bloc', '◆');

-- --------------------------------------------------------

--
-- Structure de la table `Murs`
--

CREATE TABLE `Murs` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(256) NOT NULL,
  `Photo` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Mur_Utilisateurs`
--

CREATE TABLE `Mur_Utilisateurs` (
  `Mur` int(1) UNSIGNED NOT NULL,
  `Utilisateur` int(1) UNSIGNED NOT NULL,
  `Groupe` varchar(64) DEFAULT NULL,
  `Droits` char(1) NOT NULL DEFAULT 'G' COMMENT 'G : grimpeur, V : modifie les voies ; P : visualisation des performances ; A : administration des utilisateurs',
  `Ordre` int(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Tournois`
--

CREATE TABLE `Tournois` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(256) NOT NULL,
  `Date` date NOT NULL,
  `Difficulté_validation_consecutive` int(1) UNSIGNED DEFAULT NULL COMMENT 'Nombre de voie de difficulté consécutive dont la validation valide toutes les voies de difficulté inférieure',
  `Difficulté_validation_partielle` int(1) UNSIGNED DEFAULT NULL COMMENT 'Nombre de voie de difficulté inférieur à valider pour comptabiliser une voie réussite partiellement',
  `Options_Résultats` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Tournois_Utilisateurs`
--

CREATE TABLE `Tournois_Utilisateurs` (
  `Tournoi` int(1) UNSIGNED NOT NULL,
  `Utilisateur` int(1) UNSIGNED NOT NULL,
  `Type` enum('Administrateur','Juge','Grimpeur') NOT NULL,
  `Dossard` varchar(12) DEFAULT NULL,
  `Catégorie` varchar(256) DEFAULT NULL,
  `Equipe` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Tournois_Voies`
--

CREATE TABLE `Tournois_Voies` (
  `Tournoi` int(1) UNSIGNED NOT NULL,
  `Voie` int(1) UNSIGNED NOT NULL,
  `Type` enum('Difficulté','Vitesse','Bloc') NOT NULL,
  `Evaluation` enum('Prise','Dégaine','Top') NOT NULL DEFAULT 'Dégaine',
  `Nb_Essais_Libres` int(1) UNSIGNED DEFAULT NULL,
  `Nb_Essais_Evalués` int(1) UNSIGNED DEFAULT NULL,
  `Chronométrée` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `Nb_Points_Absolu` int(1) UNSIGNED DEFAULT NULL COMMENT 'Nombre de points donné à chaque grimpeur ',
  `Nb_Points_Relatif` int(1) UNSIGNED DEFAULT NULL COMMENT 'Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur',
  `Phase` varchar(32) DEFAULT NULL COMMENT 'Qualification, demi-finale, finale...'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Tournois_Voies_Zones`
--

CREATE TABLE `Tournois_Voies_Zones` (
  `Tournoi` int(1) UNSIGNED NOT NULL,
  `Voie` int(1) UNSIGNED NOT NULL,
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(256) NOT NULL,
  `Nb_Points_Absolu` int(1) UNSIGNED DEFAULT NULL COMMENT 'Nombre de points donné à chaque grimpeur ',
  `Nb_Points_Relatif` int(1) DEFAULT NULL COMMENT 'Nombre de points donné à chaque grimpeur, divisé par le nombre de grimpeur',
  `Cotation` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateurs`
--

CREATE TABLE `Utilisateurs` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Nom` varchar(256) NOT NULL,
  `Prénom` varchar(256) NOT NULL,
  `Genre` enum('Homme','Femme') NOT NULL,
  `Adresse_électronique` varchar(128) DEFAULT NULL,
  `Identifiant` varchar(64) DEFAULT NULL,
  `Mot_de_passe` varchar(64) DEFAULT NULL,
  `CAS_Id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Voies`
--

CREATE TABLE `Voies` (
  `Id` int(1) UNSIGNED NOT NULL,
  `Emplacement` int(1) UNSIGNED NOT NULL,
  `Couleur` int(1) UNSIGNED NOT NULL,
  `Cotation` varchar(4) DEFAULT NULL,
  `Active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `DateCréation` datetime NOT NULL DEFAULT '2018-09-14 00:00:00',
  `Description` mediumtext DEFAULT NULL,
  `Photo` varchar(64) DEFAULT NULL,
  `Vidéo` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Couleurs`
--
ALTER TABLE `Couleurs`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `Emplacements`
--
ALTER TABLE `Emplacements`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Inclinaison` (`Inclinaison`),
  ADD KEY `Mur` (`Mur`);

--
-- Index pour la table `Essais`
--
ALTER TABLE `Essais`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Mode` (`Mode`),
  ADD KEY `Utilisateur` (`Utilisateur`),
  ADD KEY `Voie` (`Voie`),
  ADD KEY `Tournoi` (`Tournoi`),
  ADD KEY `Entrée_Utilisateur` (`Entrée_Utilisateur`);

--
-- Index pour la table `Inclinaison`
--
ALTER TABLE `Inclinaison`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `Modes`
--
ALTER TABLE `Modes`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `Murs`
--
ALTER TABLE `Murs`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `Mur_Utilisateurs`
--
ALTER TABLE `Mur_Utilisateurs`
  ADD PRIMARY KEY (`Mur`,`Utilisateur`),
  ADD KEY `Utilisateur` (`Utilisateur`);

--
-- Index pour la table `Tournois`
--
ALTER TABLE `Tournois`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `Tournois_Utilisateurs`
--
ALTER TABLE `Tournois_Utilisateurs`
  ADD UNIQUE KEY `Tournoi` (`Tournoi`,`Utilisateur`) USING BTREE,
  ADD KEY `Utilisateur` (`Utilisateur`);

--
-- Index pour la table `Tournois_Voies`
--
ALTER TABLE `Tournois_Voies`
  ADD UNIQUE KEY `Tournoi` (`Tournoi`,`Voie`),
  ADD KEY `Voie` (`Voie`);

--
-- Index pour la table `Tournois_Voies_Zones`
--
ALTER TABLE `Tournois_Voies_Zones`
  ADD UNIQUE KEY `Tournoi` (`Tournoi`,`Voie`,`Id`),
  ADD KEY `Voie` (`Voie`);

--
-- Index pour la table `Utilisateurs`
--
ALTER TABLE `Utilisateurs`
  ADD UNIQUE KEY `Id` (`Id`),
  ADD UNIQUE KEY `Identifiant` (`Identifiant`),
  ADD UNIQUE KEY `Adresse_électronique` (`Adresse_électronique`),
  ADD UNIQUE KEY `CAS_Id` (`CAS_Id`);

--
-- Index pour la table `Voies`
--
ALTER TABLE `Voies`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Emplacement` (`Emplacement`),
  ADD KEY `Couleur` (`Couleur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `Couleurs`
--
ALTER TABLE `Couleurs`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `Emplacements`
--
ALTER TABLE `Emplacements`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Essais`
--
ALTER TABLE `Essais`
  MODIFY `Id` int(1) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Inclinaison`
--
ALTER TABLE `Inclinaison`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `Modes`
--
ALTER TABLE `Modes`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `Murs`
--
ALTER TABLE `Murs`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Tournois`
--
ALTER TABLE `Tournois`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Utilisateurs`
--
ALTER TABLE `Utilisateurs`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Voies`
--
ALTER TABLE `Voies`
  MODIFY `Id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `Emplacements`
--
ALTER TABLE `Emplacements`
  ADD CONSTRAINT `Emplacements_ibfk_1` FOREIGN KEY (`Mur`) REFERENCES `Murs` (`Id`),
  ADD CONSTRAINT `Emplacements_ibfk_2` FOREIGN KEY (`Inclinaison`) REFERENCES `Inclinaison` (`Id`);

--
-- Contraintes pour la table `Essais`
--
ALTER TABLE `Essais`
  ADD CONSTRAINT `Essais_ibfk_10` FOREIGN KEY (`Mode`) REFERENCES `Modes` (`Id`),
  ADD CONSTRAINT `Essais_ibfk_11` FOREIGN KEY (`Tournoi`) REFERENCES `Tournois` (`Id`),
  ADD CONSTRAINT `Essais_ibfk_12` FOREIGN KEY (`Entrée_Utilisateur`) REFERENCES `Utilisateurs` (`Id`),
  ADD CONSTRAINT `Essais_ibfk_8` FOREIGN KEY (`Utilisateur`) REFERENCES `Utilisateurs` (`Id`),
  ADD CONSTRAINT `Essais_ibfk_9` FOREIGN KEY (`Voie`) REFERENCES `Voies` (`Id`);

--
-- Contraintes pour la table `Mur_Utilisateurs`
--
ALTER TABLE `Mur_Utilisateurs`
  ADD CONSTRAINT `Mur_Utilisateurs_ibfk_1` FOREIGN KEY (`Mur`) REFERENCES `Murs` (`Id`),
  ADD CONSTRAINT `Mur_Utilisateurs_ibfk_2` FOREIGN KEY (`Utilisateur`) REFERENCES `Utilisateurs` (`Id`);

--
-- Contraintes pour la table `Tournois_Utilisateurs`
--
ALTER TABLE `Tournois_Utilisateurs`
  ADD CONSTRAINT `Tournois_Utilisateurs_ibfk_1` FOREIGN KEY (`Tournoi`) REFERENCES `Tournois` (`Id`),
  ADD CONSTRAINT `Tournois_Utilisateurs_ibfk_2` FOREIGN KEY (`Utilisateur`) REFERENCES `Utilisateurs` (`Id`);

--
-- Contraintes pour la table `Tournois_Voies`
--
ALTER TABLE `Tournois_Voies`
  ADD CONSTRAINT `Tournois_Voies_ibfk_1` FOREIGN KEY (`Tournoi`) REFERENCES `Tournois` (`Id`),
  ADD CONSTRAINT `Tournois_Voies_ibfk_2` FOREIGN KEY (`Voie`) REFERENCES `Voies` (`Id`);

--
-- Contraintes pour la table `Tournois_Voies_Zones`
--
ALTER TABLE `Tournois_Voies_Zones`
  ADD CONSTRAINT `Tournois_Voies_Zones_ibfk_1` FOREIGN KEY (`Tournoi`) REFERENCES `Tournois` (`Id`),
  ADD CONSTRAINT `Tournois_Voies_Zones_ibfk_2` FOREIGN KEY (`Voie`) REFERENCES `Tournois_Voies` (`Voie`);

--
-- Contraintes pour la table `Voies`
--
ALTER TABLE `Voies`
  ADD CONSTRAINT `Voies_ibfk_1` FOREIGN KEY (`Emplacement`) REFERENCES `Emplacements` (`Id`),
  ADD CONSTRAINT `Voies_ibfk_2` FOREIGN KEY (`Couleur`) REFERENCES `Couleurs` (`Id`);
COMMIT;