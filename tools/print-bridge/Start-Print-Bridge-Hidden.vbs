' QRPOS — start Print Bridge with no visible window
Option Explicit
Dim sh, fso, dir, ps1, cmd
Set fso = CreateObject("Scripting.FileSystemObject")
Set sh = CreateObject("WScript.Shell")
dir = fso.GetParentFolderName(WScript.ScriptFullName)
ps1 = dir & "\Print-Bridge.ps1"
If Not fso.FileExists(ps1) Then
  MsgBox "Print-Bridge.ps1 not found next to this script.", vbExclamation, "QRPOS Print Bridge"
  WScript.Quit 1
End If
' 0 = hidden window
cmd = "powershell.exe -NoLogo -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File """ & ps1 & """"
sh.Run cmd, 0, False
