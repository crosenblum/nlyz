@echo off
rem ebay store downloaded
rem Craig M. Rosenblum
rem February 21st, 2022

rem download store page
wget --force-html --convert-links ‐P C:\xampp\htdocs\nlyz\temp\ "https://www.ebay.com/str/cashinwhi?_sop=10"

rem rename file to what i want
rem ren C:\xampp\htdocs\nlyz\temp\cashwinwhi C:\xampp\htdocs\nlyz\temp\index.html 