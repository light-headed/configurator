<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");


$stmt = $modx->prepare("SELECT id,customer,project,quoteref FROM modx_lightforms_customerquotes");

$results = [];

if ($stmt->execute()) {
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach($results as $v) {
 
 $tr.='<tr><td>'.$v['quoteref'].'</td><td>'.$v['customer'].'</td><td>'.$v['project'].'</td>
  <td style="text-align:center"><button type="button" class="btn btn-dark open-quotation" style="font-size:14px; width:100px;" data-toggle="modal" data-customerid="'.$v['id'].'" data-target="#previewQuote">
   View Quote
  </button></td><td style="text-align:center"><a href="/quotes/quote.php?qid='.$v['id'].'" class="btn btn-dark" target="_blank" style="font-size:14px;  width:100px;">
    PDF
  </a></td><td style="text-align:center"><button class="btn btn-warning delete-quote" data-deleteref="'.$v['quoteref'].'" data-deleteid="'.$v['id'].'" style="font-size:14px;  width:100px;">
    Delete
  </button></td>
</tr>';
   
}
$data["quoterows"] = $tr ?? ""; 

$output = [$data];
echo json_encode($output);