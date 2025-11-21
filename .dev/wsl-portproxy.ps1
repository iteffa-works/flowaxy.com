# ------------------------------
# WSL Port Forward (80 + 443)
# Автоматический проброс портов
# ------------------------------

Write-Host "`nПолучение текущего WSL IP..." -ForegroundColor Cyan
$wslIp = wsl.exe hostname -I | ForEach-Object { $_.Trim() }

if (-not $wslIp) {
    Write-Host "Ошибка: не удалось получить IP WSL!" -ForegroundColor Red
    Write-Host "Нажмите Enter для выхода..."
    Read-Host
    exit
}

Write-Host "WSL IP найден: $wslIp`n" -ForegroundColor Green

# Очистка старых portproxy правил
Write-Host "Удаление старых portproxy правил..." -ForegroundColor Yellow
netsh interface portproxy reset

# Добавление новых правил
Write-Host "Добавление новых правил портов 80 и 443..." -ForegroundColor Yellow
netsh interface portproxy add v4tov4 listenport=80 listenaddress=0.0.0.0 connectport=80 connectaddress=$wslIp
netsh interface portproxy add v4tov4 listenport=443 listenaddress=0.0.0.0 connectport=443 connectaddress=$wslIp

# Firewall правила (добавляются только если их ещё нет)
Write-Host "Добавление правил firewall (если отсутствуют)..." -ForegroundColor Yellow
netsh advfirewall firewall add rule name="WSL Port 80" dir=in action=allow protocol=TCP localport=80 >$null 2>&1
netsh advfirewall firewall add rule name="WSL Port 443" dir=in action=allow protocol=TCP localport=443 >$null 2>&1

Write-Host "`nГотово!" -ForegroundColor Green
Write-Host "Проброс портов 80 и 443 → $wslIp обновлён.`n" -ForegroundColor Green

Write-Host "Нажмите Enter, чтобы закрыть окно..."
Read-Host
