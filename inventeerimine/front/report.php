<?php

include("../../../inc/includes.php");

Session::checkLoginUser();

Html::header(
   __('Inventory Report', 'inventeerimine'),
   $_SERVER['PHP_SELF'],
   "tools",
   "inventeerimine"
);

$from   = $_GET['from'] ?? date('Y-01-01');
$to     = $_GET['to'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? '';
$export = $_GET['export'] ?? '';
$print  = $_GET['print'] ?? '';

$types = [
   'Computer',
   'Monitor',
   'NetworkEquipment',
   'Peripheral',
   'Phone',
   'Printer',
   'PassiveEquipment',
   'Enclosure',
   'PDU'
];

function getInventoryRows($types, $from, $to, $type_filter = '') {

   $rows = [];

   $from_ts = strtotime($from . ' 00:00:00');
   $to_ts   = strtotime($to . ' 23:59:59');

   foreach ($types as $type) {

      if (!empty($type_filter) && $type !== $type_filter) {
         continue;
      }

      if (!class_exists($type)) {
         continue;
      }

      $item = new $type();
      $list = $item->find(['is_deleted' => 0]);

      foreach ($list as $id => $data) {

         $infocom = new Infocom();

         if (!$infocom->getFromDBforDevice($type, $id)) {
            continue;
         }

         $date = $infocom->fields['inventory_date'] ?? null;

         if (!$date || $date == '0000-00-00') {
            continue;
         }

         $date_ts = strtotime($date);

         if ($date_ts < $from_ts || $date_ts > $to_ts) {
            continue;
         }

         // User
         $user = '';

         if (!empty($data['users_id'])) {
            $u = new User();

            if ($u->getFromDB($data['users_id'])) {
               $user = $u->fields['name'] ?? '';
            }
         }

         // Location
         $loc = '';

         if (!empty($data['locations_id'])) {
            $l = new Location();

            if ($l->getFromDB($data['locations_id'])) {
               $loc = $l->fields['name'] ?? '';
            }
         }

         $rows[] = [
            'type'     => $type,
            'name'     => $data['name'] ?? '',
            'serial'   => $data['serial'] ?? '',
            'user'     => $user,
            'location' => $loc,
            'date'     => $date
         ];
      }
   }

   return $rows;
}

// ================= HEADER =================

echo "<h2>" . __('Inventory Report', 'inventeerimine') . "</h2>";

// ================= FILTER FORM =================

echo "<form style='margin-bottom: 20px;' method='get'>

   " . __('Start', 'inventeerimine') . ":
   <input type='date' name='from' value='$from'>

   " . __('End', 'inventeerimine') . ":
   <input type='date' name='to' value='$to'>

   " . __('Type', 'inventeerimine') . ":
   <select name='type'>

      <option value=''>" . __('All', 'inventeerimine') . "</option>";

foreach ($types as $t) {

   $sel = ($t == $type_filter) ? "selected" : "";

   echo "<option value='$t' $sel>$t</option>";
}

echo "</select>

   <button class='btn btn-primary'>
      " . __('Filter', 'inventeerimine') . "
   </button>

</form>";

// ================= TABLE =================

echo "<table class='table table-bordered'>

<tr>
   <th>" . __('Type', 'inventeerimine') . "</th>
   <th>" . __('Name', 'inventeerimine') . "</th>
   <th>" . __('Serial', 'inventeerimine') . "</th>
   <th>" . __('User', 'inventeerimine') . "</th>
   <th>" . __('Location', 'inventeerimine') . "</th>
   <th>" . __('Date', 'inventeerimine') . "</th>
</tr>";

foreach (getInventoryRows($types, $from, $to, $type_filter) as $r) {

   echo "<tr>
      <td>{$r['type']}</td>
      <td>{$r['name']}</td>
      <td>{$r['serial']}</td>
      <td>{$r['user']}</td>
      <td>{$r['location']}</td>
      <td>{$r['date']}</td>
   </tr>";
}

echo "</table>";

Html::footer();