@echo off
echo creating sync.bat in %CD%
echo cd "%CD%" > "%CD%\sync.bat"
echo php sync.php >> "%CD%\sync.bat"
echo creating createTask.bat
echo schtasks /create /ru SYSTEM /tn "CGMonitor" /tr "%CD%\sync.bat" /sc minute /mo 2 /st 00:00 > createTask.bat
echo pause >> createTask.bat
echo all done
echo Run createTask.bat as Administrator now!
pause