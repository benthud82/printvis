<?php

if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}

function _boxprep($boxsize, $whse) {
    switch ($whse) {
        case 6:
            //Denver Times
            $boxheadertime = .465915;
            switch ($boxsize) {
                case '#E2':
                    $boxtime = .04908;
                    break;
                case '#G3':
                    $boxtime = .056658;
                    break;
                case '# 2':
                    $boxtime = .049;
                    break;
                case '# 4':
                    $boxtime = .06227;
                    break;
                case '# 5':
                    $boxtime = .07892;
                    break;
                case '# 9':
                    $boxtime = .10632;
                    break;
                case '#12':
                    $boxtime = .144408;
                    break;
                default:
                    $boxtime = 'error';
                    break;
            }
            break;
        case 7:
            //Dallas Times
            $boxheadertime = .636;
            switch ($boxsize) {
                case '#E2':
                    $boxtime = .049;
                    break;
                case '#G3':
                    $boxtime = .0564;
                    break;
                case '# 2':
                    $boxtime = .07;
                    break;
                case '# 4':
                    $boxtime = .069;
                    break;
                case '# 5':
                    $boxtime = .0879;
                    break;
                case '# 9':
                    $boxtime = .11;
                    break;
                case '#12':
                    $boxtime = .136;
                    break;
                default:
                    $boxtime = 'error';
                    break;
            }
            break;
        case 3:
            //Sparks Times
            $boxheadertime = .5642;
            switch ($boxsize) {
                case '#E2':
                    $boxtime = .1877;
                    break;
                case '#G3':
                    $boxtime = .1729;
                    break;
                case '# 2':
                    $boxtime = .1877;
                    break;
                case '# 4':
                    $boxtime = .2212;
                    break;
                case '# 5':
                    $boxtime = .2356;
                    break;
                case '# 9':
                    $boxtime = .1486;
                    break;
                case '#12':
                    $boxtime = .2126;
                    break;
                default:
                    $boxtime = 'error';
                    break;
            }
            break;
        case 9:
            //Jax Times
            $boxheadertime = .577;
            switch ($boxsize) {
                case '#E2':
                    $boxtime = .023;
                    break;
                case '#G3':
                    $boxtime = .036;
                    break;
                case '# 2':
                    $boxtime = .0347;
                    break;
                case '# 4':
                    $boxtime = .0347;
                    break;
                case '# 5':
                    $boxtime = .056;
                    break;
                case '# 9':
                    $boxtime = .027;
                    break;
                case '#12':
                    $boxtime = .069;
                    break;
                default:
                    $boxtime = 'error';
                    break;
            }
            break;
        case 2:
            //Indy Times
            $boxheadertime = .489;
            switch ($boxsize) {
                case '#E2':
                    $boxtime = .059;
                    break;
                case '#G3':
                    $boxtime = .071;
                    break;
                case '# 2':
                    $boxtime = .0;
                    break;
                case '# 4':
                    $boxtime = .069;
                    break;
                case '# 5':
                    $boxtime = .072;
                    break;
                case '# 9':
                    $boxtime = .123;
                    break;
                case '#12':
                    $boxtime = .128;
                    break;
                default:
                    $boxtime = 'error';
                    break;
            }
            break;
        default:
            break;
    }



    $boxtotaltime = $boxtime + $boxheadertime;

    return $boxtotaltime;
}

function _contentlisttime($speedpack, $whse) {
    switch ($whse) {
        case 6:
            //Denver Times
            if ($speedpack == 'Y') {
                $time_contentlist = .04229;  //per line for speed pack
            } else {
                $time_contentlist = .0919;  //per line for audit pack
            }
            break;
        case 7:
            //Dallas Times
            if ($speedpack == 'Y') {
                $time_contentlist = .0235;  //per line for speed pack
            } else {
                $time_contentlist = .0919;  //per line for audit pack
            }
            break;
        case 3:
            //Sparks Times
            if ($speedpack == 'Y') {
                $time_contentlist = .0111;  //per line for speed pack
            } else {
                $time_contentlist = .272;  //per line for audit pack
            }
            break;
        case 9:
            //Sparks Times
            if ($speedpack == 'Y') {
                $time_contentlist = .018;  //per line for speed pack
            } else {
                $time_contentlist = .07;  //per line for audit pack
            }
            break;
        default:
            break;
    }
    return $time_contentlist;
}

function _1yydddtogregdate($date) {
    $a1 = substr($date, 2, 3);
    $a2 = substr($date, 0, 2);
    $converteddate = date("Y-m-d", mktime(0, 0, 0, 1, $a1, $a2));

    return $converteddate;
}

function _equipestimator($LINE_COUNT, $PRIM_PICKS, $BULK_PICKS, $PTB_PICKS, $PALLET_PICKS, $HALFDECK_PICKS, $OTHER_PICKS, $FIRST_LOC, $LAST_LOC, $whsesel) {

    $primpercent = $PRIM_PICKS / $LINE_COUNT;
    $bulkpercent = $BULK_PICKS / $LINE_COUNT;
    $ptbpercent = $PTB_PICKS / $LINE_COUNT;
    $palletpercent = $PALLET_PICKS / $LINE_COUNT;
    $halfdeckpercent = $HALFDECK_PICKS / $LINE_COUNT;
    $OTHER_PICKS = $LINE_COUNT - $BULK_PICKS - $PTB_PICKS - $PALLET_PICKS - $HALFDECK_PICKS;
    $otherpercent = $OTHER_PICKS / $LINE_COUNT;

    //Beltline must be 98%


    $maxpercent = max($bulkpercent, $palletpercent, $halfdeckpercent, $otherpercent);

    if ($whsesel == 6 && substr($FIRST_LOC, 0, 2) == 'W9') {
        $equiptype = 'BULK';
        return $equiptype;
    }

    if ($FIRST_LOC === $LAST_LOC) {
        $equiptype = 'REACH';
        return $equiptype;
    } elseif ($ptbpercent >= .98) {
        $equiptype = 'BELTLINE';
        return $equiptype;
    } else {

        switch ($maxpercent) {
            case $bulkpercent:
                $equiptype = 'PALLETJACK';
                break;
            case $ptbpercent:
                $equiptype = 'BELTLINE';
                break;
            case $palletpercent:
                $equiptype = 'PALLETJACK';
                break;
            case $halfdeckpercent:
                if ($whsesel == 7) {
                    $equiptype = 'PALLETJACK';
                } else {
                    $equiptype = 'ORDERPICKER';
                }
                break;
            case $otherpercent:
                $equiptype = 'ORDERPICKER';
                break;
            default:
                $equiptype = 'OTHER';
                break;
        }
    }
    return $equiptype;
}

function _convertToHoursMins($time, $format = '%02d:%02d') {
    if ($time < 0) {

        $time = $time * -1;
        $hours = floor($time / 60);
        $minutes = ($time % 60);
        return '-' . sprintf($format, $hours, $minutes);
    }
    $hours = floor($time / 60);
    $minutes = ($time % 60);
    return sprintf($format, $hours, $minutes);
}

function _convertMinToMilliSec($time) {
    if ($time < 0) {
        return 0;
    }
    $minutes = floor($time);
    $seconds = ($time - $minutes) * 60;
    return ($minutes * 60 + $seconds) * 1000;
}

function _gregdatetoyyddd($convertdate) {
    $startyear = date('y', strtotime($convertdate));
    $startday = date('z', strtotime($convertdate)) + 1;
    if ($startday < 10) {
        $startday = '00' . $startday;
    } else if ($startday < 100) {
        $startday = '0' . $startday;
    }
    $datej = intval($startyear . $startday);
    return $datej;
}

function _jdatetomysqldate($jdate) {
    $year = "20" . substr($jdate, 0, 2);
    $days = substr($jdate, 2, 3);

    $ts = mktime(0, 0, 0, 1, $days, $year);
    $mydate = date('Y-m-d', $ts);
    return $mydate;
}

function _WeekOfMonth($date, $rollover) {
    $cut = substr($date, 0, 8);
    $daylen = 86400;

    $timestamp = strtotime($date);
    $first = strtotime($cut . "00");
    $elapsed = ($timestamp - $first) / $daylen;

    $weeks = 1;

    for ($i = 1; $i <= $elapsed; $i++) {
        $dayfind = $cut . (strlen($i) < 2 ? '0' . $i : $i);
        $daytimestamp = strtotime($dayfind);

        $day = strtolower(date("l", $daytimestamp));

        if ($day == strtolower($rollover))
            $weeks ++;
    }

    return $weeks;
}

function _printdatepredictor($converted_recdate, $hist_rechrmin, $cutoff_time, $hist_shipzone, $hist_shipclass, $cutoff_group) {
    $predicted_printdate = $converted_recdate;  //default to order being printed today
    //if received date is a weekend, then add a business day
    $dayofweek = date('w', strtotime($converted_recdate));
    if ($dayofweek == 6) {
        $predicted_printdate = date('Y-m-d', strtotime($converted_recdate . ' + 2 day'));
        return $predicted_printdate;
    } elseif ($dayofweek == 0) {
        $predicted_printdate = date('Y-m-d', strtotime($converted_recdate . ' + 1 day'));
        return $predicted_printdate;
    }

    if ($hist_rechrmin > $cutoff_time) {
        //call function to add one business day to printdate
        $predicted_printdate = _addbusinessday($converted_recdate);
    }
    return $predicted_printdate;
}

function _addbusinessday($converted_recdate) {

    $newdate = date('Y-m-d', strtotime($converted_recdate . ' + 1 day'));
    $dayofweek = date('w', strtotime($newdate));


    if ($dayofweek == 6) {
        $newdate = date('Y-m-d', strtotime($newdate . ' + 2 day'));
    } elseif ($dayofweek == 0) {
        $newdate = date('Y-m-d', strtotime($newdate . ' + 1 day'));
    }

    return $newdate;
}

function _packtype($PBICEF, $TRUEHAZ, $speedpack) {
    //determines type of packing based off batch characteristics
    if ($PBICEF == 'I') {
        $packfunction = 'PACKICE';
        return $packfunction;
    }
    if ($TRUEHAZ > 0) {
        $packfunction = 'PACKHAZ';
        return $packfunction;
    }
    if ($speedpack == 'Y') {
        $packfunction = 'PACKSPEED';
        return $packfunction;
    }

    $packfunction = 'PACKAUDIT';
    return $packfunction;
}

function _cellcolor($projected_compl_min, $CART_TIME) {
    $effeciency = $projected_compl_min / $CART_TIME;
    if ($effeciency > 1) {
        $cellcolor = 'RED';
        return $cellcolor;
    } else {
        $cellcolor = 'GREEN';
        return $cellcolor;
    }
}

function _casetravel($previousloc_X, $previousloc_Z, $previousaisle_X, $previousaisle_Z, $currentaisle_X, $currentaisle_Z, $FIRSTLOC_X, $FIRSTLOC_Z) {
    //calculates the outer aisle travel time from last location of previous record to first location of current record

    $outeraisle = abs($previousloc_X - $previousaisle_X) + abs($previousloc_Z - $previousaisle_Z);  //previous last location to previous parking spot
    $outeraisle += abs($previousaisle_X - $currentaisle_X) + abs($previousaisle_Z - $currentaisle_Z); //previous parking spot to current parking spot
    $outeraisle += abs($currentaisle_X - $FIRSTLOC_X) + abs($currentaisle_Z - $FIRSTLOC_Z);

    return $outeraisle;
}

function _picktype($loctype, $tier) {
    $picktype = 'PICKSTD';
    switch ($loctype) {
        case 'RI':
            $picktype = 'PICKICE';
            return $picktype;
            break;
        case 'RS':
            $picktype = 'PICKICE';
            return $picktype;
            break;
        default:
            break;
    }

    if ($tier == 'L06') {
        $picktype = 'PICKMEZ';
    }
    return $picktype;
}

function _diffnext($currloc, $nextloc) {
    if ($currloc != $nextloc) {
        $diff_next = 1;
    } else {
        $diff_next = 0;
    }
    return $diff_next;
}

function _diffprev($currloc, $prevloc) {
    if ($currloc != $prevloc) {
        $diff_prev = 1;
    } else {
        $diff_prev = 0;
    }
    return $diff_prev;
}

function _logequip($logequip_totlines, $logequip_putflow, $logequip_putcart, $logequip_putdogp, $logequip_putturr, $logequip_putordp, $logequip_recvol) {
    //default to CRT
    $log_equip = 'CRT';

    //cubic cm cutoff for tote putaway batch
    $toteavg = 1200;
    if ($logequip_recvol < $toteavg) {
        $log_equip = 'TOT';
        return $log_equip;
    }
    $perc_flow = $logequip_putflow / $logequip_totlines;
    $perc_cart = $logequip_putcart / $logequip_totlines;
    $perc_dogp = $logequip_putdogp / $logequip_totlines;
    $perc_turr = $logequip_putturr / $logequip_totlines;
    $perc_ordp = $logequip_putordp / $logequip_totlines;

    $maxperc = max($perc_flow, $perc_cart, $perc_dogp, $perc_turr, $perc_ordp);

    switch ($maxperc) {
        case $perc_flow:
            $log_equip = 'FLW';
            break;
        case $perc_cart:
            $log_equip = 'CRT';
            break;
        case $perc_turr:
            $log_equip = 'TUR';
            break;
        case $perc_ordp:
            $log_equip = 'ORD';
            break;
        case $perc_dogp:
            $log_equip = 'DOG';
            break;
    }
    return $log_equip;
}

function pdoMultiInsert($tableName, $schema, $data, $pdoObject, $arraychunk) {

    //Get a list of column names to use in the SQL statement.
    $columnNames = array_keys($data[0]);

    $data_chunked = array_chunk($data, $arraychunk); //chunk the large array into smaller pieces to prevent memory over allocation
    //Loop through our $data array.
    foreach ($data_chunked as $key => $value) {
        //Will contain SQL snippets.
        $rowsSQL = array();

        //Will contain the values that we need to bind.
        $toBind = array();

        foreach ($value as $arrayIndex => $row) {
            $params = array();
            foreach ($row as $columnName => $columnValue) {
                $param = ":" . $columnName . $arrayIndex;
                $params[] = $param;
                if (empty($columnValue)) {
                    $toBind[$param] = "0";
                } else {
                    $toBind[$param] = $columnValue;
                }
            }
            $rowsSQL[] = "(" . implode(", ", $params) . ")";
        }

        //Construct our SQL statement
        $sql = "INSERT IGNORE INTO `$schema` . `$tableName` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

        //Prepare our PDO statement.
        $pdoStatement = $pdoObject->prepare($sql);

        //Bind our values.
        foreach ($toBind as $param => $val) {
            $pdoStatement->bindValue($param, $val);
        }

        //Execute our statement (i.e. insert the data).
        $pdoStatement->execute();
    }
    return;
}

function pdoMultiInsert_duplicate($tableName, $schema, $data, $pdoObject, $arraychunk) {

    //Get a list of column names to use in the SQL statement.
    $columnNames = array_keys($data[0]);

    $data_chunked = array_chunk($data, $arraychunk); //chunk the large array into smaller pieces to prevent memory over allocation
    //Loop through our $data array.
    foreach ($data_chunked as $key => $value) {
        //Will contain SQL snippets.
        $rowsSQL = array();
        $updateCols = array();
        //Will contain the values that we need to bind.
        $toBind = array();

        foreach ($value as $arrayIndex => $row) {
            $params = array();
            foreach ($row as $columnName => $columnValue) {
                $param = ":" . $columnName . $arrayIndex;
                $params[] = $param;
                if (empty($columnValue)) {
                    $toBind[$param] = "0";
                    $updateCols[] = $columnName . " = VALUES($columnName)";
                } else {
                    $toBind[$param] = $columnValue;
                    $updateCols[] = $columnName . " = VALUES($columnName)";
                }
            }
            $rowsSQL[] = "(" . implode(", ", $params) . ")";
            $onDup = implode(', ', $updateCols);
        }


//        //setup the ON DUPLICATE column names
//        $updateCols = array();
//        foreach ($colNames as $curCol) {
//            $updateCols[] = $curCol . " = VALUES($curCol)";
//        }
        //Construct our SQL statement
        $sql = "INSERT INTO `$schema` . `$tableName` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL) . " ON DUPLICATE KEY UPDATE $onDup";

        //Prepare our PDO statement.
        $pdoStatement = $pdoObject->prepare($sql);

        //Bind our values.
        foreach ($toBind as $param => $val) {
            $pdoStatement->bindValue($param, $val);
        }

        //Execute our statement (i.e. insert the data).
        $pdoStatement->execute();
    }
    return;
}