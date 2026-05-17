$ErrorActionPreference = "Stop"

$Watch = $false
foreach ($arg in $args) {
    if ($arg -eq "--watch" -or $arg -eq "-watch" -or $arg -eq "-Watch") {
        $Watch = $true
    }
}

$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$DeployEnvPath = Join-Path $ProjectRoot ".env.deploy"

function Read-DotEnvFile {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Missing .env.deploy. Copy .env.deploy.example to .env.deploy and fill in the deploy credentials."
    }

    $values = @{}
    foreach ($rawLine in Get-Content -LiteralPath $Path) {
        $line = $rawLine.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) {
            continue
        }

        $index = $line.IndexOf("=")
        if ($index -lt 1) {
            continue
        }

        $key = $line.Substring(0, $index).Trim()
        $value = $line.Substring($index + 1).Trim()
        if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
            $value = $value.Substring(1, $value.Length - 2)
        }

        $values[$key] = $value
    }

    return $values
}

function Require-DeployValue {
    param(
        [hashtable]$Env,
        [string]$Key
    )

    if (-not $Env.ContainsKey($Key) -or [string]::IsNullOrWhiteSpace([string]$Env[$Key])) {
        throw "Missing $Key in .env.deploy"
    }

    return [string]$Env[$Key]
}

function Get-RelativePath {
    param([string]$Path)

    $root = [System.IO.Path]::GetFullPath($ProjectRoot).TrimEnd('\') + '\'
    $full = [System.IO.Path]::GetFullPath($Path)
    return $full.Substring($root.Length).Replace('\', '/')
}

function Test-ExcludedPath {
    param([string]$RelativePath)

    $path = $RelativePath.Replace('\', '/').TrimStart('/')
    $leaf = Split-Path $path -Leaf

    $exact = @(
        ".gitignore",
        ".env",
        ".env.deploy",
        ".env.deploy.example",
        ".env.example",
        ".cpanel.yml",
        "config.php",
        "deploy.ps1",
        "DEPLOYMENT.md",
        "README.md",
        "install.php"
    )
    if ($exact -contains $path) {
        return $true
    }

    $dirPrefixes = @(
        ".git/",
        "node_modules/",
        "vendor/",
        "storage/logs/",
        "storage/cache/",
        "cache/",
        "tmp/"
    )
    foreach ($prefix in $dirPrefixes) {
        if ($path.StartsWith($prefix)) {
            return $true
        }
    }

    if ($leaf -like "*.log" -or $leaf -like "*.cache" -or $leaf -eq ".phpunit.result.cache") {
        return $true
    }

    return $false
}

function Get-DeployableFiles {
    Get-ChildItem -LiteralPath $ProjectRoot -Recurse -File -Force |
        Where-Object {
            $relative = Get-RelativePath $_.FullName
            -not (Test-ExcludedPath $relative)
        }
}

function Join-RemotePath {
    param(
        [string]$RemoteRoot,
        [string]$RelativePath
    )

    $root = $RemoteRoot.Replace('\', '/')
    if (-not $root.StartsWith("/")) {
        $root = "/" + $root
    }
    if (-not $root.EndsWith("/")) {
        $root += "/"
    }

    return ($root + $RelativePath.TrimStart("/")).Replace("//", "/")
}

function ConvertTo-RemoteUrlPath {
    param([string]$Path)

    $segments = $Path.Replace('\', '/').Split("/")
    $encoded = foreach ($segment in $segments) {
        [System.Uri]::EscapeDataString($segment)
    }
    return ($encoded -join "/")
}

function Upload-File {
    param(
        [string]$LocalPath,
        [string]$RelativePath
    )

    $remotePath = Join-RemotePath $script:RemoteRoot $RelativePath
    $encodedRemotePath = ConvertTo-RemoteUrlPath $remotePath
    $url = "{0}://{1}:{2}{3}" -f $script:Protocol, $script:HostName, $script:Port, $encodedRemotePath

    $configFile = [System.IO.Path]::GetTempFileName()
    try {
        $curlConfig = @(
            "fail",
            "silent",
            "show-error",
            "ftp-create-dirs",
            "connect-timeout = 20",
            "user = `"$script:UserName`:$script:Password`"",
            "upload-file = `"$LocalPath`"",
            "url = `"$url`""
        )
        Set-Content -LiteralPath $configFile -Value $curlConfig -Encoding ASCII

        & $script:CurlPath --config $configFile
        if ($LASTEXITCODE -ne 0) {
            throw "Upload failed for $RelativePath"
        }

        Write-Host "Uploaded $RelativePath"
    } finally {
        Remove-Item -LiteralPath $configFile -Force -ErrorAction SilentlyContinue
    }
}

function Upload-All {
    $files = @(Get-DeployableFiles)
    Write-Host "Uploading $($files.Count) files to ${script:Protocol}://${script:HostName}:${script:Port}${script:RemoteRoot}"

    foreach ($file in $files) {
        $relative = Get-RelativePath $file.FullName
        Upload-File -LocalPath $file.FullName -RelativePath $relative
    }
}

function Get-FileSnapshot {
    $snapshot = @{}
    foreach ($file in Get-DeployableFiles) {
        $relative = Get-RelativePath $file.FullName
        $snapshot[$relative] = "$($file.Length):$($file.LastWriteTimeUtc.Ticks)"
    }
    return $snapshot
}

function Watch-And-Upload {
    Write-Host "Watch mode is running. Press Ctrl+C to stop."
    $snapshot = Get-FileSnapshot

    while ($true) {
        Start-Sleep -Seconds 2
        $current = Get-FileSnapshot

        foreach ($relative in $current.Keys) {
            if (-not $snapshot.ContainsKey($relative) -or $snapshot[$relative] -ne $current[$relative]) {
                $localPath = Join-Path $ProjectRoot ($relative.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
                if (Test-Path -LiteralPath $localPath -PathType Leaf) {
                    Upload-File -LocalPath $localPath -RelativePath $relative
                }
            }
        }

        $snapshot = $current
    }
}

$envValues = Read-DotEnvFile $DeployEnvPath
$script:HostName = (Require-DeployValue $envValues "DEPLOY_HOST") -replace "^[a-zA-Z]+://", ""
$script:Protocol = (Require-DeployValue $envValues "DEPLOY_PROTOCOL").ToLowerInvariant()
if ($script:Protocol -notin @("ftp", "sftp")) {
    throw "DEPLOY_PROTOCOL must be ftp or sftp"
}

$defaultPort = if ($script:Protocol -eq "sftp") { "22" } else { "21" }
$script:Port = if ($envValues.ContainsKey("DEPLOY_PORT") -and -not [string]::IsNullOrWhiteSpace([string]$envValues["DEPLOY_PORT"])) { [string]$envValues["DEPLOY_PORT"] } else { $defaultPort }
$script:UserName = Require-DeployValue $envValues "DEPLOY_USER"
$script:Password = Require-DeployValue $envValues "DEPLOY_PASSWORD"
$script:RemoteRoot = Require-DeployValue $envValues "DEPLOY_REMOTE_PATH"
$script:CurlPath = (Get-Command "curl.exe" -ErrorAction SilentlyContinue).Source
if (-not $script:CurlPath) {
    throw "curl.exe was not found. Install curl or use a Windows version that includes curl.exe."
}

Upload-All

if ($Watch) {
    Watch-And-Upload
}
