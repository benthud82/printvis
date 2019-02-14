
<?php
switch ($whsesel) {
    case 7:
        date_default_timezone_set('America/Chicago');
        $timezone = 'America/Chicago';
        $mintosubtract = 60;
        break;
    case 2:
        date_default_timezone_set('America/New_York');
        $timezone = 'America/New_York';
        $mintosubtract = 0;
        break;
    case 6:
        date_default_timezone_set('America/New_York');
        $timezone = 'America/New_York';
        $mintosubtract = 0;
        break;
    case 3:
        date_default_timezone_set('America/Los_Angeles');
        $timezone = 'America/Los_Angeles';
        $mintosubtract = 180;
        break;
    case 9:
        date_default_timezone_set('America/New_York');
        $timezone = 'America/New_York';
        $mintosubtract = 0;
        break;

    default:
        break;
}