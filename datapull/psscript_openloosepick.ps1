Set-Location -Path D:\xampp\htdocs\printvis\datapull
[int]$hour = get-date -format HH
If($hour -lt 1 -or $hour -gt 23){ 
exit
stop-process -Id $PID
}
Else{
cmd.exe /c 'update_loosepickpred_open.bat' 
."D:\xampp\htdocs\printvis\datapull\psscript_openloosepick.ps1"
}