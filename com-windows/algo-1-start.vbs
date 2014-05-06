dim WinScriptHost

currentDirectory = left(WScript.ScriptFullName,(Len(WScript.ScriptFullName))-(len(WScript.ScriptName)))

Set WinScriptHost = CreateObject("WScript.Shell")
WinScriptHost.Run  currentDirectory + "algo-1.bat", 1
set WinScriptHost = nothing
WScript.Quit
