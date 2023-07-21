@echo off
rem run php script on demand
rem Craig M. Rosenblum
rem March 22nd, 2022

REM This adds the folder containing php.exe to the path
PATH=%PATH%;C:\xampp\php

rem run script now
C:\xampp\php\php.exe -f C:\xampp\htdocs\nlyz\que_processor.php