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
$string['modulename_help']             = 'Intégrez une classe d’exercices WIMS dans votre cours';

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

// Administration - Réglages de l'interface.
$string['wimssettings']                = 'Réglages de l’interface Moodle-Wims';
$string['adminnamelang']               = 'Langue des classes';
$string['admindesclang']               = 'Code de langue par défaut des classes WIMS qui seront créées. (valeurs possibles : ca, cn, en, es, fr, it, nl, si, tw, de)';

$string['adminnamedefaultinstitution'] = 'Institution (valeur par défaut)';
$string['admindescdefaultinstitution'] = 'L’établissement par défaut affiché dans les classes WIMS.';

$string['adminnameusenameinlogin']     = 'Inclure les noms d’utilisateurs dans les login WIMS';
$string['admindescusenameinlogin']     = '';

$string['adminnameusegradepage']       = 'Rediriger les liens du carnet de notes MOODLE vers la page de notes de WIMS';
$string['admindescusegradepage']       = '';

// Administration - Réglages de debug.
$string['wimsdebugsettings']           = 'Réglages de debug de l’interface WIMS';
$string['adminnamedebugviewpage']      = 'Activer l’affichage d’information de debug: VIEW';
$string['admindescdebugviewpage']      = '';

$string['adminnamedebugcron']          = 'Activer l’affichage d’information de debug: CRON';
$string['admindescdebugcron']          = '';

$string['adminnamedebugsettings']      = 'Activer l’affichage d’information de debug: SETTINGS';
$string['admindescdebugsettings']      = '';

// Capacités (roles).
$string['wims:view']                   = 'Accéder à une classe WIMS';
$string['wims:addinstance']            = 'Ajouter une classe WIMS';

// Messages d'erreurs.
$string['class_select_failed_title']   = 'Impossible d’accéder à la classe WIMS.';
$string['class_select_failed_desc']    = 'Le serveur est probablement indisponible. Merci de retester dans quelques minutes, ou informez-en l’administrateur.';
$string['class_select_refused_title']  = 'Accès à cette classe WIMS refusé.';
$string['class_select_refused_desc']   = 'La classe WIMS que vous tentez d’atteindre n’autorise pas un accès depuis ce serveur Moodle. Accédez à la classe directement par WIMS ou contacter l’administrateur.';
$string['class_deleted']               = 'La classe WIMS que vous cherchez n’existe plus sur ce serveur.';
$string['class_deleted_with_id']       = 'La classe WIMS n°{$a} n’existe plus sur ce serveur.';

// Sauvegardes de classes.
$string['restore_or_new']              = 'Vous pouvez au choix restaurer une sauvegarde précédente ou créer une classe vide.';
$string['backup_legend']               = 'Restaurer une précédente sauvegarde';
$string['backup_found']                = 'Nous avons trouvé une sauvegarde qui correspond à l’identifiant de votre classe.';
$string['backups_found']               = 'Nous avons trouvé {$a} sauvegardes correspondant à l’identifiant de votre classe.';

$string['backup_select']               = 'Choisissez une sauvegarde';
$string['backup_restore']              = 'Restaurer';
$string['backup_help']                 = 'WIMS effectue automatiquement une sauvegarde avant de supprimer une classe. Choisissez l’année de suppression estimée.';

$string['create_new_legend']           = 'Créer une nouvelle classe';
$string['create_new_class']            = 'Créer une classe vide';
$string['create_class_desc']           = 'Utilisez le bouton ci-dessous pour créer une classe vierge de tout contenu.';


// Configuration des instances de modules WIMS.
$string['name']                        = 'Nom de l’activité';
$string['userinstitution']             = 'Nom de l’établissement';
$string['userfirstname']               = 'Prénom de l’enseignant';
$string['userlastname']                = 'Nom de famille de l’enseignant';
$string['useremail']                   = 'Adresse électronique de contact';

// Configuration des feuilles et examens.
$string['sheettypeworksheets']         = 'Feuilles :';
$string['sheettypeexams']              = 'Examens :';
$string['sheettitle']                  = 'Titre';
$string['sheetgraded']                 = 'Notes Suivies';
$string['sheetexpiry']                 = 'Date d’expiration';
$string['wimsstatus1']                 = 'Actif';
$string['wimsstatus2']                 = 'Expiré';
$string['wimsstatusx']                 = 'Inactif';

// Textes divers.
$string['page-mod-wims-x']             = 'N’importe quelle page du module Wims';
$string['modulename_link']             = 'mod/wims/view';

// Taches planifiées.
$string['updatescores']                = 'Mise à jour des notes des activités WIMS';

// Grade items.
/*for ($i = 1; $i < 150; $i++) {
    $string['grade_exam_'.$i.'_name'] = 'Score de l’examen '.$i;
}*/

// Données personelles.

$string['privacy:metadata:core_grades'] = 'L’activité WIMS enregistre dans le carnet de note Moodle les scores obtenus dans les classes WIMS.';

$string['privacy:metadata:wims'] = 'Informations sur la classe WIMS';
$string['privacy:metadata:wims_classes:id']              = '';
$string['privacy:metadata:wims_classes:course']          = '';
$string['privacy:metadata:wims_classes:name']            = 'titre de la classe indiqué par son créateur';
$string['privacy:metadata:wims_classes:userinstitution'] = 'Institution name specified by the class creator is sent from Moodle to the external WIMS classroom.';
$string['privacy:metadata:wims_classes:userfirstname']   = 'First name specified by the class creator, used to create the classroom on the WIMS server';
$string['privacy:metadata:wims_classes:userlastname']    = 'Last name specified by the class creator, used to create the classroom on the WIMS server';
$string['privacy:metadata:wims_classes:useremail']       = 'Email specified by the class creator, used to create the classroom on the WIMS server.';
$string['privacy:metadata:wims_classes:userdata']        = 'Données utilisateur';

$string['privacy:metadata:wims_server']                  = 'In order to integrate with a remote WIMS classroom, user data needs to be exchanged with that classroom.';
$string['privacy:metadata:wims_server:userid']           = 'The user id is sent from Moodle to the external WIMS classroom.';
$string['privacy:metadata:wims_server:fullname']         = 'The user full name is sent from Moodle to the external WIMS classroom.';
