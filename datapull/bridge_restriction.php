<?php

if (isset($whsesel)) {
    $whse = $whsesel;
}
switch ($whse) {
    case 7:
        $bridge_restriction_array = array(3600);
        $bridge_prev_coordinate = $previousaisleB_X;
        $bridge_curr_coordianate = $BRIDGESTART_X;
        break;
    case 3.2:
        $bridge_restriction_array = array(10200);
        $bridge_prev_coordinate = $previousaisleB_Z;
        $bridge_curr_coordianate = $BRIDGESTART_Z;
        break;

    default:
        $bridge_restriction_array = array();
        break;
}
