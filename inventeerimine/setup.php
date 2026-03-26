<?php

define('Inventeerimine', '1.0.0');

include(__DIR__ . '/inc/menu.class.php');
include(__DIR__ . '/inc/reportmenu.class.php');

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

function plugin_init_inventeerimine() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['inventeerimine'] = true;

    Plugin::registerClass('PluginInventeerimineMenu');
    Plugin::registerClass('PluginInventeerimineReportMenu');

    $PLUGIN_HOOKS['menu_toadd']['inventeerimine'] = [
        'tools'      => 'PluginInventeerimineMenu',
        'management' => 'PluginInventeerimineReportMenu'
    ];
}

function plugin_inventeerimine_getRights() {
    return [
        [
            'itemtype' => 'PluginInventeerimineMenu',
            'label'    => 'Inventeerimine',
            'field'    => 'plugin_inventeerimine'
        ]
    ];
}