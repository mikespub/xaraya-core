<?php
/**
 * Display the privileges of this role
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
 * showprivileges - display the privileges of this role
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_showprivileges()
{
    if (!xarVarFetch('id', 'int:1:', $id)) return;

    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    // Call the Roles class and get the role
    $role = xarRoles::get($id);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    sys::import('modules.privileges.class.privileges');

// -------------------------------------------------------------------
    // Get the inherited privileges
    $ancestors = $role->getRoleAncestors();
    $inherited = array();
    // this assembles the irreducuble set of privileges
    // needs to be moved to a method of the Role class
    $maxlevel = 0;
    foreach ($ancestors as $ancestor) {
        if ($ancestor->getLevel() > $maxlevel) $maxlevel = $ancestor->getLevel();
        $privs = $ancestor->getAssignedPrivileges();
        $allprivileges = array();
        foreach ($privs as $priv) {
            $allprivileges[] = $priv;
            $allprivileges = array_merge($allprivileges, $priv->getDescendants());
        }
        $groupname = $ancestor->getName();
        $groupid = $ancestor->getID();
        foreach($allprivileges as $priv) {
            if ($priv->getModule() == null) {
                $inherited[] = array('privid' => $priv->getID(),
                        'name' => $priv->getName(),
                        'realm' => "",
                        'module' => "",
                        'component' => "",
                        'instance' => "",
                        'level' => "",
                        'groupid' => $groupid,
                        'groupname' => $groupname,
                        'relation' => $ancestor->getLevel(),
                        'status' => 1,
                        'object' => $priv);
            } else {
                $inherited[] = array('privid' => $priv->getID(),
                        'name' => $priv->getName(),
                        'realm' => $priv->getRealm(),
                        'module' => $priv->getModule(),
                        'component' => $priv->getComponent(),
                        'instance' => $priv->getInstance(),
                        'level' => xarPrivileges::$levels[$priv->getLevel()],
                        'groupid' => $groupid,
                        'groupname' => $groupname,
                        'relation' => $ancestor->getLevel(),
                        'status' => 3,
                        'object' => $priv);
            }
        }
    }
    // resort the array for display purposes
    $inherited = array_reverse($inherited);

// -------------------------------------------------------------------
// get the array of objects of the assigned set of privileges
    $curprivs = $role->getAssignedPrivileges();
    $directassigned = array();
    $curprivileges = array();
    // for each one winnow the assigned privileges and then the inherited
    foreach ($curprivs as $priv) {
        $directassigned[] = $priv->getID();
        $curprivileges = array_merge(array($priv), $curprivileges);
        $curprivileges = array_merge($priv->getDescendants(), $curprivileges);
    }
    // extract the info for display by the template
    $currentprivileges = array();
    foreach ($curprivileges as $priv) {
        $frozen = !xarSecurityCheck('DeassignPrivilege',0,'Privileges',$priv->getName());
        if ($priv->getModule() == null) {
            $currentprivileges[] = array('privid' => $priv->getID(),
                'name' => $priv->getName(),
                'realm' => "",
                'module' => "",
                'component' => "",
                'instance' => "",
                'level' => "",
                'frozen' => $frozen,
                'relation' => 0,
                'status' => 1,
                'object' => $priv);
        } else {
            $currentprivileges[] = array('privid' => $priv->getID(),
                'name' => $priv->getName(),
                'realm' => $priv->getRealm(),
                'module' => $priv->getModule(),
                'component' => $priv->getComponent(),
                'instance' => $priv->getInstance(),
                'level' => xarPrivileges::$levels[$priv->getLevel()],
                'frozen' => $frozen,
                'relation' => 0,
                'status' => 3,
                'object' => $priv);
        }
    }
    $currentprivileges = array_reverse($currentprivileges);

// -------------------------------------------------------------------
// Now we have to compare the privileges between the different levels

    $privilegesdone = $currentprivileges;
    $privilegestodo = $inherited;
    unset($inherited);
    $inherited[0] = $currentprivileges;
    for ($i=1;$i<$maxlevel+1;$i++) {
        $inherited[$i] = array();
        foreach ($privilegestodo as $todo) {
            if ($todo['relation'] != $i) continue;
            foreach($privilegesdone as $done) {
                if (!($done['relation'] < $todo['relation'])) continue;
                if (xarMasks::includes($done['object']->normalform,$todo['object']->normalform)) {
                    $todo['status'] = 1;
                    break;
                }
                elseif (xarMasks::includes($todo['object']->normalform,$done['object']->normalform)) {
                    $todo['status'] = 2;
                }
            }
            $privilegesdone[] = $todo;
//            unset($todo['object']);
            $inherited[$i][] = $todo;
//            $inherited[] = $todo;
        }
    }
//    $inherited = array_reverse($inherited);
// -------------------------------------------------------------------
// Finally we have to compare the privileges of a given level among each other

    for ($i=0;$i<$maxlevel+1;$i++) {
        $xs = $inherited[$i];
        $ys = $xs;
        $inherited[$i] = array();
        foreach ($xs as $x) {
            if ($x['status'] != 1) {
                foreach ($ys as $y) {
                    if ($y['module'] == null) continue;
                    if ($y['privid'] == $x['privid']) continue;
                    if ($y['object']->implies($x['object'])) {
                        $x['status'] = 1;
                        break;
                    }
                    elseif (xarMasks::includes($x['object']->normalform,$y['object']->normalform) && !$x['object']->implies($y['object'])) {
                        $x['status'] = 2;
                    }
                }
            }
        $inherited[$i][] = $x;
        }
    }
    $xs = array();
    for ($i=1;$i<$maxlevel+1;$i++) {
        $xs = array_merge($xs, $inherited[$i]);
    }
    $currentprivileges = $inherited[0];
    $inherited = array_reverse($xs);
//    echo var_dump($inherited);exit;

// -------------------------------------------------------------------
// Load Template
    $data['pname'] = $role->getName();
    $data['itemtype'] = $role->getType();
    $data['basetype'] = $data['itemtype'];
    $types = xarMod::apiFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['roleid'] = $id;
    $data['inherited'] = $inherited;
    $data['privileges'] = $currentprivileges;
    $data['directassigned'] = $directassigned;
    $data['authid'] = xarSecGenAuthKey();
    $data['groups'] = xarRoles::getgroups();
    $data['removeurl'] = xarModURL('roles',
        'admin',
        'removeprivilege',
        array('roleid' => $id));
    $data['groupurl'] = xarModURL('roles',
        'admin',
        'showprivileges');
    $data['addlabel'] = xarML('Add');
    return $data;
    // redirect to the next page
    xarResponse::Redirect(xarModURL('roles', 'admin', 'new'));
}

?>