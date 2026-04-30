<?php
include("../../../inc/includes.php");

Session::checkLoginUser();

$plugin_url = "/plugins/inventeerimine/front/search.php";


// ================= HELPER =================
function setInventoryDate($itemtype, $items_id, $date) {

    $infocom = new Infocom();

    if ($infocom->getFromDBforDevice($itemtype, $items_id)) {

        $infocom->update([
            'id' => $infocom->getID(),
            'inventory_date' => $date
        ]);

    } else {

        $infocom->add([
            'itemtype'       => $itemtype,
            'items_id'       => $items_id,
            'inventory_date' => $date
        ]);
    }
}


// ================= SAVE =================
if (isset($_POST['save_all']) && isset($_POST['update_id'])) {

    $id       = (int)$_POST['update_id'];
    $itemtype = $_POST['update_type'];

    if (class_exists($itemtype)) {

        $item = new $itemtype();

        if ($item->getFromDB($id)) {

            $input = [
                'id'           => $id,
                'name'         => $_POST['update_name'] ?? '',
                'serial'       => $_POST['update_serial'] ?? '',
                'users_id'     => $_POST['users_id'] ?? 0,
                'otherserial'  => $_POST['update_otherserial'] ?? '',
                'locations_id' => $_POST['new_location_id'] ?? 0,
                'states_id'    => $_POST['states_id'] ?? 0
            ];

            $item->update($input);

            // AUTOMAATNE INVENTUURI KUUPÄEV
            setInventoryDate($itemtype, $id, date("Y-m-d"));

            $notepad = new Notepad();

            $notepad->add([
                'itemtype' => $itemtype,
                'items_id' => $id,
                'users_id' => Session::getLoginUserID(),
                'content'  => "Inventuur teostatud: " . date("d.m.Y")
            ]);

            Html::redirect($plugin_url . "?success=1");
            exit;
        }
    }
}


// ================= ADD NEW =================
if (isset($_POST['add_new']) && !empty($_POST['new_itemtype'])) {

    $itemtype = $_POST['new_itemtype'];

    if (class_exists($itemtype)) {

        $item = new $itemtype();

        $input = [
            'name'         => $_POST['new_name'] ?? '',
            'serial'       => $_POST['new_serial'] ?? '',
            'otherserial'  => $_POST['new_otherserial'] ?? '',
            'locations_id' => $_POST['new_location_id'] ?? 0,
            'users_id'     => $_POST['users_id'] ?? 0,
            'states_id'    => $_POST['states_id'] ?? 0,
        ];

        $newID = $item->add($input);

        if ($newID) {
            setInventoryDate($itemtype, $newID, date("Y-m-d"));

            $notepad = new Notepad();
            
            $notepad->add([
                'itemtype' => $itemtype,
                'items_id' => $newID,
                'users_id' => Session::getLoginUserID(),
                'content'  => "Uus inventeeritud vara lisatud: " . date("d.m.Y")
            ]);
        }

        Html::redirect($plugin_url . "?success=1");
        exit;
    }
}


// ================= UI =================
Html::header(__("Inventeerimine"), $plugin_url, "tools", "inventeerimine");

if (isset($_GET['success'])) {
    echo "<div class='alert alert-success text-center'>
            ✓ Vara ja inventuuri kuupäev salvestatud!
          </div>";
}

echo "<div class='center'>";
echo "<h2>Vara inventeerimine</h2>";

$asset_code = htmlspecialchars($_GET['asset_code'] ?? '');

echo "<form action='$plugin_url' method='get' class='mb-4'>";
echo "<input type='text' autofocus name='asset_code'
             placeholder='Sisesta kood'
             class='form-control'
             style='width:400px; display:inline-block;'
             required
             value='$asset_code'> ";
echo "<button type='submit' class='btn btn-primary'>Otsi</button>";
echo "</form>";

if (!empty($asset_code)) {

    $code = trim($asset_code);
    $found_any = false;

    $asset_types = [
        'Computer','Monitor','NetworkEquipment','Peripheral',
        'Phone','Printer','SoftwareLicense','PassiveEquipment',
        'Enclosure','PDU'
    ];

    echo "<div style='max-width:1200px;margin:0 auto;'>";

    foreach ($asset_types as $itemtype) {

        if (!class_exists($itemtype)) continue;

        $item = new $itemtype();

        $results = $item->find([
            'OR' => [
                'otherserial' => ['LIKE', "%$code%"],
                'serial'      => ['LIKE', "%$code%"],
                'name'        => ['LIKE', "%$code%"]
            ],
            'is_deleted' => 0
        ]);

        foreach ($results as $id => $data) {

            $found_any = true;
            $typeName = $item->getTypeName(1);

            echo "<div class='card shadow-sm mb-4'><div class='card-body'>";
            echo "<form method='post' action='$plugin_url'>";
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . Session::getNewCSRFToken() . '">';

            echo "<input type='hidden' name='update_id' value='$id'>";
            echo "<input type='hidden' name='update_type' value='$itemtype'>";

            echo "<div class='row g-4 align-items-end'>";

            echo "<div class='col-md-3'>
                    <label>Nimi</label>
                    <input type='text' name='update_name' class='form-control'
                    value='".htmlspecialchars($data['name'])."'>
                  </div>";

            echo "<div class='col-md-2'>
                    <label>Tüüp</label>
                    <div class='border p-2'>$typeName</div>
                  </div>";

            echo "<div class='col-md-2'><label>Staatus</label>";
            State::dropdown([
                'name'=>'states_id',
                'value'=>$data['states_id'],
                'entity'=>$data['entities_id']
            ]);
            echo "</div>";

            echo "<div class='col-md-2'>
                    <label>Seeria</label>
                    <input type='text' name='update_serial' class='form-control'
                    value='".htmlspecialchars($data['serial'])."'>
                  </div>";

            echo "<div class='col-md-3'><label>Kasutaja</label>";
            User::dropdown([
                'name'=>'users_id',
                'value'=>$data['users_id'],
                'entity'=>$data['entities_id']
            ]);
            echo "</div>";

            echo "<div class='col-md-2'>
                    <label>Inv nr</label>
                    <input type='text' name='update_otherserial' class='form-control'
                    value='".htmlspecialchars($data['otherserial'])."'>
                  </div>";

            echo "<div class='col-md-4'><label>Asukoht</label>";
            Dropdown::show('Location', [
                'name'=>'new_location_id',
                'value'=>$data['locations_id'],
                'width'=>'100%'
            ]);
            echo "</div>";

            echo "<div class='col-md-2'>
                    <button type='submit' name='save_all' class='btn btn-success'>
                    Inventeeri
                    </button>
                  </div>";

            echo "</div></form></div></div>";
        }
    }

if (!$found_any) {
        echo "<div class='alert alert-warning'>Ei leitud midagi koodiga ".htmlspecialchars($code)."</div>";

        echo "<div class='card shadow-sm mb-4'>";
        echo "<div class='card-body'>";
        echo "<h4>Lisa uus vara</h4>";
        echo "<form method='post' action='$plugin_url'>";

        echo '<input type="hidden" name="_glpi_csrf_token" value="' . Session::getNewCSRFToken() . '">';

        // Vara nimi
        echo "<div class='row g-4 align-items-end'>";
        echo "<div class='col-md-3'>
            <label>Nimi</label>
            <input type='text' name='new_name' class='form-control' required>
            </div>";

        // Vara tüüp
        echo "<div class='col-md-2'>
            <label>Tüüp</label>
            <select name='new_itemtype' class='form-control' required>";
            foreach ($asset_types as $type) {
                echo "<option value='$type'>$type</option>";
            }
        echo "</select></div>";

        // Seeria nr
        echo "<div class='col-md-2'>
            <label>Seeria</label>
            <input type='text' name='new_serial' class='form-control'>
            </div>";

        // INV nr
        echo "<div class='col-md-2'>
            <label>Inv nr</label>
            <input type='text' name='new_otherserial' class='form-control' value='".htmlspecialchars($code)."'>
            </div>";
            
        // Kasutaja
        echo "<div class='col-md-3'>
            <label>Kasutaja</label>"
            ; User::dropdown(['name'=>'users_id','value'=>0]);
        echo "</div>";

        // Asukoht
        echo "<div class='col-md-4'>
            <label>Asukoht</label>"
            ; Dropdown::show('Location',['name'=>'new_location_id','value'=>0,'width'=>'100%']);
        echo "</div>";

        // Nupp
        echo "<div class='col-md-2'>
            <button type='submit' name='add_new' class='btn btn-success'>Lisa vara</button>
            </div>";

        echo "</div>";

        echo "</form>";
        echo "</div>";
        echo "</div>";
    }

        echo "</div>";
    }

echo "</div>";

Html::footer();