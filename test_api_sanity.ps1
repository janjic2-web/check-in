# PowerShell sanity test za Atendo API (login + checkin + negativni test)
$ErrorActionPreference = "Stop"

$BASE   = "http://127.0.0.1:8000"   # promeni na 8001 ako koristiš drugi port
$APIKEY = "TEST_API_KEY_123"

function Show-HttpError {
    param($e)
    Write-Warning ("HTTP error: " + $e.Exception.Message)
    if ($e.Exception.Response) {
        $resp = $e.Exception.Response
        try {
            $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
            $body = $reader.ReadToEnd()
            Write-Host "StatusCode: $($resp.StatusCode) ($([int]$resp.StatusCode))"
            Write-Host "Response body:"
            Write-Host $body
        } catch { Write-Warning "No response body." }
    }
}

# 0) Health check porta (pre nego što krenemo)
try {
    $ok = Test-NetConnection -ComputerName 127.0.0.1 -Port 8000
    if (-not $ok.TcpTestSucceeded) { throw "Server ne sluša na $BASE" }
} catch { Write-Error $_; exit 1 }

# 1) LOGIN (login + password)
try {
    $loginBody = @{ login = "user1"; password = "tajna123" } | ConvertTo-Json
    $login = Invoke-RestMethod -Method Post -Uri "$BASE/api/v1/auth/login" `
        -Headers @{
            "Accept" = "application/json"
            "Content-Type" = "application/json"
            "x-api-key" = $APIKEY
        } -Body $loginBody -TimeoutSec 10
    Write-Host "Login response:"; $login | ConvertTo-Json -Depth 5
} catch { Show-HttpError $_; exit 1 }

$token = $login.access_token
if (-not $token) { Write-Error "JWT token nije dobijen!"; exit 1 }

# 2) CHECKIN (NFC) – pozitivan slučaj
try {
    $checkinBody = @{
        method = "nfc"
        action = "in"
        lat = 45.2671
        lng = 19.8335
        client_event_id = [guid]::NewGuid().ToString()
        details = @{ tag_uid = "TEST_TAG_123" }
    } | ConvertTo-Json

    $checkin = Invoke-RestMethod -Method Post -Uri "$BASE/api/v1/checkin" `
        -Headers @{
            "Accept" = "application/json"
            "Content-Type" = "application/json"
            "x-api-key" = $APIKEY
            "Authorization" = "Bearer $token"
            "Idempotency-Key" = ([guid]::NewGuid().ToString())
        } -Body $checkinBody -TimeoutSec 10

    Write-Host "Checkin response:"; $checkin | ConvertTo-Json -Depth 5
} catch { Show-HttpError $_; exit 1 }

# 3) CHECKIN – negativan slučaj (pogrešan tag_uid)
try {
    $badBody = @{
        method = "nfc"
        action = "in"
        lat = 45.2671
        lng = 19.8335
        client_event_id = [guid]::NewGuid().ToString()
        details = @{ tag_uid = "NEPOSTOJI" }
    } | ConvertTo-Json

    $null = Invoke-RestMethod -Method Post -Uri "$BASE/api/v1/checkin" `
        -Headers @{
            "Accept" = "application/json"
            "Content-Type" = "application/json"
            "x-api-key" = $APIKEY
            "Authorization" = "Bearer $token"
            "Idempotency-Key" = ([guid]::NewGuid().ToString())
        } -Body $badBody -TimeoutSec 10

    Write-Warning "Negativni test neočekivano prošao (trebalo je da padne)."
} catch { Show-HttpError $_ }
