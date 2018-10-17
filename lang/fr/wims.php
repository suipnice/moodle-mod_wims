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
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @auteur     Sadge (daniel@edunao.com)
 * @traducteur Frédéric Chiaroni (frederic@edunao.com)
 * @package    mod_wims
 * @licence    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Chaînes de caractères générales (notamment utilisées lors de l'ajout d'une activité dans un cours)
$string['modulename']                  = 'Classe WIMS';
$string['modulenameplural']            = 'Classes WIMS';
$string['modulename_help']             = 'Intégrez une Classe d&rsquo;exercices WIMS dans votre cours';

// Administration du module Wims
$string['pluginadministration']        = 'Administration du module Wims';
$string['pluginname']                  = 'WIMS';

// Administration du module Wims - Réglages du serveur Wims
$string['serversettings']              = 'Réglages du serveur WIMS';
$string['adminnameserverurl']          = 'URL du serveur WIMS';
$string['admindescserverurl']          = '';

$string['adminnameallowselfsigcerts']  = 'Autoriser les certificats auto-signés';
$string['admindescallowselfsigcerts']  = '';

$string['adminnameserverpassword']     = 'Mot de passe de connexion au serveur WIMS';
$string['admindescserverpassword']     = 'Doit être le même que celui que vous avez défini dans les fichiers placés dans le répertoire ".connexions" du serveur WIMS.';

$string['adminnameqcloffset']          = 'Point de départ de la numérotation des classes WIMS';
$string['admindescqcloffset']          = 'Doit être entre 11111 et 10^9';

// Administration du module Wims - Réglages du serveur Wims
$string['wimssettings']                = 'Réglages de l&rsquo;interface Moodle-Wims';
$string['adminnamelang']               = 'Langue des classes';
$string['admindesclang']               = 'La langue par défaut des classes WIMS qui seront créées.';

$string['adminnamedefaultinstitution'] = 'Institution';
$string['admindescdefaultinstitution'] = 'L&rsquo;établissemnt affiché dans les classes WIMS';

$string['adminnameusenameinlogin']     = 'Inclure les noms d&rsquo;utilisateurs dans les login WIMS';
$string['admindescusenameinlogin']     = '';

$string['adminnameusegradepage']       = 'Rediriger les liens du carnet de notes MOODLE vers la page de notes de WIMS';
$string['admindescusegradepage']       = '';

// Administration du module Wims - Réglages de debug
$string['wimsdebugsettings']           = 'Réglages de debug de l&rsquo;interface WIMS';
$string['adminnamedebugviewpage']      = 'Activer l&rsquo;affichage d&rsquo;information de debug: VIEW';
$string['admindescdebugviewpage']      = '';

$string['adminnamedebugcron']          = 'Activer l&rsquo;affichage d&rsquo;information de debug: CRON';
$string['admindescdebugcron']          = '';

$string['adminnamedebugsettings']      = 'Activer l&rsquo;affichage d&rsquo;information de debug: SETTINGS';
$string['admindescdebugsettings']      = '';


// Configuration des instances de modules Wims
$string['name']                        = 'Nom de l&rsquo;activité';
$string['userinstitution']             = 'Nom de l&rsquo;établissement';
$string['userfirstname']               = 'Prénom de l&rsquo;enseignant';
$string['userlastname']                = 'Nom de famille de l&rsquo;enseignant';
$string['username']                    = 'Nom complet de l&rsquo;enseignant';
$string['useremail']                   = 'Adresse électronique de contact';

// worksheet and exam settings
$string['sheettypeworksheets']         = '';
$string['sheettypeexams']              = 'Examens : ';
$string['sheettitle']                  = 'Titre';
$string['sheetgraded']                 = 'Notes Suivies';
$string['sheetexpiry']                 = 'Date d&rsquo;expiration';
$string['wimsstatus1']                 = 'Actif';
$string['wimsstatus2']                 = 'Expiré';
$string['wimsstatusx']                 = 'Inactif';

// Misc strings
$string['page-mod-wims-x']             = 'N&rsquo;importe quelle page du module Wims';
$string['modulename_link']             = 'mod/wims/view';
