<?php

switch ($whsesel) {
    case 6:
        //Times for Denver
        $time_cartprep = 1.8121517;  //per cart
        $time_short = .1175;  //per short
        $time_scanLP = 0.04633;  //per LP
        $time_unit = .0547;  //per unit
        $time_line = .0412;  //per line
        $time_expirycheck = .0564;   //per expiry date
        $time_lotcheck = .0564;  //per lot#
        $time_sn = .0564;  //per SN
        $time_box24 = .14205502;  //per tote
        $time_cartcomplete = 1.1333; //per cart
        $time_tempsticker = 0;
        $time_odsticker = 0;
        $time_sqsticker = 0;

        break;
    case 7:
        //Times for Dallas
        $time_cartprep = 1.627;  //per cart
        $time_short = .087;  //per short
        $time_scanLP = .0463;  //per LP
        $time_unit = .06469;  //per unit
        $time_line = .0547;  //per line
        $time_expirycheck = .001;   //per expiry date
        $time_lotcheck = .0767;  //per lot#
        $time_sn = .497;  //per SN
        $time_box24 = .008;  //per tote
        $time_cartcomplete = .4822; //per cart
        $time_tempsticker = .054;
        $time_odsticker = .0668;
        $time_sqsticker = .0426;
        break;
    case 3:

        //Times are same as Denver.  Need to adust for Dallas once actual times are calculated
        $time_cartprep = 1.0657;  //per cart
        $time_short = 1.6085;  //per short
        $time_scanLP = .0463;  //per LP
        $time_unit = .0709;  //per unit
        $time_line = .0111;  //per line
        $time_expirycheck = .0412;   //per expiry date
        $time_lotcheck = .1043;  //per lot#
        $time_sn = .414;  //per SN
        $time_box24 = .0134;  //per tote
        $time_cartcomplete = .5342; //per cart
        $time_tempsticker = 0;
        $time_odsticker = 0;
        $time_sqsticker = 0;
        break;
    case 9:

        $time_cartprep = 1.6;  //per cart
        $time_short = .357;  //per short
        $time_scanLP = .463;  //per LP
        $time_unit = .065;  //per unit
        $time_line = .009;  //per line
        $time_expirycheck = .035;   //per expiry date
        $time_lotcheck = .035;  //per lot#
        $time_sn = .2893;  //per SN
        $time_box24 = .004;  //per tote
        $time_cartcomplete = .475; //per cart
        $time_tempsticker = 0;
        $time_odsticker = .176;
        $time_sqsticker = .176;
        break;

    case 2:
        $time_cartprep = 1.596;  //per cart
        $time_short = .547;  //per short
        $time_scanLP = .463;  //per LP
        $time_unit = .0719;  //per unit
        $time_line = .1203;  //per line
        $time_expirycheck = .0006;   //per expiry date
        $time_lotcheck = .009;  //per lot#
        $time_sn = .664;  //per SN
        $time_box24 = .0056;  //per tote
        $time_cartcomplete = 1.095; //per cart
        $time_tempsticker = 0;
        $time_odsticker = 0;
        $time_sqsticker = 0;
        break;

    default:
        break;
}
