@echo off
setlocal
pushd "%~dp0"

REM ===== LOCAL nightly case/loose forecast chain =====
REM Paths point at the local XAMPP checkout (C:\Users\Bentley.Hudson\xampp\...).
REM Case-pick forecast runs the Python model (forecast_casepick.py); the R
REM boosted_tree.R does NOT run locally (R xgboost/caret/RMySQL packages absent).
REM Optional first argument: forecast date YYYY-MM-DD. Omit for normal today run.

set FORECAST_DATE=%~1
set FORECAST_ARGS=
set CASEHOURS_ARGS=
set SCORE_ARGS=

if not "%FORECAST_DATE%"=="" (
    set FORECAST_ARGS=--as_of_date %FORECAST_DATE%
    set CASEHOURS_ARGS=--date=%FORECAST_DATE%
    set SCORE_ARGS=--date %FORECAST_DATE%
    echo Running case forecast for explicit date %FORECAST_DATE%.
) else (
    echo Running case forecast for today's date.
)

REM update the case volume table hist_casevol
"C:\Users\Bentley.Hudson\xampp\php\php.exe" "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\historicalcasevol.php"
if errorlevel 1 goto fail
"C:\Users\Bentley.Hudson\xampp\php\php.exe" "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\casebatchstarttime_hist_update.php"
if errorlevel 1 goto fail

REM update loose volume table hist_loosevol_summary
"C:\Users\Bentley.Hudson\xampp\php\php.exe" "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\historicalloosevol.php"
if errorlevel 1 echo WARNING: historicalloosevol.php failed; continuing case forecast test.

REM call python to forecast cases (replaces boosted_tree.R for local)
python "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\RDir\forecast_casepick\forecast_casepick.py" %FORECAST_ARGS%
if errorlevel 1 goto fail

REM call rscript to forecast loose volume (local R 4.6.0; needs R packages installed)
"C:\Users\Bentley.Hudson\AppData\Local\Programs\R\R-4.6.0\bin\Rscript.exe" "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\RDir\forecast_whselines\whselines_byhour.R"
if errorlevel 1 echo WARNING: loose R forecast failed; continuing case forecast test.

REM update the case forecast hours table
"C:\Users\Bentley.Hudson\xampp\php\php.exe" "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\caseforecasthours.php" %CASEHOURS_ARGS%
if errorlevel 1 echo WARNING: caseforecasthours.php failed; line/cube accuracy scoring can still continue.

if not "%SCORE_ARGS%"=="" (
    REM Print forecast-vs-actual comparison for the explicit test date.
    python "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\RDir\forecast_casepick\casepick_score_forecast_day.py" %SCORE_ARGS% --out "C:\Users\Bentley.Hudson\xampp\htdocs\printvis\datapull\RDir\forecast_casepick\casepick_accuracy_%FORECAST_DATE%.txt"
    if errorlevel 1 goto fail
)

popd
endlocal
exit /b 0

:fail
echo ERROR: update_histcasevol.bat failed.
popd
endlocal
exit /b 1
