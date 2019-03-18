<?php

$lpsql = $conn1->prepare("SELECT 
                                                        complaint_detail.*, SALESPLAN, SALESPLAN_DESC
                                                    FROM
                                                        custaudit.complaint_detail
                                                           LEFT JOIN
                                                        custaudit.salesplan ON SHIPTONUM = SHIPTO
                                                    WHERE
                                                        LPNUM = $var_sqldata
                                                    LIMIT 1");
$lpsql->execute();
$lparray = $lpsql->fetchAll(pdo::FETCH_ASSOC);





