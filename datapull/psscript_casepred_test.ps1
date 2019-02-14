Set-Location -Path D:\xampp\htdocs\printvis\datapull
DO
{
[int]$hour = get-date -format HH
cmd.exe /c 'update_casepred_test.bat' 
} while ($hour -gt 2 -and $hour -lt 23)
