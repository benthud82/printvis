REM update the case volume table hist_casevol
D:\xampp\php\php.exe "D:\xampp\htdocs\printvis\datapull\historicalcasevol.php"
D:\xampp\php\php.exe "D:\xampp\htdocs\printvis\datapull\casebatchstarttime_hist_update.php"

REM update loose volume table hist_loosevol_summary
D:\xampp\php\php.exe "D:\xampp\htdocs\printvis\datapull\historicalloosevol.php"

REM call rsript to forecast cases
"C:\Program Files\R\R-3.5.0\bin\Rscript.exe" D:\xampp\htdocs\printvis\datapull\RDir\forecast_casepick\boosted_tree.R

REM call rsript to forecast loose volume
"C:\Program Files\R\R-3.5.0\bin\Rscript.exe" D:\xampp\htdocs\printvis\datapull\RDir\forecast_whselines\whselines_byhour.R


REM update the case forecast hours table
D:\xampp\php\php.exe "D:\xampp\htdocs\printvis\datapull\caseforecasthours.php"

