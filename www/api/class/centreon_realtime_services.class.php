<?php
/*
 * Copyright 2005-2017 Centreon
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

require_once _CENTREON_PATH_ . "/www/class/centreonDB.class.php";
require_once dirname(__FILE__) . "/centreon_configuration_objects.class.php";
require_once dirname(__FILE__) . "/centreon_realtime_base.class.php";

class CentreonRealtimeServices extends CentreonRealtimeBase
{
    /**
     * @var CentreonDB
     */
    protected $aclObj;
    protected $admin;

    /* parameters */
    protected $limit;
    protected $number;
    protected $status;
    protected $hostgroup;
    protected $search;
    protected $searchHost;
    protected $viewType;
    protected $sortType;
    protected $order;
    protected $instance;
    protected $criticality;

    protected $fieldList;

    protected $criticalityList;

    /**
     * CentreonConfigurationService constructor.
     */
    public function __construct()
    {
        global $centreon;

        parent::__construct();

        // Init ACL
        if (!$centreon->user->admin) {
            $this->admin = 0;
            $this->aclObj = new CentreonACL($centreon->user->user_id, $centreon->user->admin);
        } else {
            $this->admin = 1;
        }

        /* Init Values */
        $this->getCriticality();
    }

    /**
     * @return array
     */
    public function getList()
    {
        $this->setServiceFilters();
        $this->setServiceFieldList();
        return $this->getServiceState();
    }

    protected function getFieldContent()
    {
        $tab = explode(',', $this->arguments['fields']);

        $fieldList = array();
        foreach ($tab as $key) {
            $fieldList[$key] = 1;
        }
        return ($fieldList);
    }

    protected function setServiceFilters()
    {
        /* Pagination Elements */
        if (isset($this->arguments['limit'])) {
            $this->limit = $this->arguments['limit'];
        } else {
            $this->limit = 30;
        }
        if (isset($this->arguments['number'])) {
            $this->number = $this->arguments['number'];
        } else {
            $this->number = 0;
        }

        /* Filters */
        if (isset($this->arguments['status'])) {
            $this->status = $this->arguments['status'];
        } else {
            $this->status = null;
        }
        if (isset($this->arguments['hostgroup'])) {
            $this->hostgroup = $this->arguments['hostgroup'];
        } else {
            $this->hostgroup = null;
        }
        if (isset($this->arguments['servicegroup'])) {
            $this->servicegroup = $this->arguments['servicegroup'];
        } else {
            $this->servicegroup = null;
        }
        if (isset($this->arguments['search'])) {
            $this->search = $this->arguments['search'];
        } else {
            $this->search = null;
        }
        if (isset($this->arguments['searchHost'])) {
            $this->searchHost = $this->arguments['searchHost'];
        } else {
            $this->searchHost = null;
        }
        if (isset($this->arguments['searchOutput'])) {
            $this->searchOutput = $this->arguments['searchOutput'];
        } else {
            $this->searchOutput = null;
        }
        if (isset($this->arguments['instance'])) {
            $this->instance = $this->arguments['instance'];
        } else {
            $this->instance = null;
        }

        /* view properties */
        if (isset($this->arguments['viewType'])) {
            $this->viewType = $this->arguments['viewType'];
        } else {
            $this->viewType = null;
        }
        if (isset($this->arguments['sortType'])) {
            $this->sortType = $this->arguments['sortType'];
        } else {
            $this->sortType = null;
        }
        if (isset($this->arguments['order'])) {
            $this->order = $this->arguments['order'];
        } else {
            $this->order = null;
        }
    }

    protected function setServiceFieldList()
    {
        $fields = array();

        if (!isset($this->arguments['fields'])) {
            $fields["h.host_id as id"] = 'host_id';
            $fields["h.name"] = 'name';
            $fields["s.service_id"] = 'service_id';
            $fields["s.state"] = 'state';
            $fields["s.state_type"] = 'state_type';
            $fields["s.output"] = 'output';
            $fields["s.perfdata"] = 'perfdata';
            $fields["s.max_check_attempts"] = 'max_check_attempts';
            $fields["s.check_attempt"] = 'check_attempt';
            $fields["s.last_check"] = 'last_check';
            $fields["s.last_state_change"] = 'last_state_change';
            $fields["s.last_hard_state_change"] = 'last_hard_state_change';
            $fields["s.acknowledged"] = 'acknowledged';
            $fields["cv.value as criticality"] = 'criticality';
        } else {
            $tab = explode(',', $this->arguments['fields']);

            $fieldList = array();
            foreach ($tab as $key) {
                $fieldList[trim($key)] = 1;
            }

            /* hosts informations */
            if (isset($fieldList['host_id'])) {
                $fields["h.host_id"] = 'host_id';
            }
            if (isset($fieldList['host_name'])) {
                $fields["h.name as host_name"] = 'host_name';
            }
            if (isset($fieldList['host_alias'])) {
                $fields["h.alias as host_alias"] = 'host_alias';
            }
            if (isset($fieldList['host_address'])) {
                $fields["h.address as host_address"] = 'host_address';
            }
            if (isset($fieldList['host_state'])) {
                $fields["h.state as host_state"] = 'host_state';
            }
            if (isset($fieldList['host_state_type'])) {
                $fields["h.state_type as host_state_type"] = 'host_state_type';
            }
            if (isset($fieldList['host_output'])) {
                $fields["h.output as host_output"] = 'host_output';
            }
            if (isset($fieldList['host_last_check'])) {
                $fields["h.last_check as host_last_check"] = 'host_last_check';
            }
            if (isset($fieldList['host_next_check'])) {
                $fields["h.next_check as host_next_check"] = 'host_next_check';
            }
            if (isset($fieldList['host_acknowledged'])) {
                $fields["h.acknowledged as host_acknowledged"] = 'host_acknowledged';
            }
            if (isset($fieldList['instance'])) {
                $fields["i.name as instance_name"] = 'instance';
            }
            if (isset($fieldList['instance_id'])) {
                $fields["i.id as instance_id"] = 'instance_id';
            }
            if (isset($fieldList['host_action_url'])) {
                $fields["h.action_url as host_action_url"] = 'host_action_url';
            }
            if (isset($fieldList['host_notes_url'])) {
                $fields["h.notes_url as host_notes_url"] = 'host_notes_url';
            }
            if (isset($fieldList['host_notes'])) {
                $fields["h.notes as host_notes"] = 'host_notes';
            }
            if (isset($fieldList['host_icon_image'])) {
                $fields["h.icon_image as host_icon_image"] = 'host_icon_image';
            }

            /* services informations */
            if (isset($fieldList['description'])) {
                $fields["s.description"] = 'description';
            }
            if (isset($fieldList['state'])) {
                $fields["s.state"] = 'state';
            }
            if (isset($fieldList['state_type'])) {
                $fields["s.state_type"] = 'state_type';
            }
            if (isset($fieldList['id'])) {
                $fields["s.service_id"] = 'service_id';
            }
            if (isset($fieldList['output'])) {
                $fields["s.output"] = 'output';
            }
            if (isset($fieldList['perfdata'])) {
                $fields["s.perfdata"] = 'perfdata';
            }
            if (isset($fieldList['current_attempt'])) {
                $fields["s.check_attempt as current_attempt"] = 'current_attempt';
            }
            if (isset($fieldList['last_update'])) {
                $fields["s.last_update"] = 'last_update';
            }
            if (isset($fieldList['last_state_change'])) {
                $fields["s.last_state_change"] = 'last_state_change';
            }
            if (isset($fieldList['last_hard_state_change'])) {
                $fields["s.last_hard_state_change"] = 'last_hard_state_change';
            }
            if (isset($fieldList['last_state_change'])) {
                $fields["s.last_state_change"] = 'last_state_change';
            }
            if (isset($fieldList['last_check'])) {
                $fields["s.last_check"] = 'last_check';
            }
            if (isset($fieldList['next_check'])) {
                $fields["s.next_check"] = 'next_check';
            }
            if (isset($fieldList['max_check_attempts'])) {
                $fields["s.max_check_attempts"] = 'max_check_attempts';
            }
            if (isset($fieldList['notes'])) {
                $fields["s.notes"] = 'notes';
            }
            if (isset($fieldList['notes_url'])) {
                $fields["s.notes_url"] = 'notes_url';
            }
            if (isset($fieldList['action_url'])) {
                $fields["s.action_url"] = 'action_url';
            }
            if (isset($fieldList['icon_image'])) {
                $fields["s.icon_image"] = 'icon_image';
            }
            if (isset($fieldList['display_name'])) {
                $fields["s.display_name"] = 'display_name';
            }
            if (isset($fieldList['notify'])) {
                $fields["s.notify"] = 'notify';
            }
            if (isset($fieldList['acknowledged'])) {
                $fields["s.acknowledged"] = 'acknowledged';
            }
            if (isset($fieldList['passive_checks'])) {
                $fields["s.passive_checks"] = 'passive_checks';
            }
            if (isset($fieldList['active_checks'])) {
                $fields["s.active_checks"] = 'active_checks';
            }
            if (isset($fieldList['event_handler_enabled'])) {
                $fields["s.event_handler_enabled"] = 'event_handler_enabled';
            }
            if (isset($fieldList['flapping'])) {
                $fields["s.flapping"] = 'flapping';
            }
            if (isset($fieldList['scheduled_downtime_depth'])) {
                $fields["s.scheduled_downtime_depth"] = 'scheduled_downtime_depth';
            }
            if (isset($fieldList['flap_detection'])) {
                $fields["s.flap_detection"] = 'flap_detection';
            }
            if (isset($fieldList['criticality'])) {
                $fields["cv.value as criticality"] = 'criticality';
            }
        }

        /* Build Field List */
        $this->fieldList = "";
        foreach ($fields as $key => $value) {
            if ($this->fieldList != '') {
                $this->fieldList .= ', ';
            }
            $this->fieldList .= $key;
        }
    }

    /**
     * @return array
     */
    public function getServiceState()
    {
        /** * *************************************************
         * Get Service status
         */
        $instance_filter = "";
        if ($this->instance != -1 && !empty($this->instance)) {
            $instance_filter = " AND h.instance_id = " . $this->instance . " ";
        }

        /* Search string to a host name, alias or address */
        $searchHost = "";
        if ($this->searchHost) {
            $searchHost .= " AND (h.name LIKE '%" . CentreonDB::escape($this->searchHost) . "%' ";
            $searchHost .= " OR h.alias LIKE '%" . CentreonDB::escape($this->searchHost) . "%' ";
            $searchHost .= " OR h.address LIKE '%" . CentreonDB::escape($this->searchHost) . "%') ";
        }

        $searchService = "";
        if ($this->search) {
            $searchService .= " AND (s.description LIKE '%" . CentreonDB::escape($this->search) . "%' ";
            $searchService .= " OR s.display_name LIKE '%" . CentreonDB::escape($this->search) . "%')";
        }
        $searchOutput = "";
        if ($this->searchOutput) {
            $searchOutput .= " AND s.output LIKE '%" . CentreonDB::escape($this->searchOutput) . "%' ";
        }

        $tabOrder = array();
        $tabOrder["criticality_id"] = " ORDER BY criticality " . $this->order . ", h.name, s.description ";
        $tabOrder["host_name"] = " ORDER BY h.name " . $this->order . ", s.description ";
        $tabOrder["service_description"] = " ORDER BY s.description " . $this->order . ", h.name";
        $tabOrder["current_state"] = " ORDER BY s.state " . $this->order . ", h.name, s.description";
        $tabOrder["last_state_change"] = " ORDER BY s.last_state_change " . $this->order . ", h.name, s.description";
        $tabOrder["last_hard_state_change"] = " ORDER by s.last_hard_state_change " . $this->order .
            ", h.name, s.description";
        $tabOrder["last_check"] = " ORDER BY s.last_check " . $this->order . ", h.name, s.description";
        $tabOrder["current_attempt"] = " ORDER BY s.check_attempt " . $this->order . ", h.name, s.description";
        $tabOrder["output"] = " ORDER BY s.output " . $this->order . ", h.name, s.description";
        $tabOrder["default"] = " ORDER BY s.description " . $this->order . ", h.name";

        $request = "SELECT SQL_CALC_FOUND_ROWS DISTINCT " . $this->fieldList . " ";
        $request .= " FROM hosts h, instances i ";
        if (isset($this->hostgroup) && $this->hostgroup != 0) {
            $request .= ", hosts_hostgroups hg, hostgroups hg2";
        }
        if (isset($this->servicegroup) && $this->servicegroup != 0) {
            $request .= ", services_servicegroups ssg, servicegroups sg";
        }
        if ($this->criticality) {
            $request .= ", customvariables cvs ";
        }
        if (!$this->admin) {
            $request .= ", centreon_acl ";
        }
        $request .= ", services s LEFT JOIN customvariables cv ON (s.service_id = cv.service_id " .
            "AND cv.host_id = s.host_id AND cv.name = 'CRITICALITY_LEVEL') ";
        $request .= " WHERE h.host_id = s.host_id " .
            "AND s.enabled = 1 " .
            "AND h.enabled = 1 " .
            "AND h.instance_id = i.instance_id ";
        if ($this->criticality) {
            $request .= " AND s.service_id = cvs. service_id " .
                "AND cvs.host_id = h.host_id " .
                "AND cvs.name = 'CRITICALITY_ID' " .
                "AND cvs.value = '" . CentreonDB::escape($this->criticality) . "' ";
        }
        $request .= " AND h.name NOT LIKE '_Module_BAM%' ";

        if ($searchHost) {
            $request .= $searchHost;
        }
        if ($searchService) {
            $request .= $searchService;
        }
        if ($searchOutput) {
            $request .= $searchOutput;
        }
        $request .= $instance_filter;

        if (preg_match("/^unhandled/", $this->viewType)) {
            if (preg_match("/^svc_unhandled_(warning|critical|unknown)\$/", $this->viewType, $matches)) {
                if (isset($matches[1]) && $matches[1] == 'warning') {
                    $request .= " AND s.state = 1 ";
                }
                if (isset($matches[1]) && $matches[1] == "critical") {
                    $request .= " AND s.state = 2 ";
                } elseif (isset($matches[1]) && $matches[1] == "unknown") {
                    $request .= " AND s.state = 3 ";
                } elseif (isset($matches[1]) && $matches[1] == "pending") {
                    $request .= " AND s.state = 4 ";
                } else {
                    $request .= " AND s.state != 0 ";
                }
            } else {
                $request .= " AND (s.state != 0 AND s.state != 4) ";
            }
            $request .= " AND s.state_type = 1";
            $request .= " AND s.acknowledged = 0";
            $request .= " AND s.scheduled_downtime_depth = 0";
            $request .= " AND h.acknowledged = 0 AND h.scheduled_downtime_depth = 0 ";
        } elseif ($this->viewType == "problems") {
            $request .= " AND s.state != 0 AND s.state != 4 ";
        }

        if ($this->status == "ok") {
            $request .= " AND s.state = 0";
        } elseif ($this->status == "warning") {
            $request .= " AND s.state = 1";
        } elseif ($this->status == "critical") {
            $request .= " AND s.state = 2";
        } elseif ($this->status == "unknown") {
            $request .= " AND s.state = 3";
        } elseif ($this->status == "pending") {
            $request .= " AND s.state = 4";
        }

        /**
         * HostGroup Filter
         */
        if (isset($this->hostgroup) && $this->hostgroup != 0) {
            $request .= " AND hg.hostgroup_id = hg2.hostgroup_id " .
                "AND hg.host_id = h.host_id AND hg.hostgroup_id IN (" . $this->hostgroup . ") ";
        }
        /**
         * ServiceGroup Filter
         */
        if (isset($this->servicegroup) && $this->servicegroup != 0) {
            $request .= " AND ssg.servicegroup_id = sg.servicegroup_id " .
                "AND ssg.service_id = s.service_id AND ssg.servicegroup_id IN (" . $this->servicegroup . ") ";
        }

        /**
         * ACL activation
         */
        if (!$this->admin) {
            $request .= " AND h.host_id = centreon_acl.host_id " .
                "AND s.service_id = centreon_acl.service_id " .
                "AND group_id IN (" . $this->aclObj->getAccessGroupsString() . ") ";
        }

        (isset($tabOrder[$this->sortType])) ? $request .= $tabOrder[$this->sortType] : $request .= $tabOrder["default"];
        $request .= " LIMIT " . ($this->number * $this->limit) . "," . $this->limit;
        $dbResult = $this->realTimeDb->query($request);

        $dataList = array();
        while ($data = $dbResult->fetchRow()) {
            if (isset($data['criticality']) && isset($this->criticalityList[$data['criticality']])) {
                $data["criticality"] = $this->criticalityList[$data['criticality']];
            }
            $dataList[] = $data;
        }
        return $dataList;
    }

    protected function getCriticality()
    {
        $this->criticalityList = array();

        $sql = "SELECT `sc_id`, `sc_name`, `level`, `icon_id`, `sc_description` FROM `service_categories` " .
            "WHERE `level` IS NOT NULL ORDER BY `level` DESC";
        $res = $this->pearDB->query($sql);
        while ($row = $res->fetchRow()) {
            $this->criticalityList[$row['sc_name']] = $row;
        }
    }
}
