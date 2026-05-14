<?php

class PluginInventeerimineMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Inventory', 'inventeerimine');
   }

   static function getMenuContent() {

      return [
         'title' => self::getMenuName(),
         'page'  => Plugin::getWebDir('inventeerimine', false) . '/front/search.php',
         'icon'  => 'fas fa-boxes'
      ];
   }
}