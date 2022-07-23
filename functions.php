<?php
function output_values($board_1, $output_1, $cct_1 = 0, $cri_1 = 0)
{
    global $boards;
    $out = 0;
    $out2 = "";
    $out3 = "";

    foreach ($boards as $value) {
        if ($value["boardtype"] == $board_1) {
            if (isset($value["boardweight"])) {
                $out3 = $value["boardweight"];
            }

            $boardoutputs = json_decode($value["boardoutputs"], true);

            foreach ($boardoutputs as $val) {
                if ($val["output"] == $output_1) {
                    $ccts = json_decode($val["cct"], true);
                    foreach ($ccts as $v) {
                        if ($v["cct"] == $cct_1) {
                            $cris = json_decode($v["cri"], true);

                            foreach ($cris as $c) {
                                if ($c["cri"] == $cri_1) {
                                    $out = $c["lumens"];

                                    $out2 = $c["watts"];

                                    break;
                                }
                            }

                            break;
                        }
                    }
                    break;
                }
            }

            break;
        }
    }

    $output = [$out, $out2, $out3];
    return $output;
}

function select_box($tv, $inc, $filter, $enabled = 0, $template = 0)
{
    $options = [];
    $v_arr = [];
    $testdir = "";
    global $modx, $page, $dlor, $ulor;
    $ledboard = $page->getTVValue("ledType");
   // $ulor = $page->getTVValue("ulor");
    //$dlor = $page->getTVValue("dlor");
    $caption = $modx
        ->getObject("modTemplateVar", [
            "name" => $tv,
        ])
        ->get("caption");
    $cris = $page->getTVValue($tv);
    $cris = json_decode($cris, true);
    $s = 0;

    $t = 0;
    if (is_array($cris)) {
        foreach ($cris as $v) {
            if (!array_intersect($filter, $v) || !$filter) {
                if (isset($_POST[$tv])) {
                    if ($v[$tv] == $_POST[$tv]) {
                        $selected = "selected";
                        $t++;
                    }
                }
                if ($tv == "fixtureOutputs") {
                    $op = explode("-", $v[$tv]);

                    if ($op[0] == "RGBW") {
                        $tag = " (white only)";
                    } else {
                        $tag = "";
                    }

                    if ($op[0] == "Tune Low" || $op[0] == "Tune Med") {
                        $cct = "2700-6500";
                    } else {
                        $cct = "4000";
                    }
                    if (isset($op[0])) {
                        $dir = output_values($ledboard, $op[0], $cct, "80");
                    } else {
                        $dir = 0;
                    }
                    if (isset($op[1])) {
                        $indir = output_values($ledboard, $op[1], $cct, "80");
                    } else {
                        $indir[0] = 0;
                    }

                    if ($testdir != $dir && isset($op[1]) && $s != 0) {
                        $divider = '<option data-divider="true"></option>';
                    } else {
                        $divider = "";
                    }

                    $testdir = $dir;

                    $lpm = round(($dir[0] * $dlor + $indir[0] * $ulor) / 100);

                    if ($lpm == 0) {
                        $lpm = "";
                    } else {
                        $lpm = $lpm . " lm/M" . $tag;
                    }
                } elseif ($tv == "spotBeamSpread") {
                    if ($v["spotBeamAngle"]) {
                        $spotBeamAngle = $v["spotBeamAngle"] . "&deg;";
                    }
                } else {
                    $lpm = "";
                }

                if ($tv == "fixtureOutputs" || $tv == "fixtureDistributions") {
                    $op = explode("-", $v[$tv]);
                    if (count($op) > 1) {
                        $op = $op[0] . " - " . $op[1];
                    } else {
                        $op = $v[$tv];
                    }
                } else {
                    $op = $v[$tv];
                }
                if ($template != 14 && $template != 73) {
                    $lpm = "";
                }
                if (!isset($divider)) {
                    $divider = "";
                }
                if (!isset($selected)) {
                    $selected = "";
                }

                if (!isset($spotBeamAngle)) {
                    $spotBeamAngle = "";
                }
                $options[] =
                    $divider .
                    '<option value="' .
                    $v[$tv] .
                    '" ' .
                    $selected .
                    ' data-subtext="' .
                    $lpm .
                    $spotBeamAngle .
                    '">' .
                    $op .
                    "</option>";
                $v_arr[] = $v[$tv];
                unset($selected);
                $s++;
            }
        }
    }
    if (is_array($v_arr) && isset($_POST[$tv])) {
        if (in_array($_POST[$tv], $v_arr)) {
            $status = 1;
        } else {
            $status = 0;
        }
    }
    $selectstyle = "cfg-select-default";

    if ($t > 0) {
        $selectstyle = "cfg-selected";
    }

    if ($t == 0 && $enabled == 1) {
        // Ready for selection

        $selectstyle = "cfg-select-this";
    }

    if ($enabled == 1 && $t > 0) {
        $selectstyle = "cfg-selected";
    }

    if ($enabled == 1 || $t > 0) {
        $enabled = "";
    } else {
        $enabled = "disabled";
    }

    if (is_array($options)) {
        $opt = "";
        foreach ($options as $option) {
            $opt .= $option;
        }
    }
    if (is_countable($options)) {
        if (count($options) >= 1) {
            $selects =
                '<div class="col-md-12 col-lg-6 col-xl-4 my-4"><label for="' .
                $tv .
                '">' .
                $caption .
                ':</label><br>
<select id="' .
                $tv .
                '" name="' .
                $tv .
                '" class="selectpicker config-select" title="Select" data-width="100%" data-style="' .
                $selectstyle .
                '" ' .
                $enabled .
                '>

' .
                $opt .
                '

</select></div>';
        } else {
            $selects = "";
        }
        $totaloptions = count($options);
        unset($options);
    }

    if (!isset($status)) {
        $status = 0;
    }
    $result = [$selects, $status, $totaloptions];
    return $result;
}