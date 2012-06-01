-- phpMyAdmin SQL Dump
-- version 3.4.11deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Ven 01 Juin 2012 à 15:11
-- Version du serveur: 5.1.61
-- Version de PHP: 5.4.0-3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `streambox`
--

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `username` text NOT NULL,
  `password` text NOT NULL,
  `email` text NOT NULL,
  `date_added` text NOT NULL,
  `rights` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`username`, `password`, `email`, `date_added`, `rights`) VALUES
('user1', 'user1', 'user1@email.com', '31/05/12', 100),
('user2', 'user2', 'user2@email.com', '01/06/12', 5);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
