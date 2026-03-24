<?php

define('INVENTEERIMINE_VERSION', '1.0.0');

// Include menüü klassid
include(__DIR__ . '/inc/menu.class.php');
include(__DIR__ . '/inc/reportmenu.class.php');

/**
 * Versioon
 */
function plugin_version_inventeerimine() {
    return [
        'name'         => 'Inventeerimine',
        'version'      => '1.0.0',
        'author'       => 'Kevin Laanekivi',
        'license'      => '',
        'requirements' => [
            'glpi' => ['min' => '11.0']
        ]
    ];
}

/**
 * Init plugina
 */
function plugin_init_inventeerimine() {
    global $PLUGIN_HOOKS;

    // CSRF turvalisus
    $PLUGIN_HOOKS['csrf_compliant']['inventeerimine'] = true;

    // Registreeri klassid
    Plugin::registerClass('PluginInventeerimineMenu');
    Plugin::registerClass('PluginInventeerimineReportMenu');

    // Menüü hookid
    $PLUGIN_HOOKS['menu_toadd']['inventeerimine'] = [
        'tools'      => 'PluginInventeerimineMenu',
        'management' => 'PluginInventeerimineReportMenu'
    ];
}

/**
 * Õigused plugina jaoks
 */
function plugin_inventeerimine_getRights() {
    return [
        [
            'itemtype' => 'PluginInventeerimineMenu',
            'label'    => 'Inventeerimine',
            'field'    => 'plugin_inventeerimine'
        ]
    ];
}