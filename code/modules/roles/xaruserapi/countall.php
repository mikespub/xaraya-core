<?php
/**
 * Count all users
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * count all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of users matching the selection criteria (cfr. getall)
 */
function roles_userapi_countall($args)
{
    extract($args);

    // Security check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolestable = $xartable['roles'];

    $bindvars = array();
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $query = "SELECT COUNT(id) FROM $rolestable WHERE state = ?";
        $bindvars[] = (int) $state;
    } else {
        $query = "SELECT COUNT(id) FROM $rolestable WHERE state != ?";
        $bindvars[] = ROLES_STATE_DELETED;
    }

    //suppress display of pending users to non-admins
    if (!xarSecurityCheck("AdminRole",0)) {
        $query .= " AND state != ?";
        $bindvars[] = ROLES_STATE_PENDING;
    }

    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's id and add
    // a where clause to the query
   if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND id != ?";
        $bindvars[] =  (int) $thisrole['id'];
    }

    $query .= " AND itemtype = ?";
    $bindvars[] = ROLES_USERTYPE;
    $bindvars[] = 0;
// cfr. xarcachemanager - this approach might change later
    $expire = xarModVars::get('roles','cache.userapi.countall');
    if (!empty($expire)){
        $result = $dbconn->CacheExecute($expire,$query,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>