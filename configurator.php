<?php
/**
 * configurator.php
 * Handles product configurators (form select & color choices)
 *
 */
/*
require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");
*/

require_once "functions.php";
require_once "colors.php";
$id = "";

$pageId = $_POST["pageId"] ?? die("There was an error accessing this page...");

/**
 * The main product pages are under "All Products", however these are replicated
 * in the sections, Linear, Downlights etc. which are handled with Symlinks.
 * We need always to get the primary page object.
 *
 */

$page = $modx->getObject("modResource", $pageId);

if ($page->get("class_key") == "modSymLink") {
    $id = $page->get("content");
    $page = $modx->getObject("modResource", $id);
}

/**
 * Get the LED Board Data. 120657 is the Modx resource ID for the
 * products category where all the output data for the boards are stored
 * This feeds into the function "select_box()" as a global variable and in this
 * script is only used to give some Lumens/Metre data in the Product Configurator
 * Output dropdown.
 *
 */

$board = $modx->getObject("modResource", 120657);
$boards = $board->getTVValue("ledboards");
$boards = json_decode($boards, true);

/**
 * Each product group is defined by it's own Modx template. Linears, Downlight,
 * Quadrants etc. Here we are just pulling the correct set of drop downs for
 * the relevant product group, and putting them in an array called $selectors[]
 *
 *
 */

$template = $page->get("template");

$typeexclusions = [];
$fixtureTypes = [];
$typekey = $_POST["typekey"];

if ($template == 83) {
    //Cloud Only
    $fixtureTypes = $page->getTVValue("fixtureTypesCloud");
} elseif ($template == 75) {
    $fixtureTypes = $page->getTVValue("fixtureTypesChannel");
} elseif ($template == 54) {
    $fixtureTypes = $page->getTVValue("fixtureTypesDownlight");
} else {
    $fixtureTypes = $page->getTVValue("fixtureTypes");
}
$fixtureTypes = json_decode($fixtureTypes, true);

if (isset($fixtureTypes[$typekey]["dlor"])) {
    $dlor = $fixtureTypes[$typekey]["dlor"];
} else {
    $dlor = 0;
}

if (isset($fixtureTypes[$typekey]["ulor"])) {
    $ulor = $fixtureTypes[$typekey]["ulor"];
} else {
    $ulor = 0;
}

$selectors = [];

if ($page->getTVValue("skipColors") == 1) {
    $sql = "SELECT b.name FROM modx_site_tmplvar_templates AS a,modx_site_tmplvars AS b WHERE b.id=a.tmplvarid AND a.templateid='.$template.' AND b.category='61' ORDER BY b.rank ASC";
} else {
    $sql = "SELECT b.name FROM modx_site_tmplvar_templates AS a,modx_site_tmplvars AS b WHERE b.id=a.tmplvarid AND a.templateid='.$template.' AND b.category='61' AND b.name!='fixtureFinishes' ORDER BY b.rank ASC";
}

$x = 0;
foreach ($modx->query($sql) as $row) {
    $nn = select_box($row[0], $x, [], 1, $template);
    $nn = $nn[2];
    if ($nn >= 1) {
        $selectors[] = $row[0];
    }
    $x++;
}

/**
 * Sometimes we want to exclude certain options from the Product Configurator
 * dropdowns based on another selection which is made. For example a surface mount
 * luminaire won't have a direct distribution option. This is handled via comma
 * separated lists. In the Colour Configurator tab this is the TV "fixtureTypes".
 */

if (isset($fixtureTypes[$typekey]["exclusions"])) {
    $typeexclusions = $fixtureTypes[$typekey]["exclusions"];
    # Clean up comma separated list, remove spaces etc.
    $typeexclusions = preg_replace("/\s*,\s*/", ",", $typeexclusions);
    $typeexclusions = explode(",", $typeexclusions);
}

/**
 * SpotLights are a special case because some fixtures like the TIN CAN may
 * use a different type of spot depending on Output, so therefore we need to make
 * exclusions in the LED Spot master section listed with the
 * LED Board Data in Resource ID 120657. This merges two CSV lists from
 * LED Spots, $exclusionsparent and $exclusions2. The combined array is merged
 * below with the $typeexclsuions array to and the further still with the
 * exclusions from the Colour Configurator exclude[]
 */

$outputBeams = $page->getTVValue("outputBeam");
$outputBeams = json_decode($outputBeams, true);
$spotType = "";
$outputBeam = "";
if (isset($_POST["outputBeam"])) {
    $outputBeam = $_POST["outputBeam"];
}

if (isset($outputBeams)) {
    foreach ($outputBeams as $v) {
        if ($v["outputBeam"] == $outputBeam) {
            $spotType = $v["spotSelection"];
            break;
        }
    }
}
$ledSpots = $board->getTVValue("ledSpots");
$ledSpots = json_decode($ledSpots, true);
$exclusionsparent = "";
$exclusions2 = "";
foreach ($ledSpots as $spot) {
    if ($spot["boardtype"] == $spotType) {
        $boardoutputs = $spot["boardoutputs"];

        $boardoutputs = json_decode($boardoutputs, true);

        foreach ($boardoutputs as $board) {
            $outputBeam = "";
            if (isset($_POST["outputBeam"])) {
                $outputBeam = $_POST["outputBeam"];
            }

            $spotBeamSpread = "";
            if (isset($_POST["spotBeamSpread"])) {
                $spotBeamSpread = $_POST["spotBeamSpread"];
            }

            if ($board["output"] == $outputBeam . "-" . $spotBeamSpread) {
                $exclusionsparent = $board["exclusions"];
                $ccts = $board["cct"];
                $ccts = json_decode($ccts, true);

                foreach ($ccts as $cct) {
                    $fixtureCCT = "";
                    if (isset($_POST["fixtureCCT"])) {
                        $fixtureCCT = $_POST["fixtureCCT"];
                    }
                    if ($cct["cct"] == $fixtureCCT) {
                        $exclusions2 = $cct["exclusions"];

                        break;
                    }
                }

                break;
            }
        }
        break;
    }
}

$exclusionsparent = explode(",", $exclusionsparent);
$exclusions2 = explode(",", $exclusions2);
$exclusionsparent = array_merge($exclusionsparent, $exclusions2);
$typeexclusions = array_merge($typeexclusions, $exclusionsparent);

/**
 * if type exclusions has no value, return an empty array (avoids weird behaviour)
 */

foreach ($typeexclusions as $key => $value) {
    if (empty($value)) {
        unset($typeexclusions[$key]);
    }
}

$x = 0;
$countselected = 0;
$exclude = [];

foreach ($selectors as $val) {
    if ($x > $countselected) {
        $enabled = 0;
    } else {
        $enabled = 1;
    }
    $select = select_box(
        $val,
        $x,
        array_merge($typeexclusions, $exclude),
        $enabled,
        $template
    );
    $selects[] = $select[0];

    $fix = $page->getTVValue($val);
    $fix = json_decode($fix, true);
    foreach ($fix as $v) {
        if (isset($_POST[$val])) {
            if ($v[$val] == $_POST[$val]) {
                if (isset($v["exclusions"])) {
                    if ($v["exclusions"]) {
                        $excludes = preg_replace(
                            "/\s*,\s*/",
                            ",",
                            $v["exclusions"]
                        );
                        $excludes = explode(",", $excludes);
                        # if excludes has no value, return an empty array
                        foreach ($excludes as $key => $value) {
                            if (empty($value)) {
                                unset($excludes[$key]);
                            }
                        }
                        /**
                         * Create an array of excluded values form the comma separated values in the
                         * Product Configurator and above this is merged with the TV "fixtureTypes"
                         * from the Colour Configurator to create a combine array of exclusions
                         */
                        foreach ($excludes as $exc) {
                            $exclude[] = $exc;
                        }
                    }
                }
            }
        }
    }

    if ($select[1] == 1) {
        $countselected++;
    }

    $x++;
}

/**
 * Output the configurator dropdowns
 */

$outputselect = "";
foreach ($selects as $select) {
    $outputselect .= $select;
}

$j = 0;

$data["countselects"] = count($selects); # How many select boxes in total
if (
    $page->getTVValue("configuratorEnabled") == 0 ||
    $page->get("template") == 77
) {
    $cfgHeading =
        '<div class="number-indicator">3</div><div style="font-size:2rem;"><strong>Step 3</strong><br>Download data sheet</div>';
} else {
    if ($page->getTVValue("skipColors") == 1) {
        $cfgHeading =
            '<div class="number-indicator">1</div><div style="font-size:2rem;"><strong>Step 2</strong><br>Choose specification<div style="font-size:1.6rem; padding-top:0.7rem">(Select all fields in turn)</div></div>';
    } else {
        $cfgHeading =
            '<div class="number-indicator">3</div><div style="font-size:2rem;"><strong>Step 3</strong><br>Choose specification<div style="font-size:1.6rem; padding-top:0.7rem">(Select all fields in turn)</div></div>';
    }
}

$cls_1 = $_POST["cls-1"] ?? "";
$cls_2 = $_POST["cls-2"] ?? "";
$cls_3 = $_POST["cls-3"] ?? "";
$cls_4 = $_POST["cls-4"] ?? "";
$cls_5 = $_POST["cls-5"] ?? "";
$cls_6 = $_POST["cls-6"] ?? "";
$cls_7 = $_POST["cls-7"] ?? "";
$cls_8 = $_POST["cls-8"] ?? "";
$cls_9 = $_POST["cls-9"] ?? "";
$cls_10 = $_POST["cls-10"] ?? "";

$color_1 = $_POST["color-1"] ?? "";
$color_2 = $_POST["color-2"] ?? "";
$color_3 = $_POST["color-3"] ?? "";
$color_4 = $_POST["color-4"] ?? "";
$color_5 = $_POST["color-5"] ?? "";
$color_6 = $_POST["color-6"] ?? "";
$color_7 = $_POST["color-7"] ?? "";
$color_8 = $_POST["color-8"] ?? "";
$color_9 = $_POST["color-9"] ?? "";
$color_10 = $_POST["color-10"] ?? "";

$priceband_1 = $_POST["priceband-1"] ?? "";

if ($_POST["emergency"] == "Emergency") {
    $checked_emergency = "checked";
}

if ($_POST["pir"] == "PIR") {
    $checked_pir = "checked";
}

if ($_POST["casambi"] == "Casambi") {
    $checked_casambi = "checked";
}

if ($_POST["pricing"] == "Pricing") {
    $checked_pricing = "checked";
}

if ($_POST["costMultiplier"]) {
    $multVal = $_POST["costMultiplier"];
} else {
    $multVal = 2.0;
}

if ($page->getTVValue("trackLengthEnabled") == 1) {
    $trackLengthField =
        ' <div class="col-12 pt-3 pb-3"><div style="font-size:2rem;"><strong>Track Requirements</strong> - (If known)</div><div>Visit <a href="https://www.powergear.eu" target="_blank" style="font-weight:bold; text-decoration:underline;">powergear.eu</a> for available track options.</div></div>';
    $trackLengthField .=
        '<div class="col-12 pt-3 pb-3"><input type="text" class="form-control" name="trackLength" value="' .
        $_POST["trackLength"] .
        '"  style="max-width:300px" placeholder="eg. 5m x 3m Rectangle"></div>';
}

if (
    $page->getTVValue("configuratorEnabled") == 0 ||
    $page->get("template") == 77
) {
    

    $data["configurator"] =
        $cfgHeading .
        '
<form id="filterpanel" action="" method="post">

<input type="hidden" id="cls-1" name="cls-1" value="' .
        $cls_1 .
        '">
<input type="hidden" id="cls-2" name="cls-2" value="' .
        $cls_2 .
        '">
<input type="hidden" id="cls-3" name="cls-3" value="' .
        $cls_3 .
        '">
<input type="hidden" id="cls-4" name="cls-4" value="' .
        $cls_4 .
        '">
<input type="hidden" id="cls-5" name="cls-5" value="' .
        $cls_5 .
        '">
<input type="hidden" id="cls-6" name="cls-6" value="' .
        $cls_6 .
        '">
<input type="hidden" id="cls-7" name="cls-7" value="' .
        $cls_7 .
        '">
<input type="hidden" id="cls-8" name="cls-8" value="' .
        $cls_8 .
        '">
<input type="hidden" id="cls-9" name="cls-9" value="' .
        $cls_9 .
        '">
<input type="hidden" id="cls-10" name="cls-10" value="' .
        $cls_10 .
        '">
<input type="hidden" id="color-1" name="color-1" value="' .
        $color_1 .
        '">
<input type="hidden" id="color-2" name="color-2" value="' .
        $color_2 .
        '">
<input type="hidden" id="color-3" name="color-3" value="' .
        $color_3 .
        '">
<input type="hidden" id="color-4" name="color-4" value="' .
        $color_4 .
        '">
<input type="hidden" id="color-5" name="color-5" value="' .
        $color_5 .
        '">
<input type="hidden" id="color-6" name="color-6" value="' .
        $color_6 .
        '">
<input type="hidden" id="color-7" name="color-7" value="' .
        $color_7 .
        '">
<input type="hidden" id="color-8" name="color-8" value="' .
        $color_8 .
        '">
<input type="hidden" id="color-9" name="color-9" value="' .
        $color_9 .
        '">
<input type="hidden" id="color-10" name="color-10" value="' .
        $color_10 .
        '">
 <input type="hidden" id="priceband-1" name="priceband-1" value="' .
        $priceband_1 .
        '">       
<input type="hidden" id="typekey" name="typekey" value="' .
        $typekey .
        '">
<input type="hidden" id="pageid" name="pageId" value="' .
        $_POST["pageId"] .
        '">
<input type="hidden" name="projects" value="Project 123">

</form>
';

} else {
    if (
        $modx->user->isMember("Pricing") &&
        $page->getTVValue("pricingEnabled") == 1
    ) {
        $pricing =
            '
    
 <div class="row">
<div class="col-12 pt-3 pb-3"><div style="font-size:2rem;"><strong>Pricing</strong> - (Optional)</div></div>
<div class="col-md-12 col-lg-4">
<label class="checkbox-container">Enable Pricing
  <input id="pricing" name="pricing" type="checkbox" value="Pricing" ' .
            $checked_pricing .
            '>
  <span class="checkmark"></span>
</label>
<label for="multiplier">
Cost Price Multiplier
<input id="multiplier" name="costMultiplier" type="text" value="' .
            $multVal .
            '" class="form-control" style="font-size:2rem; border-radius:0; padding:0.5rem">
</label>
</div>



 </div>
 

    
    ';
    }

    $stmt = $modx->prepare(
        "SELECT name FROM modx_lightforms_projects ORDER BY name ASC"
    );

    $results = [];

    if ($stmt->execute()) {
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($results as $row) {
        $options .=
            "<option>" .
            $row["name"] .
            '</option>
';
    }
    if (
        $countselected == count($selects) &&
        $modx->user->isMember("Pricing") &&
        $page->getTVValue("pricingEnabled") == 1
    ) {
        $costField =
            '
  <div class="center ds-button" style="margin-top:15px; margin-bottom:20px;"><a href="#" id="cfg-submit" class="button">Data Sheet & LDT</a></div>
  <div class="pt-3 pb-3"><div style="font-size:2rem;"><strong>Cost Multiplier</strong> - (Price Admin Only)</div></div>
  <div class="form-group">
   
   <input type="number" name="costMultiplier" value="2.00" autocomplete="off" step="0.10" class="form-control" id="costMultiplier" style="font-size:1.8rem; width:100px; text-align:center; margin-bottom:10px;">
   
      <select name="projects" class="selectpicker" data-style="price-filter" data-width="300px" style="margin-bottom:30px;">
                     <option value="">Add to Project (optional)</option>
            ' .
            $options .
            '
                  </select>
                  <div><a href="#" id="price-submit" class="pricebutton">Calculate Price (&pound;)</a></div>
  </div>';
    }

    $data["configurator"] =
        $cfgHeading .
        '
<form id="filterpanel" action="" method="post">

 <div class="row">
 ' .
        $outputselect .
        $trackLengthField .
        '
        
<div class="col-12 pt-3 pb-3"><div style="font-size:2rem;"><strong>Controls</strong> - (Optional)</div></div>
<div class="col-md-12 col-lg-4">
<label class="checkbox-container">Emergency
  <input id="emergency" name="emergency" type="checkbox" value="Emergency" ' .
        $checked_emergency .
        '>
  <span class="checkmark"></span>
</label>
</div>

<div class="col-md-12 col-lg-4">
<label class="checkbox-container">PIR
  <input id="pir" name="pir" type="checkbox" value="PIR" ' .
        $checked_pir .
        '>
  <span class="checkmark"></span>
</label>
</div>

<div class="col-md-12 col-lg-4">

<label class="checkbox-container">Casambi
  <input id="casambi" name="casambi" type="checkbox" value="Casambi" ' .
        $checked_casambi .
        '>
  <span class="checkmark"></span>
</label>
</div>

 </div>

'.$costField.'


 
<input type="hidden" id="cls-1" name="cls-1" value="' .
        $cls_1 .
        '">
<input type="hidden" id="cls-2" name="cls-2" value="' .
        $cls_2 .
        '">
<input type="hidden" id="cls-3" name="cls-3" value="' .
        $cls_3 .
        '">
<input type="hidden" id="cls-4" name="cls-4" value="' .
        $cls_4 .
        '">
<input type="hidden" id="cls-5" name="cls-5" value="' .
        $cls_5 .
        '">
<input type="hidden" id="cls-6" name="cls-6" value="' .
        $cls_6 .
        '">
<input type="hidden" id="cls-7" name="cls-7" value="' .
        $cls_7 .
        '">
<input type="hidden" id="cls-8" name="cls-8" value="' .
        $cls_8 .
        '">
<input type="hidden" id="cls-9" name="cls-9" value="' .
        $cls_9 .
        '">
<input type="hidden" id="cls-10" name="cls-10" value="' .
        $cls_10 .
        '">
<input type="hidden" id="color-1" name="color-1" value="' .
        $color_1 .
        '">
<input type="hidden" id="color-2" name="color-2" value="' .
        $color_2 .
        '">
<input type="hidden" id="color-3" name="color-3" value="' .
        $color_3 .
        '">
<input type="hidden" id="color-4" name="color-4" value="' .
        $color_4 .
        '">
<input type="hidden" id="color-5" name="color-5" value="' .
        $color_5 .
        '">
<input type="hidden" id="color-6" name="color-6" value="' .
        $color_6 .
        '">
<input type="hidden" id="color-7" name="color-7" value="' .
        $color_7 .
        '">
<input type="hidden" id="color-8" name="color-8" value="' .
        $color_8 .
        '">
<input type="hidden" id="color-9" name="color-9" value="' .
        $color_9 .
        '">
<input type="hidden" id="color-10" name="color-10" value="' .
        $color_10 .
        '">
   <input type="hidden" id="priceband-1" name="priceband-1" value="' .
        $priceband_1 .
        '">        
<input type="hidden" id="typekey" name="typekey" value="' .
        $typekey .
        '">
<input type="hidden" id="pageid" name="pageId" value="' .
        $_POST["pageId"] .
        '">


</form>
';
}
#END
# Start Color Stuff

$imagepath = "/ws/configurator/products/";
$iconpath = "/ws/configurator/icons/";

$pagetitle = $page->get("pagetitle");
$configuratorEnabled = $page->getTVValue("configuratorEnabled");

$defaultFixture = [];
if (isset($fixtureTypes[0]["title"])) {
    $defaultFixture = $fixtureTypes[0]["title"];
}
$svgclose = "</svg>";

if (isset($fixtureTypes[$_POST["typekey"]]["image"])) {
    $startImage =
        '<img class="image-type" id="background-image" src="' .
        $imagepath .
        $fixtureTypes[$_POST["typekey"]]["image"] .
        '" alt="" >';
    $startSrc = $fixtureTypes[0]["image"];
}
$numPathsForMounting = "";
if (isset($fixtureTypes[$_POST["typekey"]]["buttonValue"])) {
    if (
        is_countable(
            json_decode($fixtureTypes[$_POST["typekey"]]["buttonValue"], true)
        )
    ) {
        $numPathsForMounting = count(
            json_decode($fixtureTypes[$_POST["typekey"]]["buttonValue"], true)
        );
    }
}
$btnMounting = "";
$active = "";
$checked = "";
$selected = "";
//if (is_countable($fixtureTypes) && count($fixtureTypes) > 1) {
$y = 0;

if ($page->getTVValue("mountingOptions") == 1) {
    $btnMounting .=
        '<select name="buttontype1" class="select-type selectpicker form-control" data-style="select-mounting" data-width="75%">';
}
foreach ($fixtureTypes as $key => $value) {
    if ($key == $_POST["typekey"]) {
        $active = "active";
        $checked = "checked";
    }

    if ($page->getTVValue("mountingOptions") == 1) {
        if ($key == $_POST["typekey"]) {
            $selected = "selected";
        }

        $btnMounting .=
            '<option value="' .
            $value["title"] .
            '"
          
          data-key="' .
            $key .
            '"  data-src="' .
            $value["image"] .
            '" data-image="' .
            htmlentities(
                '<img class="image-type" id="background-image" style="background:#dfdfdf"  src="' .
                    $imagepath .
                    $value["image"] .
                    '" " alt="" >'
            ) .
            '"
          
          ' .
            $selected .
            ">" .
            $value["title"] .
            "</option>";
        unset($selected);
    } else {
        $btnMounting .=
            '
        <label class="button-type" data-key="' .
            $key .
            '" data-tooltip="' .
            $value["title"] .
            '"  data-src="' .
            $value["image"] .
            '" data-image="' .
            htmlentities(
                '<img class="image-type" id="background-image" style="background:#dfdfdf"  src="' .
                    $imagepath .
                    $value["image"] .
                    '" " alt="" >'
            ) .
            '">
    <input type="radio" name="buttontype" value="' .
            $value["title"] .
            "," .
            $value["image"] .
            '" ' .
            $checked .
            '>
    <img src="' .
            $iconpath .
            $value["icon"] .
            '" style="' .
            $active .
            '" class="mounting-image ' .
            $active .
            '"  alt="" >
    </label>
';
    }
    $active = "";
    $checked = "";

    $y++;
}

if ($page->getTVValue("mountingOptions") == 1) {
    $btnMounting .= "</select>";
}
//}
$original = "";
if (isset($fixtureTypes[$_POST["typekey"]]["buttonValue"])) {
    $product_parts = json_decode(
        $fixtureTypes[$_POST["typekey"]]["buttonValue"],

        true
    );
    $original = $product_parts[0]["buttonPath"];
}

$m = 0;
$c = 1;
$r1 = [];
$svgparts = "";
if (isset($product_parts)) {
    foreach ($product_parts as $product_part) {
        $svgparts .= '<input type="hidden" name="part[]" value="' . $m . '">';
        $data_path[] = $product_part["buttonPath"];

        if (isset($product_part["trackChart"])) {
            $track_chart = $product_part["trackChart"];
        } else {
            $track_chart = "";
        }

        if (isset($product_part["colorChart"])) {
            $color_chart = $product_part["colorChart"];
        } else {
            $color_chart = "";
        }

        if (isset($product_part["baffleChart"])) {
            $baffle_chart = $product_part["baffleChart"];
        } else {
            $baffle_chart = "";
        }

        if ($color_chart != 1 && $track_chart != 1 && $baffle_chart != 1) {
            $r1[] =
                '<div class="clear pt-4 pb-2 font-light" style="font-size:1.8rem;">' .
                $track_chart .
                "RAL colours</div>";
            foreach ($ral_colours as $i => $row) {
                $r1[] =
                    '<label class="color" style="background-color:rgb(' .
                    $row["rgb"] .
                    ');" data-cls="cls-' .
                    $c .
                    '" data-color="color-' .
                    $c .
                    '" data-priceband="priceband-' .
                    $c .
                    '" data-part="' .
                    $id .
                    '" data-price="' .
                    $row["priceband"] .
                    '"  data-hex="rgb(' .
                    $row["rgb"] .
                    ')"  data-tooltip="' .
                    $i .
                    " (" .
                    $row["name"] .
                    ')" data-label="' .
                    $i .
                    " - " .
                    $row["name"] .
                    '">
      <input type="radio" name="buttoncolor[' .
                    $product_part["filterOptions"] .
                    '][color]" value="' .
                    $row["rgb"] .
                    '">
   </label>';
                //  $t++;
            }
            $r1[] =
                '<div class="clear pt-4 pb-2 font-light" style="font-size:1.8rem;">Premium finishes</div>';
            foreach ($ral_colours3 as $i => $row) {
                $r1[] =
                    '<label class="color ' .
                    $row["bg"] .
                    '" data-cls="cls-' .
                    $c .
                    '" data-color="color-' .
                    $c .
                    '" data-priceband="priceband-' .
                    $c .
                    '" data-part="' .
                    $id .
                    '" data-price="' .
                    $row["priceband"] .
                    '"  data-hex="' .
                    $row["pr"] .
                    '"  data-tooltip="' .
                    $i .
                    '" data-label="' .
                    $i .
                    " - " .
                    $row["name"] .
                    '">
        <input type="radio" name="buttoncolor[' .
                    $product_part["filterOptions"] .
                    '][color]" value="' .
                    $row["rgb"] .
                    '">
    </label>';
            }
        } else {
            if ($color_chart == 1) {
                $r1[] =
                    '<div class="clear pt-4 pb-2 font-light" style="font-size:1.8rem;">Cable Colours</div>';
                foreach ($ral_colours2 as $i => $row) {
                    $r1[] =
                        '<label class="color" style="background-color:rgb(' .
                        $row["rgb"] .
                        ');" data-cls="cls-' .
                        $c .
                        '" data-color="color-' .
                        $c .
                        '" data-priceband="priceband-' .
                        $c .
                        '" data-part="' .
                        $id .
                        '" data-price="' .
                        $row["priceband"] .
                        '"  data-hex="rgb(' .
                        $row["rgb"] .
                        ')"  data-tooltip="' .
                        $i .
                        '" data-label="' .
                        $i .
                        " - " .
                        $row["name"] .
                        '">
      <input type="radio" name="buttoncolor[' .
                        $product_part["filterOptions"] .
                        '][color]" value="' .
                        $row["rgb"] .
                        '">
   </label>';
                    //  $t++;
                }
            }

            if ($baffle_chart == 1) {
                $r1[] =
                    '<div class="clear pt-4 pb-2 font-light"  style="font-size:1.8rem;">Baffle Colours</div>';
                foreach ($ral_colours5 as $i => $row) {
                    $r1[] =
                        '<label class="color" style="background-color:rgb(' .
                        $row["rgb"] .
                        ');" data-cls="cls-' .
                        $c .
                        '" data-color="color-' .
                        $c .
                        '" data-priceband="priceband-' .
                        $c .
                        '" data-part="' .
                        $id .
                        '" data-price="' .
                        $row["priceband"] .
                        '"  data-hex="rgb(' .
                        $row["rgb"] .
                        ')"  data-tooltip="' .
                        $i .
                        '" data-label="' .
                        $i .
                        " - " .
                        $row["name"] .
                        '">
      <input type="radio" name="buttoncolor[' .
                        $product_part["filterOptions"] .
                        '][color]" value="' .
                        $row["rgb"] .
                        '">
   </label>';
                    //  $t++;
                }
            }
            if ($track_chart == 1) {
                $r1[] =
                    '<div class="clear pt-4 pb-2 font-light" style="font-size:1.8rem;">Track Colours</div>';
                foreach ($ral_colours4 as $i => $row) {
                    $r1[] =
                        '<label class="color" style="background-color:rgb(' .
                        $row["rgb"] .
                        ');" data-cls="cls-' .
                        $c .
                        '" data-color="color-' .
                        $c .
                        '" data-priceband="priceband-' .
                        $c .
                        '" data-part="' .
                        $id .
                        '" data-price="' .
                        $row["priceband"] .
                        '"  data-hex="rgb(' .
                        $row["rgb"] .
                        ')"  data-tooltip="' .
                        $i .
                        '" data-label="' .
                        $i .
                        " - " .
                        $row["name"] .
                        '">
      <input type="radio" name="buttoncolor[' .
                        $product_part["filterOptions"] .
                        '][color]" value="' .
                        $row["rgb"] .
                        '">
   </label>';
                    //   $t++;
                }
            }
        }
        $bp[strtolower($product_part["filterOptions"])] = $r1;
        unset($r1);
        $m++;
        $c++;
    }
}

$m_index = 1;
$d = 0;
$pills = "";
$tab = "";
$color = "";

if (isset($bp)) {
    foreach ($bp as $key => $val) {
        if ($m_index == 1) {
            $active = "active";
        } else {
            $active = "";
        }
        $pills .=
            '<li><a class="button-part ' .
            $active .
            '"  data-key="' .
            $_POST["typekey"] .
            '" data-path="' .
            htmlentities($data_path[$d]) .
            '" data-ind="path-' .
            $m_index .
            '" data-num="' .
            $m_index .
            '" data-toggle="pill" href="#menu' .
            $m_index .
            '">' .
            $key .
            "</a></li>";

        foreach ($val as $v) {
            $color .= $v;
        }

        if ($m_index == 1) {
            $cl = "in active show";
        } else {
            $cl = "";
        }
        $tab .=
            '<div id="menu' .
            $m_index .
            '" class="tab-pane fade ' .
            $cl .
            '" style="background:none !important">' .
            $color .
            "</div>";
        $color = "";
        $m_index++;
        $d++;
    }
}
if ($configuratorEnabled == 1 || $page->get("template") == 77) {
    $continuebutton =
        '    <button class="button button-underline" id="close_selector">Back</button>    <button class="button button-underline config-selector">Next</button>';
}
/*
else {
    if ($page->getTVValue("specSheet") != "") {
        $continuebutton =
            '<a href="' .
            $page->getTVValue("specSheet") .
            '" target="_blank"  class="button button-underline button-blue mt-4"><i class="icon-download"></i> Spec Sheet</a>';
    } else {
        $continuebutton =
            '<a href=""  class="button button-blue button-underline mt-4" data-toggle="modal" data-target="#requestQuote">Request Quote</a>';
    }
}
*/
$colorbutton = "";
if (
    /*
    isset($product_parts) &&
    is_countable($product_parts) &&
    count($product_parts) >= 0
 
 */
    1 == 1
) {
    $colorbutton =
        '<div class="mt-5"><button class="button button-underline color-selector">Next</button></div>';
}
if ($page->getTVValue("skipColors") == 1) {
    $colorbutton =
        '<div class="mt-5"><button class="button button-underline config-selector">Next</button></div>';
}
if (isset($_POST["rgb"])) {
    $rgb = $_POST["rgb"];
} else {
    $rgb = "";
}

if ($pills) {
    $cf =
        '
 <ul class="nav nav-pills color-pills" id="myTab" role="tablist">' .
        $pills .
        '</ul>
        <div class="tab-content" id="myTabContent">' .
        $tab .
        '
            <div></div>
            <input type="hidden" name="product_id" value="' .
        $id .
        '">
            <input type="hidden" name="fixture_type" value="' .
        $_POST["typekey"] .
        '"> ' .
        $svgparts .
        '
            <input type="hidden" name="hex" value="' .
        $rgb .
        '">
            <div class="modal fade" id="configuratorModal2" tabindex="-1" role="dialog" aria-labelledby="coloursModalLabel" aria-hidden="true">
                <div class="modal-dialog  modal-xl">
                    <div class="loading"></div>
                    <div class="modal-content px-4 py-3">
                        <div class="modal-header">
                            <h4 class="modal-title">' .
        $pagetitle .
        '</h4>
                            <input type="submit">
                            <button type="button" class="close modal-close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
                        </div>
                        <div class="modal-body"> </div>
                    </div>
                </div>
            </div>
        </div>

';
} else {
    $cf =
        '<div class="alert alert-warning mt-3" role="alert" style="max-width:390px">There are no colour options available for this type. Please just click NEXT to configure this product</div>';
}
/*
if (is_countable($fixtureTypes) && count($fixtureTypes) > 1) {
$step1caption='<div class="mb-4" style="font-size:2rem;"><strong>Step 1</strong> - '.$page->getTVValue("step1Caption");    
}    
*/
if ($page->getTVValue("trackLengthEnabled") == 1) {
    $standardColorNote =
        '<div style="font-size:1.2rem !important">Note: Standard finishes are Black Powdercoat (RAL9005) and White Powdercoat (RAL9010)</div>';
}
$data["colorConfigurator"] =
    ' 
<form id="handler" action="" id="configurator-new" method="post">
    <div id="mounting-buttons-container" class="mt-0"><div class="number-indicator">1</div>
        <div class="mb-4" style="font-size:2rem;"><strong>Step 1</strong><br>' .
    ucfirst(strtolower($page->getTVValue("step1Caption"))) .
    "</div>" .
    $btnMounting .
    $colorbutton .
    '
    </div>
    <div id="color-selector"><div class="number-indicator">2</div> 
       <div style="font-size:2rem; margin-bottom:18px;"><strong>Step 2</strong><br>Choose colours</div>' .
    $standardColorNote .
    $cf .
    '
</form>
<div style="clear:both; margin-bottom:20px;"></div>
' .
    $continuebutton;

#End Color Stuff

if (
    $countselected == count($selects) ||
    $page->getTVValue("configuratorEnabled") == 0 ||
    $page->get("template") == 77
) {
    if (
        $page->getTVValue("configuratorEnabled") == 1 &&
        $modx->user->isMember("Pricing") &&
        $page->getTVValue("pricingEnabled") == 1
    ) {
        $statusMessage ='';
          
    } elseif ($page->getTVValue("configuratorEnabled") == 1) {
        $statusMessage =
            '<div class="center ds-button" style="margin-top:15px;"><a href="#" id="cfg-submit" class="button">Data Sheet & LDTs</a></div>';
    } else {
        $statusMessage = '<div class="center ds-button" style="margin-top:15px;">
  <a href="#" id="cfg-submit" class="button">Data Sheet</a>
  </div>';
    }
} else {
    $statusMessage =
        ' <button class="button button-underline" id="close_all">Back</button>';
    if ($page->getTVValue("skipColors") == 1) {
    } else {
        $statusMessage =
            '<button class="button button-underline" id="goback">Back</button>';
    }
}

$test = $page->getTVValue("fixtureDistributions");
$test = json_decode($test, true);

//if ($colorsEnabled) {
$data["colorsenabled"] = 1;
//} else {
//   $data["colorsenabled"] = 0;
//}
$data["statusMessage"] = $statusMessage ?? "";
$data["startSrc"] = $startSrc ?? "";
$data["startImage"] = $startImage ?? "";
$data["countselected"] = $countselected ?? "";
$data["numPathsForMounting"] = $numPathsForMounting ?? "";
$data["original"] = $original ?? "";
$data["defaultFixture"] = $defaultFixture ?? "";

$output = [$data];
echo json_encode($output);