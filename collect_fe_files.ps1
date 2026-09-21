param(
    [string]$Output = "fnh_contents.txt",
    [string]$Root = (Get-Location).Path,
    [int64]$MaxBytes = 2MB
)

Write-Host "========================================"
Write-Host "Target Root: $Root"
Write-Host "Target Output: $Output"
Write-Host "========================================"

# ---------------------------------------------------------
# Allow PHP, CSS, and SQL files
# ---------------------------------------------------------
$allowedExtensions = @(".php", ".css", ".sql", ".txt" )

# ---------------------------------------------------------
# Directory exclusions (ONLY vscode)
# ---------------------------------------------------------
$excludeDirNames = @(".vscode")

# ---------------------------------------------------------
# File exclusions (keep your existing list)
# ---------------------------------------------------------
$excludeFileNames = @(
    ".gitignore", ".env", ".env.local", ".env.development",
    ".env.production", "fnh_contents.txt", "backend_contents.txt",
    "fe_contents.txt", "ticket_contents.txt",
    "package-lock.json", "yarn.lock", "pnpm-lock.yaml",
    "poetry.lock", "Pipfile.lock",
    "gradlew", "gradlew.bat",
    $MyInvocation.MyCommand.Name
)

if ($Output) {
    $outName = [System.IO.Path]::GetFileName($Output)
    if ($excludeFileNames -notcontains $outName) { $excludeFileNames += $outName }
}

# ---------------------------------------------------------
# No exact-name allowances anymore
# ---------------------------------------------------------
$allowedExactNames = @()

# Wipe out old file early
if (Test-Path $Output) {
    Remove-Item $Output -Force -ErrorAction SilentlyContinue
}

# ---------------------------------------------------------
# Simplified Inclusion Logic (PHP + CSS + SQL)
# ---------------------------------------------------------
function Test-IsAllowedFile {
    param([System.IO.FileInfo]$File)

    # 1. Block excluded filenames
    if ($excludeFileNames -contains $File.Name) { return $false }

    # 2. Size restriction
    if ($File.Length -gt $MaxBytes) { return $false }

    # 3. Directory exclusion (ONLY .vscode)
    $cleanPath = $File.FullName.Replace('/', '\')
    foreach ($dir in $excludeDirNames) {
        if ($cleanPath -match "\\$dir\\") { return $false }
    }

    # 4. Allow .php, .css, .sql
    if ($allowedExtensions -notcontains $File.Extension.ToLowerInvariant()) {
        return $false
    }

    return $true
}

# ---------------------------------------------------------
# MAIN LOOPS
# ---------------------------------------------------------
Write-Host "Scanning files..."
$rawFiles = Get-ChildItem -Path $Root -Recurse -File -Force

$files = $rawFiles | Where-Object { Test-IsAllowedFile $_ } | Sort-Object FullName

Write-Host "Total raw files discovered in path: $($rawFiles.Count)"
Write-Host "Files qualifying filter rules: $($files.Count)"

if ($files.Count -eq 0) {
    Write-Warning "No PHP, CSS, or SQL files matched criteria. Verify you are executing from inside your project folder."
    return
}

# Initialize target stream
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$writer = New-Object System.IO.StreamWriter($Output, $false, $utf8NoBom)

try {
    foreach ($file in $files) {
        $writer.WriteLine("========================================")
        $writer.WriteLine("FILE PATH: $($file.FullName)")
        $writer.WriteLine("FILENAME: $($file.Name)")
        $writer.WriteLine("SIZE_BYTES: $($file.Length)")
        $writer.WriteLine("----------------------------------------")

        try {
            $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
            $content = [System.Text.Encoding]::UTF8.GetString($bytes)
            $writer.Write($content)

            if (-not $content.EndsWith("`n")) {
                $writer.WriteLine("")
            }
        }
        catch {
            $writer.WriteLine("[ERROR READING FILE]")
        }

        $writer.WriteLine("")
        $writer.WriteLine("")
    }
}
finally {
    $writer.Close()
}

Write-Host ""
Write-Host "Done! Output safely committed to: $Output"
