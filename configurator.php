<?php
/**
 * configurator.php
 * Handles product configurators (form select & color choices)
 *
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");


$data["defaultFixture"] = $defaultFixture ?? "";

$output = [$data];
echo json_encode($output);