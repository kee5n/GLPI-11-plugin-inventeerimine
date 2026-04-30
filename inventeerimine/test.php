<?php
include ('../../inc/includes.php');

use Glpi\Models\Computer; // Kui GLPI 11+ ja mudelid

// $id on objekti ID
$id = 5;

// Loo objekt (nt Computer)
$item = new Computer();

// Lae andmed andmebaasist
$item->getFromDB($id);

// Näita kõikide väljade nimesid
echo "<pre>";
print_r(array_keys($item->fields));
echo "</pre>";

exit;