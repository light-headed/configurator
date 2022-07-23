<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/config.core.php";
require_once MODX_CORE_PATH . "model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("web");
$modx->getService("error", "error.modError", "", "");
date_default_timezone_set("Europe/London");
$user = $modx->getUser();
$userid = $user->get("id");

if($_POST['refresh']==1){
    
    $now=time();

$sql = "INSERT INTO modx_lightforms_refresh (lastrefresh) VALUES ('$now')";

$stmt = $modx->prepare($sql);
$stmt->execute();
}
    
$pageNum=$_POST['pageNum'];    

$perPage=$_POST['perPage'];

if($_POST['del'] == 1) {
$remove=$_POST['remove'];

foreach($remove as $r) {

$sql="DELETE FROM modx_lightforms_prices WHERE contentid = '$r'";

$stmt = $modx->prepare($sql);
$stmt->execute();    
    
}

}

if($_POST['type']) {
    
$type=$_POST['type'];    
    
} else {
    
$type='%';    
}


if($_POST['cct']) {
    
$cct=$_POST['cct'];    
    
} else {
    
$cct='%';    
}

if($_POST['output']) {
    
$output=$_POST['output'];    
    
} else {
    
$output='%';    
}

if($_POST['shapesize']) {
    
$shapesize=$_POST['shapesize'];    
    
} else {
    
$shapesize='%';    
}

if($_POST['driver']) {
    
$driver=$_POST['driver'];    
    
} else {
    
$driver='%';    
}

if($_POST['extras']) {
    
$extras=$_POST['extras'];    
    
} else {
    
$extras='%';    
}
if($_POST['priceband']) {
    
$priceband=$_POST['priceband'];    
    
} else {
    
$priceband='%';    
}

if($_POST['project']) {
    
$project=$_POST['project'];    
    
} else {
    
$project='%';    
}


$timediff=0;
$timefilter=$_POST['timefilter'];

if($timefilter == 1) {
 
$timediff=strtotime("-1 hours");    
    
}

if($timefilter == 24) {
 
$timediff=strtotime("-24 hours");    
    
}

if($timefilter == 7) {
 
$timediff=strtotime("-7 days");    
    
}

if($timefilter == 30) {
 
$timediff=strtotime("-30 days");    
    
}

if($timefilter == 90) {
 
$timediff=strtotime("-90 days");    
    
}

if($timefilter == "older") {
 
$timediff=strtotime("+90 days");    

}



$sql = "SELECT COUNT(*) AS id FROM modx_lightforms_prices WHERE type LIKE '$type' AND cct LIKE '$cct' AND output LIKE '$output' AND shapesize LIKE '$shapesize' AND driver LIKE '$driver' AND extras LIKE '$extras' AND colorband LIKE '$priceband' AND project LIKE '$project' AND createdon > $timediff";
$result = $modx->query($sql);

$row = $result->fetch(PDO::FETCH_ASSOC);
$numResults = $row['id'];

$numPages=ceil($numResults/$perPage);

if($pageNum > $numPages) {
    
$pageNum=$numPages;

    
}

if($pageNum < 1) {
    
$pageNum=1;

    
}

if($_POST['offset'] == 1) {
 
    $offset = 0;
    $perPage = 10;
    $pageNum=1;
    
} else {
 $offset=($pageNum*$perPage) - $perPage;   
   
}    

$offset=($pageNum*$perPage) - $perPage;     

$numStart = $offset +1;

$stmt = $modx->prepare(
    "SELECT speccode,type,shapesize,distribution,cct,cri,output,driver,extras,totalsell,contentid,image,svg,createdon,costmultiplier,project,id,resourceid FROM modx_lightforms_prices WHERE type LIKE '$type' AND cct LIKE '$cct' AND output LIKE '$output' AND shapesize LIKE '$shapesize' AND driver LIKE '$driver' AND extras LIKE '$extras' AND colorband LIKE '$priceband' AND project LIKE '$project' AND createdon > '$timediff'  ORDER BY createdon DESC  LIMIT $offset,$perPage");

$results = [];

if ($stmt->execute()) {
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach($results as $v) {

        $tr .=
            '
        <tr><td><input type="checkbox" name="remove[]" value="'.$v["contentid"].'"></td>
        <td><a href="/data-sheets/Spec-'.$v["speccode"].'.pdf" target="_blank">
       ' .
            $v["speccode"] .
            "</a></td><td>".$v['project']."<td>" .
             $v['type'] .
            "</td><td>" .
            $v['shapesize'] .
            "</td><td>" .
            $v['distribution'] .
            "</td><td>".$v['cct']."</td><td>".$v['cri']."</td><td>" . $v['output'] . "</td><td>" . $v['driver'] . "</td><td>" . str_replace(","," + ",$v['extras']) . '</td><td><a href="/data-sheets/Price-'.$v['speccode'].'.pdf" target="_blank">' .
            $v['totalsell'] .
            '</a></td><td>'.$v['costmultiplier'].'</td><td><div class="show-product"><div class="product-tooltip" style="width:200px; height:200px; background: url(/ws/configurator/products/'.$v['image'].'); background-size: 200px 200px; background-repeat: no-repeat;">
            <div class="product-tooltip-overlay"><img src="/ws/configurator/overlays/06-22/'.$v['svg'].'.svg" alt=""></div>
            </div><img src="/lf/images/image-icon.png" alt="" style="width:30px"></div></td>' .
            '<td><input type="number" style="width:50px;" class="product-qty" data-priceid="'.$v['id'].'" name="qty[]"></td><td><input type="hidden" name="speccode[]" value=""><input type="hidden" name="priceid[]" value="'.$v['id'].'"><input type="hidden" name="resourceid[]" value="'.$v['resourceid'].'"></td>
        
        </tr>         
        ';
$tq.='<input type="hidden" name="qty[]" data-spec="'.$v['id'].'" value="0">
<input type="hidden" name="priceid[]" value="'.$v['id'].'">
<input type="hidden" name="resourceid[]" value="'.$v['resourceid'].'">';

}
if($numResults == 0) {
$filterError='<div style="color:red; margin:50px 0 50px 0;">No results found - too many filters. </div>';
}

$pageResults = count($results);
$numEnd = $offset+$pageResults;
$data["quoteTable"] = $tq ?? "";
$data["pricingTable"] = $tr ?? "";
$data["pricingCount"] = $count ?? "";
$data["numpages"] = $numPages ?? "";
$data["offset"] = $offset ?? "";
$data["perpage"] = $perPage ?? "";
$data["pagenum"] = $pageNum ?? "";
$data["numresults"] = $numResults ?? "";
//$data["numresults"] = $pageResults ?? "";
$data["numstart"] = $numStart ?? "";
$data["numend"] = $numEnd ?? "";
$data["filtererror"] = $filterError ?? "";

$output = [$data];
echo json_encode($output);

