<?php


require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");





/*
 $rid=$_POST['resourceid']; 
 
 
 $page = $modx->getObject("modResource", $rid);
*/ 
 $type=$_POST['type'];
$description=$_POST['description'];
$colors=$_POST['colors'];
$qty=$_POST['qty'];
$unitprice=$_POST['unitprice'];
$netprice=$_POST['netprice'];
$image=$_POST['image'];
$svg=$_POST['svg'];
$quoteid=$_POST['quoteid'];
$customerid=$_POST['customerid'];
$resourceid=$_POST['resourceid'];
$priceid=$_POST['priceid'];



for($i=0; $i<count($type); $i++) {
    
$sql = "REPLACE INTO modx_lightforms_quotes (id, customerid, resourceid, priceid, type, description, colors, qty, unitprice, netprice, image, svg) VALUES ('$quoteid[$i]', '$customerid','$resourceid[$i]','$priceid[$i]', '$type[$i]', '$description[$i]', '$colors[$i]', $qty[$i], $unitprice[$i], $netprice[$i], '$image[$i]', '$svg[$i]')";


$stmt = $modx->prepare($sql);
$stmt->execute();    
    
}
    

    $sql = "SELECT type,description,colors,qty,unitprice,netprice,image,svg,id FROM modx_lightforms_quotes WHERE customerid='$customerid' ORDER BY id DESC";
    
    
    
    
    


foreach ($modx->query($sql) as $row) {
$tr.='<input type="hidden" name="itemid[]" value="'.$row[8].'"><tr><td><input type="text" name="type[]" value="'.$row[0].'" size="4"></td><td><textarea name="description[]"  rows="8" cols="30" style="font-size:16px;">'.$row[1].'</textarea></td><td><textarea name="colors[]" rows="8" cols="30" style="font-size:16px;">'.$row[2].'</textarea></td><td><input type="hidden" name="qty[]" value="'.$row[3].'">'.$row[3].'</td><td><input type="hidden" name="unitprice[]" value="'.$row[4].'">'.$row[4].'</td><td><input type="hidden" name="netprice[]" value="'.$row[5].'">'.$row[5].'</td><td><input type="hidden" name="image[]" value="'.$row[6].'"><input type="hidden" name="svg[]" value="'.$row[7].'">
<div style="width:100px; position:relative;"><img src="' .
            $row[7] .
            '" alt="" style="position:absolute; top:0; left:0;"><img src="' .
            $row[6] .
            '" alt="" style="width:100%;"></div>

</td></tr>';
}

$table='

<div class="modal-content">
 <form action="" method="post" id="updateform">
         <!-- Modal Header -->
         <div class="modal-header">
            <h4 class="modal-title">Quotation</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
         </div>
         <!-- Modal body -->
         <div class="modal-body">
         <div>Customer: '.$page->get('pagetitle').'</div>
         <div>Project: '.$page->get('longtitle').'</div>
         <table class="table">
<tr><th>Type</th><th>Description</th><th>Colour/Finishes</th><th>Qty</th><th>Unit Price (&pound;)</th><th>Nett (&pound;)</th><th>Image</th></tr>   

'.$tr.'

<input type="hidden" name="resourceid" value="'.$rid.'"></table>
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


