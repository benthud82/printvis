Set-Location -Path D:\xampp\htdocs\printvis\datapull
DO
{
[int]$hour = get-date -format HH
Start-Sleep -Seconds 15
cmd.exe /c 'update_cartputpred.bat' 
cmd.exe /c 'update_breaklunch_wcs.bat' 
} while ($hour -gt 2 -and $hour -lt 23)
