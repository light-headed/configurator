<?php
$photometrypath = "/ws/configurator/photometry/";
$ldtpath = "/ws/configurator/ldt/";
$polarpath = "/ws/configurator/polar-plots/";
$fname = "";
# LDT FILE CONFIGURATOR
/*
if ($parent->getTVValue("individualLDT") == "No") {

   
    $ldtFile_link =
  '<a href="'.$product->getTVValue("ldtFiles").'" class="dl-link-black" target="_blank">LDT files</a>';



} else {
*/

   
        if ($distribution == "Direct") {
            if ($newPage->getTVValue("TS_Output") == "UGR<19") {
                $old_name =
                    $_SERVER["DOCUMENT_ROOT"] .
                    $ldtpath .
                    $fixtureTypes[$typekey]["ldtdirectugr"];
            } else {
                if ($product->get("template") == 54 || $product->get("template") == 75) {
                    $old_name =
                        $_SERVER["DOCUMENT_ROOT"] . $ldtpath . $spotLdtTemplate;
                } else {
                    $old_name =
                        $_SERVER["DOCUMENT_ROOT"] .
                        $ldtpath .
                        $fixtureTypes[$typekey]["ldtdirect"];
                }
            }
        } elseif ($distribution == "Indirect") {
            $old_name =
                $_SERVER["DOCUMENT_ROOT"] .
                $ldtpath .
                $fixtureTypes[$typekey]["ldtindirect"];
        } elseif ($distribution == "Direct-Indirect") {
            $string = $newPage->getTVValue("TS_Output");
            if (stripos($string, "UGR<19") !== false) {
                $old_name =
                    $_SERVER["DOCUMENT_ROOT"] .
                    $ldtpath .
                    $fixtureTypes[$typekey]["ldtdirectindirectugr"];
            } else {
                $old_name =
                    $_SERVER["DOCUMENT_ROOT"] .
                    $ldtpath .
                    $fixtureTypes[$typekey]["ldtdirectindirect"];
            }
        }
        if ($product->getTVValue("TS_NominalSize") != "To Be Confirmed") {
            $fname = $newPage->get("pagetitle") . ".ldt";

            $new_name = $_SERVER["DOCUMENT_ROOT"] . $photometrypath . $fname;
            copy($old_name, $new_name);

            if (strpos($old_name, ".ldt")) {
                copy($old_name, $new_name);
                $file_content = file($new_name);
                $x = count($file_content);
            }
        }
        /* End NEW configurator LDTs */

  date_default_timezone_set("Europe/London");

       if ($newPage->getTVValue("TS_NominalSize") != "To Be Confirmed") {


        if (strpos($old_name, ".ldt")) {
            $fp = fopen($new_name, "w+");
        }
        $file_content[8] =
            $newPage->get("longtitle") .
            " [" .
            $newPage->getTVValue("TS_Output") .
            "] [" .
            $newPage->getTVValue("TS_Distribution") .
            "]\r\n";
        $file_content[9] = $newPage->get("pagetitle") . "\r\n";
        $file_content[10] = $fname . "\r\n";
        $file_content[11] = date("d\-m\-y h:i:s A") . "\r\n";

        if ($parent->get("template") != 56 && $parent->get("template") != 54 && $parent->get("template") != 75) {
            //does not equal Circular Bands
            $file_content[12] = $actuallength . "\r\n";
            $file_content[15] = $luminousarea . "\r\n";
        }
        if ($parent->get("template") == 56) {
            $luminousArea = $file_content[15];
            $circ = $fixtureDiameter * pi();
            $file_content[28] =
                round(
                    ($luminousArea / $circ) *
                        ($totalLumensDirect + $totalLumensIndirect)
                ) . "\r\n";
            $file_content[31] =
                round(($luminousArea / $circ) * $totalWatts, 2) . "\r\n";
        } elseif ($parent->get("template") == 54 || $parent->get("template") == 75) {
            $file_content[14] = $housingHeight . "\r\n"; //Height

            $file_content[28] = round($totalLumensDirect) . "\r\n"; // Lumens
            $file_content[31] = round($totalWatts, 2) . "\r\n"; // Watts
        } else {
            $file_content[28] =
                round($totalLumensDirect + $totalLumensIndirect) . "\r\n";
            $file_content[31] = round($totalWatts, 2) . "\r\n";
        }
        $file_content[29] = $newPage->getTVValue("TS_cct") . "\r\n";
        $file_content[30] = $newPage->getTVValue("TS_cri") . "\r\n";
        $total_lumens = $totalLumensDirect + $totalLumensIndirect;
        //  $file_content[21]=$total_lumens;
        //
        if ($newPage->getTVValue("TS_Distribution") == "Direct-Indirect") {
            $num_c_planes = $file_content[3]; // (4) Number Mc of C-planes between 0 and 360 degrees (usually 24 for interior, 36 for road lighting luminaires)
            $distance_c_planes = $file_content[4]; // (5) Distance Dc between C-planes (Dc = 0 for non-equidistantly available C-planes)
            $li = $file_content[5]; // (6) Number Ng of luminous intensities in each C-plane (usually 19 or 37) 46
            $dsl = 42 + $num_c_planes + $li; // Data start Line
            $data_inc = $li / 2; // Because we're stopping at 90 deg
          
            $file_content[21] =
                round($totalLumensDirect / $total_lumens, 2) * 100 . "\r\n"; // Downward Flux fraction
            
        }
        $y = 0;
        while ($y < $x) {
            fwrite($fp, $file_content[$y]);
            $y++;
        }
        fclose($fp);

        if (strpos($old_name, ".ldt")) {
            
           $ldtFile_link =   '<a href="' .
                $photometrypath .
                $fname .
                '" class="dl-link-black" target="_blank">LDT file</a>';
            
            
         
        }
  $sourceLDT = $_SERVER["DOCUMENT_ROOT"] . $photometrypath . $fname;  

    } else {
    
           $sourceLDT = $old_name;   
        
    }    
         
    
// }
// ------- CHART DIRECTOR ------- //

require_once "phpchartdir.php";

if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $polarpath . date("m\-y"))) {
    mkdir($_SERVER["DOCUMENT_ROOT"] . $polarpath . date("m\-y"));
}

if ($file = file($sourceLDT)) {
    $num_c_planes = $file[3]; // (4) Number Mc of C-planes between 0 and 360 degrees (usually 24 for interior, 36 for road lighting luminaires)
    $distance_c_planes = $file[4]; // (5) Distance Dc between C-planes (Dc = 0 for non-equidistantly available C-planes)
    $li = $file[5]; // (6) Number Ng of luminous intensities in each C-plane (usually 19 or 37)
    $sym = $file[2]; //Symmetry, 0 for UGR
    $dsl = 42 + $num_c_planes; // Data start Line
    $angle_range = $dsl + $li;
    $icp = $num_c_planes / 4; //Increment C Planes
    $x = count($file);
    //   $fp = fopen($file, "w+");
    if ($sym == 0) {
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles1[] = $file[$i] + 180;
        }
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles2[] = $file[$i] + 180;
        }
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles3[] = 360 - $file[$i] + 180;
        }
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles4[] = 360 - $file[$i] + 180;
        }
        $m = 1;
        for ($i = $dsl + $li * $m; $i < $dsl + $li * ($m + 1); $i++) {
            $data3[] = $file[$i];
        }
        $m = $m + $icp;
        for ($i = $dsl + $li * $m; $i < $dsl + $li * ($m + 1); $i++) {
            $data4[] = $file[$i];
        }
        $m = $m + $icp;
        for ($i = $dsl + $li * $m; $i < $dsl + $li * ($m + 1); $i++) {
            $data1[] = $file[$i];
        }
        $m = $m + $icp;
        for ($i = $dsl + $li * $m; $i < $dsl + $li * ($m + 1); $i++) {
            $data2[] = $file[$i];
        }
    } elseif ($sym == 1) {
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles1[] = $file[$i] + 180;
            $angles2[] = 360 - $file[$i] + 180;
           // $angles2[] = 360 - $file[$i] + 180;
        }

        for ($i = $dsl + $li; $i < $dsl + $li * 2; $i++) {
            $data1[] = $file[$i];
            $data2[] = $file[$i];
        }
        
    } elseif ($sym == 2) {
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles1[] = $file[$i] + 180;
            $angles2[] = 360 - $file[$i] + 180;
        }

        for ($i = $dsl + $li; $i < $dsl + $li * 2; $i++) {
            $data1[] = $file[$i];
            $data2[] = $file[$i];
        }
        
    } else {
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles1[] = $file[$i] + 180;
            $angles2[] = 360 - $file[$i] + 180;
        }
        for ($i = $dsl; $i < $dsl + $li; $i++) {
            $angles3[] = $file[$i] + 180;
            $angles4[] = 360 - $file[$i] + 180;
        }
        $k = 0;
        for ($i = 40; $i <= 100; $i++) {
            if ($file[$i] == 90) {
                $num = $k;
                break;
            }
            $k++;
        }
        for ($i = $dsl + $li; $i < $dsl + $li * 2; $i++) {
            $data1[] = $file[$i];
            $data2[] = $file[$i];
        }
        for ($i = $dsl + $li * ($num - 1); $i < $dsl + $li * $num; $i++) {
            $data3[] = $file[$i];
            $data4[] = $file[$i];
        }
    }

    if ($parent->getTVValue("individualLDT") == "Yes") {
        $c = new PolarChart(450, 450);
        $c->setPlotArea(225, 200, 150);
        $c->setGridStyle(false);
        $b = $c->addLegend(230, 393, false, "arial.ttf", 10);
        $b->setAlignment(TopCenter);
        $b->setBackground(Transparent, Transparent, 1);
        $c->addText(
            230,
            380,
            "cd/klm",
            "arial.ttf",
            10,
            0x000000,
            TopCenter,
            0
        );
        $c->angularAxis->setLinearScale(0, 360, 15);
        $c->angularAxis->addLabel(345, "165°");
        $c->angularAxis->addLabel(330, "150°");
        $c->angularAxis->addLabel(315, "135°");
        $c->angularAxis->addLabel(300, "120°");
        $c->angularAxis->addLabel(285, "105°");
        $c->angularAxis->addLabel(270, "90°");
        $c->angularAxis->addLabel(255, "75°");
        $c->angularAxis->addLabel(240, "60°");
        $c->angularAxis->addLabel(225, "45°");
        $c->angularAxis->addLabel(210, "30°");
        $c->angularAxis->addLabel(195, "15°");
        $c->angularAxis->addLabel(180, "0°");
        $c->angularAxis->addLabel(165, "15°");
        $c->angularAxis->addLabel(150, "30°");
        $c->angularAxis->addLabel(135, "45°");
        $c->angularAxis->addLabel(120, "60°");
        $c->angularAxis->addLabel(105, "75°");
        $c->angularAxis->addLabel(90, "90°");
        $c->angularAxis->addLabel(75, "105°");
        $c->angularAxis->addLabel(60, "120°");
        $c->angularAxis->addLabel(45, "135°");
        $c->angularAxis->addLabel(30, "150°");
        $c->angularAxis->addLabel(15, "165°");
        $c->angularAxis->addLabel(0, "180°");
        if ($sym == 0) {
            $layer2 = $c->addLineLayer($data2, 0x1821cd, "C90 - C270");
            $layer2->setAngles($angles2);
            $layer2->setLineWidth(1);
            $layer2->setCloseLoop(false);
            $layer4 = $c->addLineLayer($data4, 0x1821cd, "");
            $layer4->setAngles($angles4);
            $layer4->setLineWidth(1);
            $layer4->setCloseLoop(false);
            $layer1 = $c->addLineLayer($data1, 0xff0000, "C0 - C180");
            $layer1->setAngles($angles1);
            $layer1->setLineWidth(1);
            $layer1->setCloseLoop(false);
            $layer3 = $c->addLineLayer($data3, 0xff0000, "");
            $layer3->setAngles($angles3);
            $layer3->setLineWidth(1);
            $layer3->setCloseLoop(false);
            $c->getLegend()->setReverse();
        } elseif ($sym == 2) {
            $layer1 = $c->addLineLayer($data1, 0xff0000, "C0 - C180");
            $layer1->setAngles($angles1);
            $layer1->setLineWidth(1);
            $layer1->setCloseLoop(false);
            $layer2 = $c->addLineLayer($data2, 0xff0000, "");
            $layer2->setAngles($angles2);
            $layer2->setLineWidth(1);
            $layer2->setCloseLoop(false);
            $c->getLegend()->setReverse();
        } else {
            $layer3 = $c->addLineLayer($data3, 0x1821cd, "C90 - C270");
            $layer3->setAngles($angles3);
            $layer3->setLineWidth(1);
            $layer3->setCloseLoop(false);
            $layer4 = $c->addLineLayer($data4, 0x1821cd, "");
            $layer4->setAngles($angles4);
            $layer4->setLineWidth(1);
            $layer4->setCloseLoop(false);
            $layer1 = $c->addLineLayer($data1, 0xff0000, "C0 - C180");
            $layer1->setAngles($angles1);
            $layer1->setLineWidth(1);
            $layer1->setCloseLoop(false);
            $layer2 = $c->addLineLayer($data2, 0xff0000, "");
            $layer2->setAngles($angles2);
            $layer2->setLineWidth(1);
            $layer2->setCloseLoop(false);
            $c->getLegend()->setReverse();
        }
        # Output the chart

        header("Content-type: image/svg");
        $c->makeChart(
            $_SERVER["DOCUMENT_ROOT"] .
                $polarpath .
                date("m\-y") .
                "/" .
                $newPage->get("pagetitle") .
                ".svg"
        );
        $polarPlotImage =
            '<img src="' .
            $polarpath .
            date("m\-y") .
            "/" .
            $newPage->get("pagetitle") .
            '.svg" alt="" width="350"  style="border:1px solid #eee;">';
    }
}

// ------- END CHART DIRECTOR ------- //