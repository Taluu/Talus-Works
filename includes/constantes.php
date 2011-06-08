<?php
/**
 * Contient les constantes générales relatives à Talus' Works.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *      
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *      
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA. 
 *
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 27/12/2007, Talus
 * @last 12/10/2008, Talus
 */

// -- Constantes pour les fichiers
if (!defined('PHP_EXT')) {
    define('PHP_EXT', substr(__FILE__, -3));
}

define('OS', strtoupper(substr(PHP_OS, 0, 3)));
define('EOL', PHP_EOL);

// -- Constantes de temps
define('ONE_MINUTE', 60);
define('ONE_HOUR', 60 * ONE_MINUTE);
define('ONE_DAY', 24 * ONE_HOUR);
define('ONE_WEEK', 7 * ONE_DAY);
define('ONE_MONTH', 4 * ONE_WEEK);
define('ONE_YEAR', 12 * ONE_MONTH);

// -- Dates Relatives
define('TIME_RELATIVE_HOURS', 2); // On ne va pas plus loin que 1h et XXmin.

// -- Constantes pour les sessions
define('GUEST', 0);
define('TIME_CONNECTED', (IS_LOCAL ? (1 * ONE_YEAR) : (5 * ONE_MINUTE)));

// -- Constantes pour les messages.
// -- Types de messages
define('MESSAGE_ERROR', 0x01);
define('MESSAGE_NEUTRAL', 0x02);
define('MESSAGE_CONFIRM', 0x04);

// -- Status de la redirection
define('MESSAGE_REDIRECTION_DISABLED', 0x10);
define('MESSAGE_REDIRECTION_INSTANT', 0x20);
define('MESSAGE_REDIRECTION_ENABLED', 0x40);

// -- Temps avant redirection... Si c'est activé !
define('MESSAGE_REDIRECTION_TIME', 3);

// -- Pour la génération de mots.
define('WORDS_MIN', 4);
define('WORDS_MAX', 8);

// -- Pour les mots de passes, les types de caractères autorisés.
define('PASSWORD_LOWCASE', 0x01);
define('PASSWORD_UPPCASE', 0x02);
define('PASSWORD_NUMERIC', 0x04);
define('PASSWORD_SPECIAL', 0x08);
define('PASSWORD_ALL', PASSWORD_LOWCASE | PASSWORD_NUMERIC | PASSWORD_UPPCASE
                                                           | PASSWORD_SPECIAL);

// -- Pour les formulaires...
define('FORM_USE_CAPTCHA', true);
define('FORM_NO_CAPTCHA', false);

// -- Pour les mails...
define('MAIL_PLUS_WIDTH', 10);
define('MAIL_PLUS_HEIGHT', 10);
define('MAIL_POS_WIDTH', (MAIL_PLUS_WIDTH / 2));
define('MAIL_POS_HEIGHT', (MAIL_PLUS_HEIGHT / 2));

// -- Pour les forums...
define('CATEGORY', 0);
define('SUB_CAT', 1);
define('FORUM', 2);
define('READ_MONTHS', 6);

// -- Pour la pagination
define('TOPICS_PER_PAGE', 20);
define('POSTS_PER_PAGE', 30);
define('AROUND_PAGE', 2);
define('PAGINATION_NONE', 0);
define('PAGINATION_ACTIVATED', 1);

// -- Flags pour la pagination.
define('PAGINATION_PREV_NEXT', 0x01);
define('PAGINATION_FIRST_LAST', 0x02);
define('PAGINATION_ALL', PAGINATION_PREV_NEXT | PAGINATION_FIRST_LAST);

// -- Pour les urls.
define('URLS_MAX', 40);
define('URLS_SEPARATOR', '[...]');
define('URLS_NB_FIX', floor((URLS_MAX - strlen(URLS_SEPARATOR)) / 2));

// -- Les grps
define('GRP_ADMIN', 0);
define('GRP_MODO', 1);
define('GRP_USER', 2);
define('GRP_LS', 3);
define('GRP_GUEST', 4);
define('GRP_BANNED', 5);

// -- La modération
define('SHOW_MODULE', true);
define('HIDE_MODULE', false);

// -- Nombre de charactères minimum dans un message.
define('MIN_LENGTH', 3);

// -- Pour les tabs.
define('TABS_STATUS', 0);
define('TABS_TITLE', 1);
define('TABS_URL', 2);

// -- Pour les RSS
define('RSS_ID_NEEDED', 0);
define('RSS_TYPES', 1);

// -- Constantes du formattage de temps
define('DATE_FULL', '\l\e d/m/Y, \à H:i');
define('DATE_DATE', 'd/m/Y');
define('DATE_TIME', 'H:i');

// -- Fuseau horaire
//define('TIME_ZONE', 'Europe/Paris'); // Europe
define('TIME_ZONE', 'UTC'); // UTC

// -- Longueur de caractère pour la meta description
define('META_DESCRIPTION_STRLEN', 200);

// -- Types de BBCode
define('BBCODE_FORUM', 0x01);
define('BBCODE_BLOG', 0x02);
define('BBCODE_ALL', BBCODE_BLOG | BBCODE_FORUM);

/** EOF /**/
