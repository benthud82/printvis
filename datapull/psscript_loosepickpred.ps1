Set-Location -Path D:\xampp\htdocs\printvis\datapull

[int]$hour = get-date -format HH
while (($hour -lt 3) -or !($hour -gt 23)) {
    [int]$hour = get-date -format HH
	Start-Sleep -s 60
	cmd.exe /c 'update_loosepickpred.bat' 
}
