<?php

class PluginInventeerimineReportMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Inventory Report', 'inventeerimine');
   }

   static function getMenuContent() {

      return [
         'title' => self::getMenuName(),
         'page'  => Plugin::getWebDir('inventeerimine', false) . '/front/report.php',
         'icon'  => 'fas fa-boxes'
      ];
   }
}