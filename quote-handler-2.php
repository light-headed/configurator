<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");

$customerid = $_POST["customer"];

/*
$page = $modx->getObject("modResource", $rid);

$type = $_POST["type"];
$description = $_POST["description"];
$colors = $_POST["colors"];
$qty = $_POST["qty"];
$unitprice = $_POST["unitprice"];
$netprice = $_POST["netprice"];
$image = $_POST["image"];
$svg = $_POST["svg"];
$itemid = $_POST["itemid"];

for ($i = 0; $i < count($type); $i++) {
    $sql = "REPLACE INTO modx_quotes (id, resource_id, resource_ids, pos, type, description, colors, qty, unitprice, netprice, image, svg) VALUES ($itemid[$i], $rid,'',4, '$type[$i]', '$description[$i]', '$colors[$i]', $qty[$i], $unitprice[$i], $netprice[$i], '$image[$i]', '$svg[$i]')";

    $stmt = $modx->prepare($sql);
    $stmt->execute();
}
*/

$sql = "SELECT quoteref,customer,project,id FROM modx_lightforms_customerquotes WHERE id='$customerid'";
foreach ($modx->query($sql) as $row) {

$quoteref=$row[0];
$customer=$row[1];
$project=$row[2];
$customerid=$row[3];
    
}

$sql = "SELECT id,type,description,colors,qty,unitprice,netprice,image,svg,resourceid,priceid FROM modx_lightforms_quotes WHERE customerid='$customerid'";

foreach ($modx->query($sql) as $row) {
    $tr .=
        '<input type="hidden" name="quoteid[]" value="' .
        $row[0] .
        '">
        <input type="hidden" name="resourceid[]" value="' .
        $row[9] .
        '">
         <input type="hidden" name="priceid[]" value="' .
        $row[10] .
        '">
        <tr><td><input type="text" name="type[]" value="' .
        $row[1] .
        '" size="4"></td><td><textarea name="description[]" rows="8" cols="30" style="font-size:16px;">' .
        $row[2] .
        '</textarea></td><td><textarea name="colors[]" rows="8" cols="30" style="font-size:16px;">' .
        $row[3] .
        '</textarea></td><td><input type="hidden" name="qty[]" value="' .
        $row[4] .
        '">' .
        $row[4] .
        '</td><td><input type="hidden" name="unitprice[]" value="' .
        $row[5] .
        '">' .
        $row[5] .
        '</td><td><input type="hidden" name="netprice[]" value="' .
        $row[6] .
        '">' .
        $row[6] .
        '</td><td><input type="hidden" name="image[]" value="' .
        $row[7] .
        '"><input type="hidden" name="svg[]" value="' .
        $row[8] .
        '">
<div style="width:100px; position:relative;"><img src="' .
        $row[7] .
        '" alt="" style="position:absolute; top:0; left:0;"><img src="' .
        $row[8] .
        '" alt="" style="width:100%;"></div>



</td></tr>';
}

$table =
    '

<div class="modal-content">
 <form action="" method="post" id="updateform">
         <!-- Modal Header -->
         <div class="modal-header">
            <h4 class="modal-title">Quotation</h4>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
         </div>
         <!-- Modal body -->
         <div class="modal-body">
         <h3 class="mt-0 mb-4" style="color:orange">Review quotation. The Type, Description & Colours fields can be edited and saved if needed.</h3>
         <div class="row mb-4">
         <div class="col-md-4"><span style="font-weight:bold;">Customer:</span> ' .
    $customer .
    '</div>
         <div class="col-md-4">Project: ' .
    $project .
    '</div>
         <div class="col-md-4">Quote Ref: '.$quoteref.'</div>
         </div>
     
         <table class="table">
<tr><th>Type</th><th>Description</th><th>Colour/Finishes</th><th>Qty</th><th>Unit Price (&pound;)</th><th>Nett (&pound;)</th><th>Image</th></tr>   

' .
    $tr .
    '

<input type="hidden" name="customerid" value="' .
    $customerid .
    '"></table>
         </div>
         <!-- Modal footer -->
         <div class="modal-footer">
         <button type="submit" class="btn btn-primary mr-5" style="font-size:18px">Save</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="font-size:18px">Close</button>
         </div>
</form>         
  
</div>
';

$data["returnquote"] = $table ?? "";

$output = [$data];
echo json_encode($output);