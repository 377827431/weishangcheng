<?php 
/*
$toolbar_node_list = \Common\Common\Permission::getCurrentAccessList();
$toolbar_prev_group = 0;
$i = 0;
foreach ($toolbar_node_list as $btn) {
    if($i == 0){
        $html .= '<div class="btn-group">';
    }else if ($toolbar_prev_group != $btn["group"]) {
        $html .= '</div><div class="btn-group">';
    }
    $toolbar_prev_group = $btn["group"];
    
    $html .= '<button type="button" data-name="' . $btn["name"] . '" class="btn btn-default'.($btn["visible"] ? '' : ' hide').'" data-event-type="' . $btn["event_type"] . '" data-event-value="' . $btn["event_value"] . '" data-target="' . $btn["target"] . '"><i class="' . $btn["icon"] . '"></i> ' . $btn["title"] . '</button>';
    
    $i++;
}
$html .= '</div>';

echo $html;
*/
?>