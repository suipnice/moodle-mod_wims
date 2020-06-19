<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Chaînes de caractères pour le module 'wims', langue 'fr'
 *
 * @package   mod_wims
 * @category  string
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * translator: Frédéric Chiaroni <frederic@edunao.com>
 */

defined('MOODLE_INTERNAL') || die();

// Chaînes de caractères générales (notamment utilisées lors de l'ajout d'une activité dans un cours).
$string['modulename']                  = 'Classe WIMS';
$string['modulenameplural']            = 'Classes WIMS';
$string['modulename_help']             = 'Intégrez une Classe d&rsquo;exercices WIMS dans votre cours';

// Administration du module WIMS.
$string['pluginadministration']        = 'Administration du module WIMS';
$string['pluginname']                  = 'WIMS';

// Administration - Réglages du serveur WIMS.
$string['serversettings']              = 'Réglages du serveur WIMS';
$string['adminnameserverurl']          = 'URL du serveur WIMS';
$string['admindescserverurl']          = '';

$string['adminnameallowselfsigcerts']  = 'Autoriser les certificats auto-signés';
$string['admindescallowselfsigcerts']  = '';

$string['adminnameserverpassword']     = 'Mot de passe de connexion au serveur WIMS';
$string['admindescserverpassword']     = 'Doit être le même que celui que vous avez défini dans les fichiers placés dans le répertoire ".connexions" du serveur WIMS.';

$string['adminnameqcloffset']          = 'Point de départ de la numérotation des classes WIMS';
$string['admindescqcloffset']          = 'Doit être entre 11111 et 10^9';

// Administration - Réglages de l'interface.
$string['wimssettings']                = 'Réglages de l&rsquo;interface Moodle-Wims';
$string['adminnamelang']               = 'Langue des classes';
$string['admindesclang']               = 'Code de langue par défaut des classes WIMS qui seront créées. (valeurs possibles : ca, cn, en, es, fr, it, nl, si, tw, de)';

$string['adminnamedefaultinstitution'] = 'Institution';
$string['admindescdefaultinstitution'] = 'L&rsquo;établissemnt affiché dans les classes WIMS';

$string['adminnameusenameinlogin']     = 'Inclure les noms d&rsquo;utilisateurs dans les login WIMS';
$string['admindescusenameinlogin']     = '';

$string['adminnameusegradepage']       = 'Rediriger les liens du carnet de notes MOODLE vers la page de notes de WIMS';
$string['admindescusegradepage']       = '';

// Administration - Réglages de debug.
$string['wimsdebugsettings']           = 'Réglages de debug de l&rsquo;interface WIMS';
$string['adminnamedebugviewpage']      = 'Activer l&rsquo;affichage d&rsquo;information de debug: VIEW';
$string['admindescdebugviewpage']      = '';

$string['adminnamedebugcron']          = 'Activer l&rsquo;affichage d&rsquo;information de debug: CRON';
$string['admindescdebugcron']          = '';

$string['adminnamedebugsettings']      = 'Activer l&rsquo;affichage d&rsquo;information de debug: SETTINGS';
$string['admindescdebugsettings']      = '';

// Messages d'erreurs
$string['class_select_failed_title']   = 'Impossible d&rsquo;accéder à la classe WIMS.';
$string['class_select_failed_desc']    = 'Le serveur est probablement indisponible. Merci de retester dans quelques minutes, ou informez-en l&rsquo;administrateur.';

// Configuration des instances de modules WIMS.
$string['name']                        = 'Nom de l&rsquo;activité';
$string['userinstitution']             = 'Nom de l&rsquo;établissement';
$string['userfirstname']               = 'Prénom de l&rsquo;enseignant';
$string['userlastname']                = 'Nom de famille de l&rsquo;enseignant';
$string['useremail']                   = 'Adresse électronique de contact';

// Configuration des feuilles et examens.
$string['sheettypeworksheets']         = 'Feuilles :';
$string['sheettypeexams']              = 'Examens :';
$string['sheettitle']                  = 'Titre';
$string['sheetgraded']                 = 'Notes Suivies';
$string['sheetexpiry']                 = 'Date d&rsquo;expiration';
$string['wimsstatus1']                 = 'Actif';
$string['wimsstatus2']                 = 'Expiré';
$string['wimsstatusx']                 = 'Inactif';

// Textes divers.
$string['page-mod-wims-x']             = 'N&rsquo;importe quelle page du module Wims';
$string['modulename_link']             = 'mod/wims/view';

// Taches planifiées.
$string['updatescores']             = 'Mise à jour des notes des activités WIMS';
