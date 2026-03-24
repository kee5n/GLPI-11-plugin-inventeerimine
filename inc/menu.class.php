<?php

class PluginInventeerimineMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Inventeerimine', 'inventeerimine');
   }

   static function getMenuContent() {
      return [
         'title' => self::getMenuName(),
         'page'  => '/plugins/inventeerimine/front/search.php',
         'icon'  => 'fas fa-search'
      ];
   }
}