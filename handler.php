<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");
require_once "colors.php";
$housingSize = "";
$fixtureWeight = "";
$spotBeamAngle = "";
$actuallength = "";
$nominallength = "";
$housingWeight = 0;
$spotCluster = 1;
$numspotheads = "";

# return lumens for each cct & cri
require_once "functions.php";

$productSectionId = 120657;

$productId = $_POST["pageId"] ?? "";
$distribution = $_POST["fixtureDistributions"] ?? "";
$cri = $_POST["fixtureCRI"] ?? "";
$cct = $_POST["fixtureCCT"] ?? "";
$driver = $_POST["fixtureDriver"] ?? "";
$output = $_POST["fixtureOutputs"] ?? "";
$outputBeam = $_POST["outputBeam"] ?? "";
$spotBeamSpread = $_POST["spotBeamSpread"] ?? "";
$spotSelection = $_POST["spotSelection"] ?? "";
$fixtureSize = $_POST["fixtureSize"] ?? "";
$fixtureDiameter = $_POST["fixtureDiameter"] ?? "";
$fixtureDiameters = $_POST["fixtureDiameters"] ?? "";
$profileSize = $_POST["fixtureProfile"] ?? "";
$fixtureMounting = $_POST["fixtureMountings"] ?? "";
$fixtureFinish = $_POST["fixtureFinishes"] ?? "";
$fixtureLightblade = $_POST["fixtureLightblade"] ?? "";
$typeindex = $_POST["typeindex"] ?? "";
$typekey = $_POST["typekey"] ?? "";
$trackLength = $_POST["trackLength"] ?? "";
$priceband = $_POST["priceband-1"] ?? "2";
$priceClicked = $_POST["token"] ?? "0";
$showPrice = "";
$worksheet_link = "";

$controls = [];
if ($_POST["emergency"]) {
    $controls[] = $_POST["emergency"];
}
if ($_POST["pir"]) {
    $controls[] = $_POST["pir"];
}
if ($_POST["casambi"]) {
    $controls[] = $_POST["casambi"];
}
$controls = implode(",", $controls);

if (isset($_POST["spotCluster"])) {
    $spotCluster = $_POST["spotCluster"];
    $numspotheads = $_POST["spotCluster"];
}

$page = $modx->getObject("modResource", $productSectionId);
$product = $modx->getObject("modResource", $productId);

$class_key = $product->get("class_key");
if ($class_key == "modSymLink") {
    $productId = $product->get("content");
    $product = $modx->getObject("modResource", $productId);
}

if ($product->get("template") == 54 || $product->get("template") == 75) {
    // If Downlight assume Direct :)

    $distribution = "Direct";
}

/*
if($product->get("id") ==  128550) {
    
  $distribution = "Direct-Indirect";   
    
}
*/
if ($product->get("template") == 83) {
    $fixtureTypes = $product->getTVValue("fixtureTypesCloud");
    $fixtureTypes = json_decode($fixtureTypes, true);
} elseif ($product->get("template") == 75) {
    $fixtureTypes = $product->getTVValue("fixtureTypesChannel");
    $fixtureTypes = json_decode($fixtureTypes, true);
    $housingSizeChannel = $product->getTVValue("housingSizeChannel");
    $housingSizeChannel = json_decode($housingSizeChannel, "true");
} elseif ($product->get("template") == 54) {
    $fixtureTypes = $product->getTVValue("fixtureTypesDownlight");
    $fixtureTypes = json_decode($fixtureTypes, true);
    $housingSize = $product->getTVValue("housingSize");
    $housingSize = json_decode($housingSize, "true");
} else {
    $fixtureTypes = $product->getTVValue("fixtureTypes");
    $fixtureTypes = json_decode($fixtureTypes, true);
}

$fixtureDescription = "";
if (isset($fixtureTypes[$typekey]["title"])) {
    $fixtureDescription = $fixtureTypes[$typekey]["title"];
}

// Check for Downlight with Upward Component (eg. Orion, Aquila)
if (isset($fixtureTypes[$typekey]["distribution"])) {
    $distributionDL = $fixtureTypes[$typekey]["distribution"];
    // returns Direct-Indirect
}

$imagesize = $product->getTVValue("imagesize");

$techphoto = [];
if (isset($fixtureTypes[$typekey]["image"])) {
    $techphoto = $fixtureTypes[$typekey]["image"];
}
$techmountingicon = [];
if (isset($fixtureTypes[$typekey]["icon"])) {
    $techmountingicon = $fixtureTypes[$typekey]["icon"];
}

$totalWeight = "";
if (isset($fixtureTypes[$typekey]["totalWeight"])) {
    $totalWeight = $fixtureTypes[$typekey]["totalWeight"];
}
$boards = $page->getTVValue("ledboards");

$boards = json_decode($boards, true);

$a = explode("-", $output);

$od = "";
if (isset($a[0])) {
    $od = $a[0];
}

$oi = "";
if (isset($a[1])) {
    $oi = $a[1];
}

$ledboard = $product->getTVValue("ledType");
$outputDirect = output_values($ledboard, $od, $cct, $cri);

if ($product->getTVValue("indirectOnly") == 1) {
    $indirectOutput = $output;
} else {
    $indirectOutput = $oi;
}

$outputIndirect = output_values($ledboard, $indirectOutput, $cct, $cri);

if ($product->get("template") == 57) {
    //Quadrant

    $fixtureDiameters = $product->getTVValue("fixtureDiameters");
    $fixtureDiameters = json_decode($fixtureDiameters, true);
    $boarddiameter = "";
    $fixtureWeight = "";
    foreach ($fixtureDiameters as $val) {
        if ($_POST["fixtureDiameters"] == $val["fixtureDiameters"]) {
            $boarddiameter = $val["boarddiameter"];
            $fixtureWeight = $val["totalWeight"];

            break;
        }
    }
    $outputDirect = output_values(
        $ledboard,
        $a[0] . "-" . $boarddiameter,
        $cct,
        $cri
    );
    $outputIndirect = output_values(
        $ledboard,
        $a[1] . "-" . $boarddiameter,
        $cct,
        $cri
    );

    $ulor = $fixtureTypes[$typekey]["ulor"] / 100;
    $dlor = $fixtureTypes[$typekey]["dlor"] / 100;

    $spotCluster = 1;
    $length = 1;
} elseif ($product->get("template") == 54 || $product->get("template") == 75) {
    //Downlights

    $spotBeamSpreads = $product->getTVValue("spotBeamSpread");
    $spotBeamSpreads = json_decode($spotBeamSpreads, true);

    $outputbeams = $product->getTVValue("outputBeam");
    $bracketWeight = $product->getTVValue("bracketWeight");
    $bracketWeightTotal = $bracketWeight * $spotCluster;
    $outputbeams = json_decode($outputbeams, true);
    // $spotbeamspreads = $product->getTVValue('spotBeamSpread');
    // $spotbeamspreads = json_decode($spotbeamspreads, true);
    $spotSelection = "";
    foreach ($outputbeams as $val) {
        if ($_POST["outputBeam"] == $val["outputBeam"]) {
            $spotSelection = $val["spotSelection"];

            break;
        }
    }
    $spotBeamAngle = "";
    foreach ($spotBeamSpreads as $val) {
        if ($_POST["spotBeamSpread"] == $val["spotBeamSpread"]) {
            $spotBeamAngle = " (" . $val["spotBeamAngle"] . "°)";

            if ($distributionDL == 1) {
                //  if($abc=="Direct-Indirect") {
                $spotLdtTemplate = $val["ldtTemplateDI"];
            } else {
                $spotLdtTemplate = $val["ldtTemplate"];
            }

            break;
        }
    }

    $boards = $page->getTVValue("ledSpots");

    $boards = json_decode($boards, true);

    $ledboard = $spotSelection;
    $dlor = 1;
    $ulor = 1;
    $length = 1;
    $outputDirect = output_values(
        $ledboard,
        $outputBeam . "-" . $spotBeamSpread,
        $cct,
        $cri
    );
    /* Added */
    if ($distributionDL == 1) {
        $outputIndirect = output_values(
            $ledboard,
            $outputBeam . "-" . $spotBeamSpread,
            $cct,
            $cri
        );
    }
    /* */
    if ($product->get("template") == 75) {
        if ($housingSizeChannel) {
            foreach ($housingSizeChannel as $v) {
                if ($v["housingSizeChannel"] == $_POST["housingSizeChannel"]) {
                    $housingWeight = $v["housingWeight"];
                    $housingSize = $v["housingSizeChannel"];

                    break;
                }
            }
        }
    } else {
        if ($housingSize) {
            foreach ($housingSize as $v) {
                if ($v["housingSize"] == $_POST["housingSize"]) {
                    $housingWeight = $v["housingWeight"];
                    $housingSize = $v["housingSize"];
                    $housingDrawing = $v["housingDrawing"];
                    $housingHeight = $v["housingHeight"];

                    break;
                }
            }
        }
    }

    $fixtureWeight = round(
        ($outputDirect[2] * $spotCluster +
            $housingWeight +
            $bracketWeightTotal) /
            1000,
        2
    );
    $output = $_POST["outputBeam"];
} elseif ($product->get("template") == 56) {
    $fixtureDiameters = $product->getTVValue("fixtureDiameter");
    $fixtureDiameters = json_decode($fixtureDiameters, "true");
    foreach ($fixtureDiameters as $v) {
        if ($v["fixtureDiameter"] == $_POST["fixtureDiameter"]) {
            $length = $v["boardslength"] / 1000;
            $fixtureDiameter = $v["fixtureDiameter"];
            $pricingLength = $v["fixtureDiameter"] * pi();
            $fixtureWeight = $v["fixtureWeight"];
            $luminousarea = $v["luminousArea"];
            $nominallength = $v["fixtureDiameter"] . "mm";
            break;
        }
    }

    $fixtureCanopy = $product->getTVValue("fixtureCanopy");
    $fixtureCanopy = json_decode($fixtureCanopy, "true");
    foreach ($fixtureCanopy as $v) {
        if ($v["fixtureCanopy"] == $_POST["fixtureCanopy"]) {
            $fixtureCanopy = $v["fixtureCanopy"];

            break;
        }
    }

    $ulor = $fixtureTypes[$typekey]["ulor"] / 100;
    $dlor = $fixtureTypes[$typekey]["dlor"] / 100;
} else {
    // Linear
    if ($product->get("template") == 83) {
        foreach ($fixtureTypes as $v) {
            if ($v["title"] == $fixtureDescription) {
                $length = $v["boardsLength"] / 1000;
                $totalWeight = $v["totalWeight"];

                break;
            }
        }

        $ulor = $fixtureTypes[$typekey]["ulor"] / 100;
        $dlor = $fixtureTypes[$typekey]["dlor"] / 100;
    } else {
        $fixtureLengths = $product->getTVValue("fixtureLengths");
        $fixtureLengths = json_decode($fixtureLengths, "true");
        foreach ($fixtureLengths as $v) {
            if ($v["fixtureLengths"] == $_POST["fixtureLengths"]) {
                $length = $v["boardslength"] / 1000;
                $actuallength = $v["actuallength"];
                $pricingLength = $v["actuallength"];
                $luminousarea = $v["luminousArea"];
                $nominallength = $v["fixtureLengths"];
                $totalWeight = $v["totalWeight"];

                break;
            }
        }

        $ulor = $fixtureTypes[$typekey]["ulor"] / 100;
        $dlor = $fixtureTypes[$typekey]["dlor"] / 100;
    }
}

// END OUTPUTS

$lumensDirect = $outputDirect[0] * $dlor * $spotCluster;
$lumensIndirect = $outputIndirect[0] * $ulor * $spotCluster;
$wattsDirect = $outputDirect[1] * $spotCluster;
$wattsIndirect = $outputIndirect[1] * $spotCluster;


$lumensPerMetre = $lumensDirect + $lumensIndirect;
$wattsPerMetre = $wattsDirect + $wattsIndirect;
$totalWatts = $wattsPerMetre * $length;
$totalLumensDirect = $lumensDirect * $length;
$totalLumensIndirect = $lumensIndirect * $length;
$totalLumens = $totalLumensDirect + $totalLumensIndirect;

// Get PROFILE PICTURES AND TITLES

if ($distribution == "Direct") {
    $techprofilepic = "";
    if (isset($fixtureTypes[$typekey]["profiledirect"])) {
        $techprofilepic = $fixtureTypes[$typekey]["profiledirect"];
    }
    $techprofiletitle = "";
    if (isset($fixtureTypes[$typekey]["profiletitledirect"])) {
        $techprofiletitle = $fixtureTypes[$typekey]["profiletitledirect"];
    }

    if (isset($fixtureTypes[$typekey]["costPerMetreD"])) {
        $cpm = $fixtureTypes[$typekey]["costPerMetreD"];
    } else {
        $cpm = 0;
    }
} elseif ($distribution == "Indirect") {
    $techprofilepic = $fixtureTypes[$typekey]["profileindirect"];
    $techprofiletitle = $fixtureTypes[$typekey]["profiletitleindirect"];

    if (isset($fixtureTypes[$typekey]["costPerMetreI"])) {
        $cpm = $fixtureTypes[$typekey]["costPerMetreI"];
    } else {
        $cpm = 0;
    }
} elseif (
    $distribution == "Direct-Indirect" ||
    $distribution == "Direct / Indirect"
) {
    $techprofilepic = $fixtureTypes[$typekey]["profiledirectindirect"];
    $techprofiletitle = $fixtureTypes[$typekey]["profiletitledirectindirect"];
    if (isset($fixtureTypes[$typekey]["costPerMetreDI"])) {
        $cpm = $fixtureTypes[$typekey]["costPerMetreDI"];
    } else {
        $cpm = 0;
    }
} else {
    $techprofilepic = "";
    $cpm = 0;
    //  $techprofiletitle = 'TBA';
}
/*
if(!$techprofiletitle) {

$techprofiletitle = 'TBA';    
    
}
*/
if ($product->get("template") == 54) {
    //Downlight
    $techprofilepic = $housingDrawing;
}

if ($productId == "118656") {
    // Buffalo

    $techprofiletitle = $profileSize;
}

// END TECH SHEET VALUES
//COLORS
for ($i = 1; $i <= 10; $i++) {
    if (!$_POST["cls-" . $i]) {
        $clsstyle[] = ".cls-" . $i . "{fill:rgb(255,255,255)}";
        $ralcolor[] = "RAL 9010 (Pure White)";
    } else {
        $clsstyle[] = "." . $_POST["cls-" . $i];
        $ralcolor[] = $_POST["color-" . $i];
    }
}

$original = "";
if (isset($fixtureTypes[$typekey]["buttonValue"])) {
    $original = $fixtureTypes[$typekey]["buttonValue"];
    $original = json_decode($original, true);
}

for ($i = 0; $i <= 9; $i++) {
    if (isset($original[$i]["buttonPath"])) {
        $svgpath[] = $original[$i]["buttonPath"];
    }
}

for ($i = 0; $i <= 9; $i++) {
    if (isset($original[$i]["filterOptions"])) {
        $partname[] = $original[$i]["filterOptions"];
    }
}

$svg_name = uniqid();

if (
    !file_exists(
        $_SERVER["DOCUMENT_ROOT"] . "/ws/configurator/overlays/" . date("m\-y")
    )
) {
    mkdir(
        $_SERVER["DOCUMENT_ROOT"] . "/ws/configurator/overlays/" . date("m\-y")
    );
}

$svgfile = fopen(
    $_SERVER["DOCUMENT_ROOT"] .
        "/ws/configurator/overlays/" .
        date("m\-y") .
        "/" .
        $svg_name .
        ".svg",
    "w"
);

$pathFinishes = $_SERVER["DOCUMENT_ROOT"] . "/ws/configurator/finishes/";

$finish = [];
$finish["ordos-sable"] = "ordos-sable.jpg";
$finish["nickel-metallic-gloss"] = "nickel-metallic-gloss.jpg";
$finish["metallic-fine-texture"] = "metallic-fine-texture.jpg";
$finish["mars-sable"] = "mars-sable.jpg";
$finish["golden-beach"] = "golden-beach.jpg";
$finish["gold-splendour"] = "gold-splendour.jpg";
$finish["dark-bronze-deep-matt"] = "dark-bronze-deep-matt.jpg";
$finish["copper-coarse-texture"] = "copper-coarse-texture.jpg";
$finish["aluminium-metallic-gloss"] = "aluminium-metallic-gloss.jpg";
$finishes = "";
foreach ($finish as $key => $value) {
    $finishes .=
        '<pattern id="' .
        $key .
        '" patternUnits="userSpaceOnUse" width="1500" height="1000">
    <image href="' .
        $pathFinishes .
        $value .
        '" x="0" y="0" width="1500" height="1000" />
  </pattern>';
}
//cls-1{fill:rgb(255,255,255)}
$cls_style = "";
foreach ($clsstyle as $val) {
    $cls_style .= $val;
}
$i = 0;

$colorpair = "";
if (isset($partname)) {
    foreach ($partname as $val) {
        if ($val) {
            $colorpair .= $val . "|" . $ralcolor[$i] . ",";
        } else {
            break;
        }

        $i++;
    }
}
$colorpair = rtrim($colorpair, ",");

fwrite(
    $svgfile,
    '<svg id="product-svg" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' .
        $imagesize .
        " " .
        $imagesize .
        '"><defs>
' .
        $finishes .
        '
<style>' .
        $cls_style .
        '</style></defs>
'
);
$svgpaths = "";
if (isset($svgpath)) {
    foreach ($svgpath as $val) {
        $svgpaths .= $val;
    }
}
fwrite($svgfile, $svgpaths);

fwrite($svgfile, "</svg>");

fclose($svgfile);

//END COLORS

$doc = $modx->newObject("modDocument");
$doc->set("parent", $productId);

$doc->set("longtitle", $product->get("longtitle"));
$doc->set("template", 55);
$doc->set("published", 0);
$doc->set("isfolder", 1);
$doc->set("searchable", 0);
$doc->set("publishedon", time());
$doc->save();

$newId = $doc->get("id");

$doc->set("description", $fixtureDescription, $newId);
$doc->set("pagetitle", "LF" . $newId);
$doc->set("alias", "LF" . $newId);

#General

if ($cct) {
    $doc->setTVValue("TS_cct", $cct, $newId); # Direct from POST
}
if ($cri) {
    $doc->setTVValue("TS_cri", $cri, $newId); # Direct from POST
}
if ($distribution) {
    $doc->setTVValue("TS_Distribution", $distribution, $newId); # Direct from POST
}
if ($driver) {
    $doc->setTVValue("TS_Driver", $driver, $newId); # Direct from POST
}
if ($fixtureLightblade) {
    $doc->setTVValue("TS_LightBlade", $fixtureLightblade, $newId); # Direct from POST
}

if ($techmountingicon) {
    $doc->setTVValue("TS_TechIcon", $techmountingicon, $newId); # Derived
}

# These 2 are only needed for cases where Colour selector in not enabled

if ($fixtureMounting) {
    $doc->setTVValue("TS_Mounting", $fixtureMounting, $newId); # Direct from POST
}
if ($fixtureFinish) {
    $doc->setTVValue("TS_Finish", $fixtureFinish, $newId); # Direct from POST
}

#Dimensions
if ($fixtureSize) {
    $doc->setTVValue("TS_FixtureSize", $fixtureSize, $newId); # Direct from POST
}
if ($housingSize) {
    $doc->setTVValue("TS_HousingSize", $housingSize, $newId); # Direct from POST
}
if ($numspotheads) {
    $doc->setTVValue("TS_NumSpotHeads", $numspotheads, $newId);
}

if ($fixtureDiameter) {
    $doc->setTVValue("TS_FixtureDiameter", $fixtureDiameter, $newId); # Derived
}

if (isset($_POST["fixtureDiameters"])) {
    $doc->setTVValue("TS_FixtureDiameters", $_POST["fixtureDiameters"], $newId);
}

if (isset($_POST["trackLength"]) && $_POST["trackLength"] >= 1) {
    $doc->setTVValue("TS_TrackLength", $_POST["trackLength"], $newId);
}

if ($nominallength) {
    $doc->setTVValue("TS_NominalSize", $nominallength, $newId);
}
if ($actuallength) {
    $doc->setTVValue("TS_ActualSize", $actuallength, $newId);
}
if ($product->get("template") == 57) {
    # Quadrant, ie. Moonfull

    $dims = $_POST["fixtureDiameters"] . " Ø x 60 mm";

    if ($dims) {
        $doc->setTVValue("TS_ProfileDimensions", $dims, $newId);
    }
} else {
    if ($techprofiletitle) {
        $doc->setTVValue("TS_ProfileDimensions", $techprofiletitle, $newId);
    }
}

if ($profileSize) {
    $doc->setTVValue("TS_FixtureProfile", $profileSize, $newId); # Direct from POST
}

if ($fixtureWeight) {
    $doc->setTVValue("TS_FixtureWeight", $fixtureWeight, $newId);
}
if ($totalWeight) {
    $doc->setTVValue("TS_TotalWeight", $totalWeight, $newId);
}

#Performance
if ($output) {
    $doc->setTVValue("TS_Output", $output, $newId);
}
if ($controls) {
    $doc->setTVValue("TS_Controls", $controls, $newId);
    $extras = $controls;
}
if ($spotBeamSpread || $spotBeamAngle) {
    $doc->setTVValue("TS_BeamAngles", $spotBeamSpread . $spotBeamAngle, $newId);
}

if (
    $product->get("template") == 14 ||
    $product->get("template") == 73 ||
    $product->get("template") == 79
) {
    # Linear Only

    if ($lumensPerMetre) {
        $doc->setTVValue("TS_LumensPerMetre", round($lumensPerMetre), $newId);
    }
    if ($wattsPerMetre) {
        $doc->setTVValue("TS_WattsPerMetre", round($wattsPerMetre, 1), $newId);
    }
}
if ($totalLumensDirect) {
    $doc->setTVValue("TS_TotalLumensDirect", round($totalLumensDirect), $newId);
}
if ($totalLumensIndirect) {
    $doc->setTVValue(
        "TS_TotalLumensIndirect",
        round($totalLumensIndirect),
        $newId
    );
}
if ($totalWatts) {
    $doc->setTVValue("TS_TotalWatts", round($totalWatts, 1), $newId);
}
if ($lumensPerMetre && $wattsPerMetre) {
    $doc->setTVValue(
        "TS_LumensPerWatt",
        round($lumensPerMetre / $wattsPerMetre),
        $newId
    );
}
if ($techphoto) {
    $doc->setTVValue("TS_TechPhoto", $techphoto, $newId);
}
if ($techprofilepic) {
    $doc->setTVValue("TS_TechProfilePic", $techprofilepic, $newId);
}
if ($techprofiletitle) {
    $doc->setTVValue("TS_TechProfileTitle", $techprofiletitle, $newId);
}
if ($svg_name) {
    $doc->setTVValue("TS_SvgFile", $svg_name . ".svg", $newId);
}
if ($colorpair) {
    $doc->setTVValue("TS_PartColor", $colorpair, $newId);
}
if ($priceband) {
    $doc->setTVValue("TS_PriceBand", $priceband, $newId);
}
$doc->save();

# Delete PDF Tech Sheet if Published Date is more than TTL in the past
$newPage = $modx->getObject("modResource", $newId);
$parentId = $newPage->get("parent");
$parent = $modx->getObject("modResource", $parentId);
$ttl = $product->getTVValue("ttl");
$ttl = time() - $ttl;
$documents = $modx->getCollection("modResource", [
    "template" => 55,
    "parent" => $productId,
    "createdon:<" => $ttl,
]);
foreach ($documents as $document) {
    $document->set("deleted", "1");
    $document->save();
}

require_once "photometry.php";

if (
    $product->getTVValue("configuratorEnabled") == 0 ||
    $product->get("template") == 77
) {
    $dspath = "/data-sheets/Colour-LF" . $newId . ".pdf";
} else {
    $dspath = "/data-sheets/Spec-LF" . $newId . ".pdf";
    $pricingpath = "/data-sheets/Price-LF" . $newId . ".pdf";
}

if (
    $product->getTVValue("configuratorEnabled") == 0 ||
    $product->get("template") == 77 ||
    $product->getTVValue("individualLDT") == "No" ||
    $product->getTVValue("individualLDTLink") == "No" ||
    $nominallength == "To Be Confirmed"
) {
    $ldtFile_link = "";
}

if (
    $modx->user->isMember("Pricing") &&
    $parent->getTVValue("pricingEnabled") == 1 &&
    !$cpm
) {
    $showPrice =
        '<h3 class="mt-5" style="color:red;">Something is wrong. Unable to calculate a price...</h3>';
} else {
    if (
        /*
        $modx->user->isMember("Pricing") &&
        $parent->getTVValue("pricingEnabled") == 1 &&
        $priceClicked == 1
        */
        1 == 1
    ) {
        $pricingenabled = 1;
        if ($distribution == "Direct-Indirect") {
            $multiplier = 2;
        } else {
            $multiplier = 1;
        }
        if ($_POST["costMultiplier"]) {
            $costMultiplier = $_POST["costMultiplier"];
        } else {
            $costMultiplier = 2.0;
        }
        //   $costMultiplier = $_POST["costMultiplier"];
        //    $costMultiplier = 2;

        if ($multiplier) {
            $modulemultiplier = $multiplier;
        }

        $actuallength = $pricingLength / 1000;
        //  $cpm = $product->getTVValue("costPerMetre");

        $currency = $product->getTVValue("costPerMetreCurrency");
        $hardwarecost = round($actuallength * $cpm, 2);
        $wastagePerCent = $product->getTVValue("wastage");
        $wastage = round(($wastagePerCent * $hardwarecost) / 100, 2);
        $landingPerCent = $product->getTVValue("landing");
        $landing = round(
            ($landingPerCent * ($hardwarecost + $wastage)) / 100,
            2
        );
        $totalhardware = $hardwarecost + $wastage + $landing;

        if ($cpm) {
            $costpermetrelocal = $cpm . " " . $currency;
        }

        if ($hardwarecost) {
            $hardwarecostlocal =
                number_format($hardwarecost, 2, ".", "") . " " . $currency;
        }

        if ($wastage) {
            $wastagecostlocal =
                number_format($wastage, 2, ".", "") . " " . $currency;
        }

        if ($landing) {
            $landingcostlocal =
                number_format($landing, 2, ".", "") . " " . $currency;
        }

        $eur = $product->getTVValue("euroToPound");
        $usd = $product->getTVValue("USDToPound");

        if ($currency == "USD") {
            $hardwarecostgbp = $totalhardware * $usd;
        } elseif ($currency == "EUR") {
            $hardwarecostgbp = $totalhardware * $eur;
        } else {
            $hardwarecostgbp = $totalhardware;
        }

        if ($totalhardware) {
            $totalhardwarecostlocal =
                number_format($totalhardware, 2, ".", "") . " " . $currency;
        }

        $hardwarecostgbp = round($hardwarecostgbp, 2);

        if ($hardwarecostgbp) {
            $totalhardwarecostsell =
                number_format($hardwarecostgbp, 2, ".", "") . " GBP";
        }

        $totalboardslength = $length * 1000;
        $tblength = $totalboardslength * $multiplier;

        $lbc = $page->getTVValue("linearBoardCost");
        $lbc = json_decode($lbc, true);

        // Price of boards from 560mm to 70mm
        foreach ($lbc as $v) {
            if (stripos($output, "tune") !== false) {
                $lng[$v["ledLength"]] = $v["ledPriceTW"];
            } else {
                $lng[$v["ledLength"]] = $v["ledPrice"];
            }
        }

        $bc = 0; // board cost

        $num = 0;
        $zarr = [];
        foreach ($lng as $key => $val) {
            $y = floor($totalboardslength / $key) * $val; //6,1
            $dl = floor($totalboardslength / $key) * $multiplier;
            $z .=
                '<tr><td colspan="2">' .
                $dl .
                " x " .
                $key .
                "mm @ " .
                number_format($val, 2, ".", "") .
                "</td></tr>";
            $zarr[] =
                $dl . " x " . $key . "mm @ " . number_format($val, 2, ".", "");
            $n = floor($totalboardslength / $key);
            $totalboardslength = $totalboardslength % $key; //300

            $bc = $bc + $y;

            $num = $num + $n;
        }

        if (count($zarr) > 0) {
            $listModules = implode(",", $zarr);

            $modulesquantity = $listModules;
        }

        $bc = round($bc * $multiplier, 2);

        if ($hardwarecostgbp) {
            $totalhardwarecostsell =
                number_format($hardwarecostgbp, 2, ".", "") . " GBP";
        }

        $lbc = $product->getTVValue("linearBoardCurrency");

        if ($lbc == "USD") {
            $tbc = $bc * $usd;
        } elseif ($lbc == "EUR") {
            $tbc = $bc * $eur;
        } else {
            $tbc = $bc;
        }

        $tbc = round($tbc, 2);

        $fixtureDriver = $product->getTVValue("fixtureDriver");
        $fixtureDriver = json_decode($fixtureDriver, true);

        foreach ($fixtureDriver as $v) {
            $drv = $v["fixtureDriver"];
            $fdc = $v["currency"];
            $driverType = $v["driverType"];

            if ($drv == $driver) {
                $driverCost = $v["driverCost"];
                $dc = json_decode($driverCost, true);

                if ($driverType == "Constant Voltage") {
                    foreach ($dc as $v2) {
                        if ($v2["maxWatts"] >= $totalWatts) {
                            if (stripos($output, "tune") !== false) {
                                $tdc = round($v2["priceTW"] * $multiplier, 2);
                            } else {
                                $tdc = round($v2["price"] * $multiplier, 2);
                            }

                            $driverInfo =
                                $drv .
                                " " .
                                $driverType .
                                " Drivers for " .
                                round($totalWatts, 0) .
                                " Watts";
                            break;
                        }
                    }
                } else {
                    foreach ($dc as $v2) {
                        if ($v2["qty"] == $num) {
                            if (stripos($output, "tune") !== false) {
                                $tdc = round($v2["priceTW"] * $multiplier, 2);
                            } else {
                                $tdc = round($v2["price"] * $multiplier, 2);
                            }

                            $driverInfo =
                                $num * $multiplier .
                                " x " .
                                $drv .
                                " " .
                                $driverType .
                                " Drivers";
                            break;
                        }
                    }
                }

                break;
            }
        }

        if ($fdc == "USD") {
            $tdcgbp = $tdc * $usd;
        } elseif ($fdc == "EUR") {
            $tdcgbp = $tdc * $eur;
        } else {
            $tdcgbp = $tdc;
        }

        $tdcgbp = round($tdcgbp, 2);

        if ($tblength) {
            $moduleslength = $tblength;
        }

        if ($bc) {
            //$totalmodulescostlocal=number_format($bc, 2, ".", "") . " " . $lbc;
            $totalmodulescostlocal = $bc;
        } else {
            $totalmodulescostlocal = 0.0;
        }

        if ($tbc) {
            $totalmodulescostsell = number_format($tbc, 2, ".", "") . " GBP";
        }

        if ($driverInfo) {
            $driverquantitytype = $driverInfo;
        }

        if ($driverInfo) {
            $drivercostlocal = number_format($tdc, 2, ".", "") . " " . $fdc;
        }
        if ($driverInfo) {
            $drivercostsell = number_format($tdcgbp, 2, ".", "") . " GBP";
        }

        $control = array_filter(explode(",", $controls));

        if (is_array($control) && count($control) > 0) {
            if ($driver == "DALI") {
                $emergencyCost = $product->getTVValue("emergencyCostDali");
                $pirCost = $product->getTVValue("pirCostDali");
            } else {
                $emergencyCost = $product->getTVValue("emergencyCost");
                $pirCost = $product->getTVValue("pirCost");
            }

            $casambiCost = $product->getTVValue("casambiCost");

            $totalExtras = 0;

            if (in_array("Emergency", $control)) {
                if ($emergencyCost) {
                    $emergencycost =
                        number_format($emergencyCost, 2, ".", "") . " GBP";
                }
                $totalExtras = $totalExtras + $emergencyCost;
            }

            if (in_array("PIR", $control)) {
                if ($pirCost) {
                    $pircost = number_format($pirCost, 2, ".", "") . " GBP";
                }
                $totalExtras = $totalExtras + $pirCost;
            }

            if (in_array("Casambi", $control)) {
                if ($casambiCost) {
                    $casambicost =
                        number_format($casambiCost, 2, ".", "") . " GBP";
                }
                $totalExtras = $totalExtras + $casambiCost;
            }

            if ($totalExtras) {
                $totalextras = $totalExtras;
            }
        }

        if ($priceband == 1) {
            $paintpermetre = 2;
        }

        if ($priceband == 2) {
            $paintpermetre = 4;
        }

        if ($priceband == 3) {
            $paintpermetre = 6;
        }

        $totalPaint = round($paintpermetre * $actuallength, 2);

        if ($paintpermetre) {
            $paintcostpermetre =
                number_format($paintpermetre, 2, ".", "") . " GBP";
        }

        if ($totalPaint) {
            $paintcosttotal = number_format($totalPaint, 2, ".", "") . " GBP";
        }
  $USDToPound = $page->getTVValue("USDToPound");
        $EuroToPound = $page->getTVValue("EuroToPound");
       
        if ($USDToPound) {
            $usdtopound = $USDToPound;
        }

        if ($EuroToPound) {
            $eurotopound = $EuroToPound;
        }

        $assemblyPerMetre = $product->getTVValue("assemblyPerMetre");
        $totalAssembly = round($assemblyPerMetre * $actuallength, 2);

        if ($assemblyPerMetre) {
            $assemblycostpermetre =
                number_format($assemblyPerMetre, 2, ".", "") . " GBP";
        }

        if ($totalAssembly) {
            $assemblycosttotal =
                number_format($totalAssembly, 2, ".", "") . " GBP";
        }

        $packagingPerMetre = $product->getTVValue("packagingPerMetre");
        $totalPackaging = round($packagingPerMetre * $actuallength, 2);

        if ($packagingPerMetre) {
            $packagingcostpermetre =
                number_format($packagingPerMetre, 2, ".", "") . " GBP";
        }

        if ($totalPackaging) {
            $packagingcosttotal =
                number_format($totalPackaging, 2, ".", "") . " GBP";
        }

        $totalCost = round(
            $hardwarecostgbp +
                $tbc +
                $tdcgbp +
                $totalExtras +
                $totalPaint +
                $totalAssembly +
                $totalPackaging,
            2
        );
        $totalSell = round($totalCost * $costMultiplier, 2);
        $grossProfit = round((($totalSell - $totalCost) * 100) / $totalSell, 0);
        if ($totalCost) {
            $costtotalnum = $totalCost;
            $costtotal = $totalCost;
        }

        if ($grossProfit) {
            $grossprofit = $grossProfit . "%";
        }
        if ($totalSell) {
            $showPrice =
                '<h2 class="mt-5">&pound;' .
                number_format($totalSell, 2, ".", "") .
                ' GBP</h2><h3 class="mt-5">Quotation reference: LF' .
                $newId .
                "</h3><p>This price is valid for a period of 7 days and is intended for guidance purposes only. For a written quotation, please contact Light Forms quoting the above reference.</p>";

            $worksheet_link =
                '<a href="' .
                $pricingpath .
                '" class="dl-link-black" target="_blank">Cost Analysis</a>';
        }
    }

    /* MIGX Prices */

    $modxuser = $modx->getUser();
    $userid = $modxuser->get("id");

    $speccode = "LF" . $newId;
    $type = $product->getTVValue("ProductCode");

    $createdby = $userid;
    $createdon = time();

    $contentid = $newId;

    if (!$priceband) {
        $priceband = 1;
    }
if(!$controls) {
    
$controls="None";    
}

if($_POST['projects']) {
    $project=$_POST['projects'];
    
} else {
     $project='None';  
    
}

 if ($product->getTVValue("pricingEnabled") == 1) {


        $sql = "INSERT INTO modx_lightforms_prices (pos, speccode, resourceid, type, shapesize, distribution, cct,cri, output, driver, extras, image, svg, project,actuallength,costtotalnum,
costpermetrelocal,hardwarecostlocal,wastagecostlocal,landingcostlocal,totalhardwarecostlocal,totalhardwarecostsell,moduleslength,modulesquantity,
totalmodulescostlocal,totalmodulescostsell,driverquantitytype,drivercostlocal,drivercostsell,paintcostpermetre,paintcosttotal,assemblycostpermetre,
assemblycosttotal,packagingcostpermetre,packagingcosttotal,costtotal,costmultiplier,grossprofit,totalsell,modulemultiplier,wastagepercent,landingpercent,emergencycost,
pircost,casambicost,totalextras,pricingenabled,usdtopound,eurotopound,colorband,contentid,createdby, createdon, deleted, published)
 VALUES (1, '$speccode', '$newId','$type','$nominallength', '$distribution','$cct','$cri','$output','$driver', '$controls', '$techphoto', '$svg_name','$project','$actuallength','$costtotalnum',
 '$costpermetrelocal','$hardwarecostlocal','$wastagecostlocal','$landingcostlocal','$totalhardwarecostlocal','$totalhardwarecostsell','$moduleslength','$modulesquantity',
 '$totalmodulescostlocal','$totalmodulescostsell','$driverquantitytype','$drivercostlocal','$drivercostsell','$paintcostpermetre','$paintcosttotal','$assemblycostpermetre',
 '$assemblycosttotal','$packagingcostpermetre','$packagingcosttotal','$costtotal','$costMultiplier','$grossprofit','$totalSell','$modulemultiplier','$wastagePerCent','$landingPerCent','$emergencycost',
 '$pircost','$casambicost','$totalextras','$pricingenabled','$usdtopound','$eurotopound','$priceband','$contentid','$createdby','$createdon',0,1)";

        $stmt = $modx->prepare($sql);
        $stmt->execute();
    }
    /* END MIGX Prices */
}

if ($priceClicked == 1) {
    $ds_link_done =
        '

' .
        $showPrice .
        $worksheet_link .
        '
 <!--
 <h3 class="mt-5">Downloads</h3>           
           <div class="mt-5 mb-2"> <img src="lf/images/icons/download-black.svg" width="30" alt="" style="width:30px"></div>
                          
            <a href="' .
        $dspath .
        '" class="dl-link-black" target="_blank">Data sheet</a>
          
                          
         ' .
        $ldtFile_link .
        '
  -->       
            
<div class="mt-5"><button class="configure_again start-again" style="color:black;">Start again</button>     




';
} else {
    $ds_link_done =
        '
<!-- <h4>Your files are ready to download</h4> -->

 
            
           <div class="mt-5 mb-2"> <img src="lf/images/icons/download-black.svg" width="30" alt="" style="width:30px"></div>
                          
            <a href="' .
        $dspath .
        '" class="dl-link-black" target="_blank">Data sheet</a>
          
                          
         ' .
        $ldtFile_link .
        '
         
            
<div class="mt-5"><button class="configure_again start-again" style="color:black;">Start again</button>     




';
}
$data["dataSheetLink"] = $ds_link_done;
// $data['dataSheetId'] = $id;
$output = [$data];
echo json_encode($output);