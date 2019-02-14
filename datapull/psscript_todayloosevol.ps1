Set-Location -Path D:\xampp\htdocs\printvis\datapull

[int]$hour = get-date -format HH
while (($hour -lt 3) -or !($hour -gt 20)) {
    [int]$hour = get-date -format HH
	Start-Sleep -s 60
	cmd.exe /c 'todayloosevol.bat' 
}
