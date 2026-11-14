# Copies only ChallengeChain source files into the official HackOnVibe team repository.
# Run from any directory with: powershell -File scripts/publish-hackonvibe.ps1
$ErrorActionPreference = 'Stop'

$sourceRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$repoName = 'HackOnVibeCom/bz78qs8c81tsx8rlmrilm5gcqsw891ovott0mibo'
$target = Join-Path $sourceRoot 'ChallengeChain-deploy'

if (-not (Test-Path -LiteralPath $target)) {
    gh repo clone $repoName $target
    if ($LASTEXITCODE -ne 0) { throw 'GitHub CLI could not clone the team repository.' }
}

$target = (Resolve-Path -LiteralPath $target).Path
$origin = (git -C $target remote get-url origin).Trim()
if ($LASTEXITCODE -ne 0 -or $origin -notmatch '(?i)github\.com[:/]HackOnVibeCom/bz78qs8c81tsx8rlmrilm5gcqsw891ovott0mibo(?:\.git)?$') {
    throw "Unexpected Git remote: $origin"
}
if ((git -C $target status --porcelain).Length -ne 0) {
    throw 'The deployment clone has uncommitted changes. Review them before publishing.'
}
if ((git -C $target branch --show-current).Trim() -ne 'main') {
    throw 'The deployment clone must be on the main branch.'
}

$rootFiles = @('.env.example', '.gitignore', 'README.md', 'bootstrap.php', 'composer.json', 'composer.lock', 'index.html')
$sourceDirs = @('app', 'config', 'database', 'public', 'routes', 'scripts', 'tests')
$copied = New-Object System.Collections.Generic.List[string]

foreach ($relative in $rootFiles) {
    $from = Join-Path $sourceRoot $relative
    if (-not (Test-Path -LiteralPath $from -PathType Leaf)) { throw "Missing source file: $relative" }
    Copy-Item -LiteralPath $from -Destination (Join-Path $target $relative) -Force
    $copied.Add($relative)
}

foreach ($dir in $sourceDirs) {
    $directory = Join-Path $sourceRoot $dir
    foreach ($file in Get-ChildItem -LiteralPath $directory -File -Recurse -Force) {
        $relative = $file.FullName.Substring($sourceRoot.Length + 1)
        $gitPath = $relative.Replace('\', '/')
        if ($gitPath -match '^public/(?:uploads|logs)/') { continue }
        $to = Join-Path $target $relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $to) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $to -Force
        $copied.Add($gitPath)
    }
}

foreach ($relative in @('storage/.gitkeep', 'storage/logs/.gitkeep')) {
    $from = Join-Path $sourceRoot $relative
    $to = Join-Path $target $relative
    New-Item -ItemType Directory -Path (Split-Path -Parent $to) -Force | Out-Null
    Copy-Item -LiteralPath $from -Destination $to -Force
    $copied.Add($relative)
}

git -C $target add -- $copied.ToArray()
if ($LASTEXITCODE -ne 0) { throw 'Could not stage project files.' }

$staged = @(git -C $target diff --cached --name-only)
$forbidden = @($staged | Where-Object { $_ -match '(^|/)(?:\.env|vendor|database\.sqlite|.*\.log|uploads)(?:/|$)' -and $_ -ne '.env.example' })
if ($forbidden.Count -gt 0) { throw "Private files staged: $($forbidden -join ', ')" }

git -C $target diff --cached --check
if ($LASTEXITCODE -ne 0) { throw 'Git reports whitespace errors in staged files.' }

if ($staged.Count -eq 0) {
    $unpushed = [int](git -C $target rev-list --count origin/main..HEAD)
    if ($LASTEXITCODE -ne 0) { throw 'Could not check for local commits awaiting push.' }
    if ($unpushed -gt 0) {
        git -C $target push origin main
        if ($LASTEXITCODE -ne 0) { throw 'Git push failed. The commit remains in the local deployment clone.' }
        Write-Host 'Existing commit pushed to the HackOnVibe team repository.'
    } else {
        Write-Host 'The team repository is already up to date.'
    }
    exit 0
}

Write-Host "Publishing $($staged.Count) changed files to $repoName"
git -C $target commit -m 'Add ChallengeChain PHP app and live demo landing page'
if ($LASTEXITCODE -ne 0) { throw 'Git commit failed.' }
git -C $target push origin main
if ($LASTEXITCODE -ne 0) { throw 'Git push failed. The commit remains in the local deployment clone.' }

Write-Host 'Push complete. The PHP application is hosted separately at http://cc.tendry.mg.'
