<?php
/*
 * Copyright 2005-2018 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

class CentreonVersion
{
    /**
     * @var CentreonDB
     */
    private $db;

    /**
     * Constructor
     *
     * @param CentreonDB $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get Centreon web version
     *
     * @return string
     */
    public function getVersion()
    {
        $data = '';

        $query = 'SELECT i.value FROM informations i ' .
            'WHERE i.key = "version"';
        $result = $this->db->query($query);
        if ($row = $result->fetchRow()) {
            $data = $row['value'];
        }
        return $data;
    }

    /**
     * Get all Centreon modules
     *
     * @return array
     */
    public function getModules()
    {
        $data = array();

        $query = 'SELECT name, mod_release FROM modules_informations';
        $result = $this->db->query($query);
        while ($row = $result->fetchRow()) {
            $data[$row['name']] = $row['mod_release'];
        }
        return $data;
    }

    /**
     * Get all Centreon widgets
     *
     * @return array
     */
    public function getWidgets()
    {
        $data = array();

        $query = 'SELECT title, version FROM widget_models';
        $result = $this->db->query($query);
        while ($row = $result->fetchRow()) {
            $data[$row['title']] = $row['version'];
        }
        return $data;
    }
}
