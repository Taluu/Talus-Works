<?php
/**
 * debug.
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
 * @copyright ©Talus, Talus' Works 2007, 2008
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 28/12/2007, Talus
 * @last 28/09/2008, Talus
 * @ignore
 */

final class Frame_Child extends Frame {
    /**
     * @ignore
     */
    protected function main(){
        if (Sys::$uid == GUEST || Sys::$u_level > GRP_ADMIN){
            message('Circulez, y\'a rien à voir.', 'index.html', MESSAGE_ERROR);
        }

        Sync::forums();
        Sync::sujets();
        Sync::clean();

        message('OK', 'index.html', 123432, MESSAGE_CONFIRM | MESSAGE_REDIRECTION_DISABLED);
    }
}

/** EOF /**/
