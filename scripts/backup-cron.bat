@echo off
REM Script para agendar backup automático no Windows
REM Adicione este script ao Agendador de Tarefas do Windows

cd /d "C:\Users\Wellington Oliveira\Documents\GitHub\Projeto_Site_Escolar"
php scripts\backup-database.php

echo Backup concluido em %date% %time%
