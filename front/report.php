<?php
include("../../../inc/includes.php");
Session::checkLoginUser();

Html::header("Inventeeritud varad raport", $_SERVER['PHP_SELF'], "management", "report");

// Filtri väärtused
$start_date_str = $_GET['start_date'] ?? date("Y-01-01");
$end_date_str   = $_GET['end_date'] ?? date("Y-m-d");
$type_filter    = $_GET['type'] ?? '';

$start_date = strtotime($start_date_str);
$end_date   = strtotime($end_date_str);

// Asset tüübid
$asset_types = [
    'Computer',
    'Monitor',
    'NetworkEquipment',
    'Peripheral',
    'Phone',
    'Printer',
    'SoftwareLicense',
    'PassiveEquipment',
    'Enclosure',
    'PDU'
];


// ===== FILTER FORM =====
echo "<form method='get' class='mb-4'>";

echo "<label>Alguskuupäev:</label> ";
echo "<input type='date' name='start_date' value='".htmlspecialchars($start_date_str)."'> ";

echo "<label>Lõppkuupäev:</label> ";
echo "<input type='date' name='end_date' value='".htmlspecialchars($end_date_str)."'> ";

echo "<label>Tüüp:</label> ";
echo "<select name='type'>";
echo "<option value=''>-- Kõik --</option>";

foreach ($asset_types as $type_option) {
    $selected = ($type_filter === $type_option) ? "selected" : "";
    echo "<option value='$type_option' $selected>$type_option</option>";
}

echo "</select> ";

echo "<button type='submit' class='btn btn-primary'>Filtreeri</button>";
echo "</form>";


// ===== DATA =====
$notepad = new Notepad();

$notes = $notepad->find([
    'content' => ['LIKE', '%Inventuur teostatud:%']
]);

$latest = [];

foreach ($notes as $id => $note) {

    // Tüübi filter
    if (!empty($type_filter) && $note['itemtype'] !== $type_filter) {
        continue;
    }

    // Parsime kuupäeva
    if (preg_match('/Inventuur teostatud: (\d{2}\.\d{2}\.\d{4})/', $note['content'], $matches)) {

        $note_date = strtotime(str_replace('.', '-', $matches[1]));

        // Kuupäeva filter
        if ($note_date < $start_date || $note_date > $end_date) {
            continue;
        }

        $key = $note['itemtype'] . '_' . $note['items_id'];

        // Võtame ainult viimase
        if (!isset($latest[$key]) || $note_date > $latest[$key]['note_date']) {
            $latest[$key] = [
                'note' => $note,
                'note_date' => $note_date
            ];
        }
    }
}


// ===== TABLE =====
echo "<table class='table table-striped'>";
echo "<thead><tr>
        <th>Vara nimi</th>
        <th>Vara tüüp</th>
        <th>Inv nr</th>
        <th>Inventeerimise kuupäev</th>
      </tr></thead>";
echo "<tbody>";

foreach ($latest as $data) {

    $note = $data['note'];
    $date = date('d.m.Y', $data['note_date']);

    if (class_exists($note['itemtype'])) {

        $item = new $note['itemtype']();
        $item->getFromDB($note['items_id']);

        $name = htmlspecialchars($item->fields['name'] ?? '');
        $inv_code = htmlspecialchars($item->fields['otherserial'] ?? '');
        $type_label = $item->getTypeName(1);

        echo "<tr>
            <td>$name</td>
            <td>$type_label</td>
            <td>$inv_code</td>
            <td>$date</td>
        </tr>";
    }
}

echo "</tbody></table>";

Html::footer();