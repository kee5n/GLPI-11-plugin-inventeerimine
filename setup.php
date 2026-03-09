<?php

define('INVENTEERIMINE_VERSION', '1.0.0');

function plugin_version_inventeerimine() {
    return [
        'name'           => 'Inventeerimine',
        'version'        => '1.0.0',
        'author'         => 'Kevin Laanekivi',
        'license'        => '',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0'
            ]
        ]
    ];
}

function plugin_init_inventeerimine() {

    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['inventeerimine'] = true;

    Plugin::registerClass('PluginInventeerimineMenu');

    $PLUGIN_HOOKS['menu_toadd']['inventeerimine'] = [
        'tools' => 'PluginInventeerimineMenu'
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