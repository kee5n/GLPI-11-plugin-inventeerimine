<?php

class PluginMypluginsMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Inventeerimine', 'myplugins');
   }

   static function getMenuContent() {

      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/inventeerimine/front/search.php';

      return $menu;
   }
}