Set-Location -Path D:\xampp\htdocs\printvis\datapull
[int]$hour = get-date -format HH
If($hour -lt 1 -or $hour -gt 23){ 
exit
stop-process -Id $PID
}
Else{
Start-Sleep -s 60
cmd.exe /c 'caserefresh.bat' 
."D:\xampp\htdocs\printvis\datapull\psscript_caserefresh.ps1"
}