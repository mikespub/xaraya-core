<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 *
 * @return string output display string
 */
function dynamicdata_user_view(Array $args=array())
{
    // Old-style arguments
    if(!xarVar::fetch('objectid', 'int',   $objectid,  NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('module_id','int',   $module_id, NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('moduleid', 'int',   $moduleid,  NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemtype', 'int',   $itemtype,  NULL, xarVar::DONT_SET)) {return;}
    // New-style arguments
    if(!xarVar::fetch('itemid',   'int',   $itemid,    NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('name',     'isset', $name,      NULL, xarVar::DONT_SET)) {return;}

    if(!xarVar::fetch('startnum', 'int',   $startnum,  NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('numitems', 'int',   $numitems,  NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('sort',     'isset', $sort,      NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('catid',    'isset', $catid,     NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('layout',   'str:1' ,$layout,    'default', xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('tplmodule','isset', $tplmodule, 'dynamicdata', xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('template', 'isset', $template,  NULL, xarVar::DONT_SET)) {return;}

    // Override if needed from argument array
    extract($args);

    // Support old-style arguments
    if (empty($itemid) && !empty($objectid)) {
        $itemid = $objectid;
    }
    if (empty($module_id) && !empty($moduleid)) {
        $module_id = $moduleid;
    }
    if (empty($module_id)) {
        $module_id = xarMod::getRegID('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // Default number of items per page in user view
    if (empty($numitems)) {
        $numitems = xarModVars::get('dynamicdata', 'items_per_page');
    }

    // Note: we need to pass all relevant arguments ourselves here
    $object = DataObjectMaster::getObjectList(
                            array('objectid'  => $itemid,
                                  'name'      => $name,
                                  'startnum'  => $startnum,
                                  'numitems'  => $numitems,
                                  'sort'      => $sort,
                                  'catid'     => $catid,
                                  'layout'    => $layout,
                                  'tplmodule' => $tplmodule,
                                  'template'  => $template,
                                  ));

    if (!$object->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $object->label));

    // Pass back the relevant variables to the template if necessary
    $data = $object->toArray();

    // Count the number of items matching the preset arguments - do this before getItems()
    $object->countItems();

    // Get the selected items using the preset arguments
    $object->getItems();

    // Pass the object list to the template
    $data['object'] = $object;

    // TODO: is this needed?
    $data = array_merge($data,xarMod::apiFunc('dynamicdata','admin','menu'));
    // TODO: remove this when we turn all the moduleid into module_id
    $data['module_id'] = $data['moduleid'];
    // TODO: another stray
    $data['catid'] = $catid;

    xarTpl::setPageTitle(xarML('View #(1)', $object->label));

    if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view.xt') ||
        file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view-' . $data['template'] . '.xt')) {
        return xarTpl::module($data['tplmodule'],'user','view',$data,$data['template']);
    } else {
        return xarTpl::module('dynamicdata','user','view',$data,$args['template']);
    }
}

?>
