<?php
include("../../../inc/includes.php");

ob_start();

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Session::checkLoginUser();

Html::header("Inventuuri raport", $_SERVER['PHP_SELF'], "tools", "inventeerimine");

// ================= FILTERS =================
$from   = $_GET['from'] ?? date('Y-01-01');
$to     = $_GET['to'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? '';
$export = $_GET['export'] ?? '';
$print  = $_GET['print'] ?? '';

$types = [
    'Computer','Monitor','NetworkEquipment','Peripheral',
    'Phone','Printer','PassiveEquipment','Enclosure','PDU'
];


// ================= DATA FUNCTION =================
function getInventoryRows($types, $from, $to, $type_filter = '') {

    $rows = [];

    $from_ts = strtotime($from . ' 00:00:00');
    $to_ts   = strtotime($to . ' 23:59:59');

    foreach ($types as $type) {

        if (!empty($type_filter) && $type !== $type_filter) continue;
        if (!class_exists($type)) continue;

        $item = new $type();
        $list = $item->find(['is_deleted' => 0]);

        foreach ($list as $id => $data) {

            $infocom = new Infocom();
            if (!$infocom->getFromDBforDevice($type, $id)) continue;

            $date = $infocom->fields['inventory_date'] ?? null;
            if (!$date || $date == '0000-00-00') continue;

            $date_ts = strtotime($date);

            if ($date_ts < $from_ts || $date_ts > $to_ts) continue;

            // kasutaja
            $user = '';
            if (!empty($data['users_id'])) {
                $u = new User();
                if ($u->getFromDB($data['users_id'])) {
                    $user = $u->fields['name'] ?? '';
                }
            }

            // asukoht
            $loc = '';
            if (!empty($data['locations_id'])) {
                $l = new Location();
                if ($l->getFromDB($data['locations_id'])) {
                    $loc = $l->fields['name'] ?? '';
                }
            }

            $rows[] = [
                'type' => $type,
                'name' => $data['name'] ?? '',
                'serial' => $data['serial'] ?? '',
                'user' => $user,
                'location' => $loc,
                'date' => $date
            ];
        }
    }

    return $rows;
}


// ================= CSV =================
if ($export === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventuuri_raport.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tüüp','Nimi','Seeria','Kasutaja','Asukoht','Kuupäev']);

    foreach (getInventoryRows($types, $from, $to, $type_filter) as $r) {
        fputcsv($out, $r);
    }

    fclose($out);
    exit;
}


// ================= XLSX =================
if ($export === 'xlsx') {

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(
        ['Tüüp','Nimi','Seeria','Kasutaja','Asukoht','Kuupäev'],
        null,
        'A1'
    );

    $i = 2;

    foreach (getInventoryRows($types, $from, $to, $type_filter) as $r) {
        $sheet->fromArray(array_values($r), null, "A$i");
        $i++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename=inventuuri_raport.xlsx');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


// ================= PDF =================
if ($export === 'pdf') {

    ob_end_clean();

    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    $rows = getInventoryRows($types, $from, $to, $type_filter);

    $html = "<h2>Inventuuri raport</h2>
    <table border='1' cellpadding='4'>
    <tr>
        <th>Tüüp</th><th>Nimi</th><th>Seeria</th>
        <th>Kasutaja</th><th>Asukoht</th><th>Kuupäev</th>
    </tr>";

    foreach ($rows as $r) {
        $html .= "<tr>
            <td>{$r['type']}</td>
            <td>{$r['name']}</td>
            <td>{$r['serial']}</td>
            <td>{$r['user']}</td>
            <td>{$r['location']}</td>
            <td>{$r['date']}</td>
        </tr>";
    }

    $html .= "</table>";

    $pdf->writeHTML($html);
    $pdf->Output("inventuuri_raport.pdf", "D");
    exit;
}


// ================= PRINT =================
if ($print == 1) {
    echo "<style>
        @media print { .btn { display:none; } }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:5px; }
    </style>";

    echo "<script>window.onload = () => window.print();</script>";
}


// ================= UI =================
echo "<h2>Inventuuri raport</h2>";

echo "<form method='get'>
    Algus: <input type='date' name='from' value='$from'>
    Lõpp: <input type='date' name='to' value='$to'>

    Tüüp: <select name='type'>
        <option value=''>-- Kõik --</option>";

foreach ($types as $t) {
    $sel = ($t == $type_filter) ? "selected" : "";
    echo "<option value='$t' $sel>$t</option>";
}

echo "</select>

    <button class='btn btn-primary'>Filtreeri</button>
</form>";


// 🔥 export lingid koos filtriga
echo "<div class='card mb-3 mt-3'>
    <div class='card-body d-flex justify-content-end'>

        <div class='btn-group'>
            <button type='button' class='btn btn-primary dropdown-toggle' data-bs-toggle='dropdown'>
                Export
            </button>

            <ul class='dropdown-menu dropdown-menu-end'>

                <li>
                    <a class='dropdown-item' 
                       href='?from=$from&to=$to&type=$type_filter&export=csv'>
                       CSV
                    </a>
                </li>

                <li>
                    <a class='dropdown-item' 
                       href='?from=$from&to=$to&type=$type_filter&export=xlsx'>
                       Excel (XLSX)
                    </a>
                </li>

                <li>
                    <a class='dropdown-item' 
                       href='?from=$from&to=$to&type=$type_filter&export=pdf'>
                       PDF
                    </a>
                </li>

                <li><hr class='dropdown-divider'></li>

                <li>
                    <a class='dropdown-item' 
                       href='?from=$from&to=$to&type=$type_filter&print=1' target='_blank'>
                       Print
                    </a>
                </li>

            </ul>
        </div>

    </div>
</div>";

// ================= TABLE =================
echo "<table class='table table-bordered'>
<tr>
    <th>Tüüp</th><th>Nimi</th><th>Seeria</th>
    <th>Kasutaja</th><th>Asukoht</th><th>Kuupäev</th>
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