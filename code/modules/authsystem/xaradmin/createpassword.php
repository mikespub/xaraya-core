<?php
/**
 * Create a new password for the user
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * createpassword - create a new password for the user
 */
function authsystem_admin_createpassword()
{
    // Security Check
    if (!xarSecurityCheck('EditAuthsystem')) return;
    // Get parameters
    if(!xarVarFetch('state', 'isset', $state, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupid', 'int:0:', $groupid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('id', 'isset', $id)) {
        throw new BadParameterException(array('parameters','admin','createpassword','roles'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    $pass = xarModAPIFunc('roles',
                          'user',
                          'makepass');
     if (empty($pass)) {
            throw new BadParameterException(null,xarML('Problem generating new password'));
     }
     $role = xarRoles::get($id);
     $modifiedstatus = $role->setPass($pass);
     $modifiedrole = $role->updateItem();
     if (!$modifiedrole) {
        return;
     }
     if (!xarModVars::get('roles', 'askpasswordemail')) {
        xarResponse::Redirect(xarModURL('roles', 'admin', 'showusers',
                      array('id' => $data['groupid'], 'state' => $data['state'])));
        return true;
    }
    else {

        xarSession::setVar('tmppass',$pass);
        xarResponse::Redirect(xarModURL('roles', 'admin', 'asknotification',
        array('id' => array($id => '1'), 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state)));
    }
}
?>