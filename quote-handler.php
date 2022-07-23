<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");

$customer = $_POST["customer"];
$project = $_POST["project"];
$quoteref = $_POST["quoteref"];
$qty = $_POST["qty"];

if($_POST['deleteid']) {
    
$d=$_POST['deleteid'];    
$sql = "DELETE FROM modx_lightforms_quotes WHERE customerid='$d'";
 
 $stmt = $modx->prepare($sql);
$stmt->execute();
 
$sql = "DELETE FROM modx_lightforms_customerquotes WHERE id='$d'";   
 $stmt = $modx->prepare($sql);
$stmt->execute();

$deletemessage='<h3>Quote Deleted!</h3>';
    
}



$x=0;
foreach ($qty as $v) {

$x = $x + $v; 

}

if(!$customer || !$project || !$quoteref) {

 $message = '<span style="color:red">Please fill in all the fields</span>';

  

} elseif($x==0) {
    
 $message = '<span style="color:red">No product quantities. Close this box, add some quantities and then come back here and generate the quote.</span>';

  
    
    
} else {

$createdon=time();
$modxuser = $modx->getUser();
    $userid = $modxuser->get("id");


$sql = "INSERT INTO modx_lightforms_customerquotes (customer, project, quoteref, createdon, createdby) VALUES ('$customer','$project','$quoteref','$createdon','$userid')";

$stmt = $modx->prepare($sql);
$stmt->execute();

$sql = "SELECT MAX( id ) FROM modx_lightforms_customerquotes";

foreach ($modx->query($sql) as $row) {
    $lastid = $row[0];
}

$i = 0;


foreach ($qty as $v) {

    if ($v > 0) {
        $resourceid = $_POST["resourceid"][$i];

        $page = $modx->getObject("modResource", $resourceid);
        $unix_ts = strtotime($page->get("createdon"));
        $imagefile =
            "/ws/configurator/products/" . $page->getTVValue("TS_TechPhoto");

        $svgfile =
            "/ws/configurator/overlays/" .
            date("m\-y", $unix_ts) .
            "/" .
            $page->getTVValue("TS_SvgFile");

        $parts = explode(",", $page->getTVValue("TS_PartColor"));
        foreach ($parts as $part) {
            $partitems .= str_replace("|", ": ", $part) . "\n";
        }

        $outputs = explode("-", $page->getTVValue("TS_Output"));

        if ($outputs[0]) {
            $directoutput = "Direct Output: " . $outputs[0]."\n";
        }

        if ($outputs[1]) {
            $indirectoutput = "Indirect Output: " . $outputs[1]."\n";
        }
        if($page->getTVValue("TS_NominalSize")) {
          $shapesize = "Size: ".$page->getTVValue("TS_NominalSize") . "\n" ?? "";    
            
        }
       

if($page->get("description")) {
   $description = "Mount: ".$page->get("description") . "\n" ?? "";    
    
}

     

        $productname = $page->get("longtitle");
        
        if($page->get("longtitle")) {
            
           $productname = $page->get("longtitle");   
            
        }
        
        if($page->getTVValue("TS_Distribution")) {
    
        $distribution = " (".$page->getTVValue("TS_Distribution").")";
    
        }
    if($productname) {
       $productname = $productname . $distribution ."\n";  
        
    }
    
       
        
        if($page->getTVValue("TS_cct")) {
            
    $color.= "Colour: " . $page->getTVValue("TS_cct");       
        }
        
        if($page->getTVValue("TS_cri")) {
            
    $color.= " CRI " . $page->getTVValue("TS_cri");       
        }
        
        if($color) {
            
        $color= $color."\n";    
        }
  unset($color);      
        if($page->getTVValue("TS_Driver")) {
         $driver = "Driver/Dimming: " . $page->getTVValue("TS_Driver") . "\n";   
            
        }
        if($page->getTVValue("TS_Controls")) {
         $controls =
            "Controls: ".str_replace(",", " + ", $page->getTVValue("TS_Controls")) . "\n";    
            
        }
        if($page->getTVValue("TS_ProfileDimensions")) {
             $dimensions =
            "Dimensions: " . $page->getTVValue("TS_ProfileDimensions") . "\n";  
            
        }
       
     if($page->get("pagetitle")) {
     
        $speccode = "Spec Code: " . $page->get("pagetitle");    
         
     }
     

        $description =
            $productname . $shapesize .
            $directoutput .
            $indirectoutput .
            $description .
            $color .
            $driver .
            $controls .
            $dimensions .
            $speccode;

        $priceid = $_POST["priceid"][$i];

        $sql = "SELECT type,totalsell FROM modx_lightforms_prices WHERE id='$priceid'";
        foreach ($modx->query($sql) as $row) {
            $type = $row[0];
            $unitprice = $row[1];
        }

        $netprice = $v * $unitprice;

        $sql = "INSERT INTO modx_lightforms_quotes (resourceid, customerid, priceid, type, description, colors, qty, unitprice, netprice, image, svg) VALUES ('$resourceid','$lastid','$priceid','$type','$description','$partitems','$v','$unitprice','$netprice','$imagefile','$svgfile')";

        $stmt = $modx->prepare($sql);
        $stmt->execute();
        unset($partitems);
    }

    $i++;
}   
    $done=1;
 $message = '<span style="color:green">Quote generated - Close this box and go to the Quotes section to view, print or edit.</span>';   
}     

$data["done"] = $done ?? "";
$data["message"] = $message ?? ""; 
$data["deletemessage"] = $deletemessage ?? "";  

$output = [$data];
echo json_encode($output);