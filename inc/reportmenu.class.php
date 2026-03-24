<?php

class PluginInventeerimineReportMenu extends CommonGLPI {

   static function getMenuName() {
      return 'Inventuuri raport';
   }

   static function getMenuContent() {
      return [
         'title' => self::getMenuName(),
         'page'  => '/plugins/inventeerimine/front/report.php',
         'icon'  => 'fas fa-boxes'
      ];
   }
}