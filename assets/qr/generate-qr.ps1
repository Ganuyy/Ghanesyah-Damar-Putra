param(
  [string]$Url = 'http://guitarlearn.test',
  [string]$OutDir = $PSScriptRoot
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

if (-not (Test-Path -LiteralPath $OutDir)) {
  New-Item -ItemType Directory -Path $OutDir | Out-Null
}
$OutDir = (Resolve-Path -LiteralPath $OutDir).Path

function Get-Color([string]$hex) {
  return [System.Drawing.ColorTranslator]::FromHtml($hex)
}

function Get-Font([string]$name, [int]$size, [string]$style = 'Regular') {
  $fs = if ($style -eq 'Bold') { [System.Drawing.FontStyle]::Bold } else { [System.Drawing.FontStyle]::Regular }
  return New-Object System.Drawing.Font($name, [single]$size, $fs, [System.Drawing.GraphicsUnit]::Pixel)
}

function Get-RoundedPath([int]$x, [int]$y, [int]$w, [int]$h, [int]$r) {
  $p = New-Object System.Drawing.Drawing2D.GraphicsPath
  $d = $r * 2
  $null = $p.AddArc($x, $y, $d, $d, 180, 90)
  $null = $p.AddArc($x + $w - $d, $y, $d, $d, 270, 90)
  $null = $p.AddArc($x + $w - $d, $y + $h - $d, $d, $d, 0, 90)
  $null = $p.AddArc($x, $y + $h - $d, $d, $d, 90, 90)
  $p.CloseFigure()
  return $p
}

function Draw-Text($g, [string]$text, $font, $brush, [single]$x, [single]$y) {
  $g.DrawString($text, $font, $brush, $x, $y)
}

function Draw-TextCenter($g, [string]$text, $font, $brush, [single]$x, [single]$y, [single]$w, [single]$h) {
  $sf = New-Object System.Drawing.StringFormat
  $sf.Alignment = [System.Drawing.StringAlignment]::Center
  $sf.LineAlignment = [System.Drawing.StringAlignment]::Center
  $rect = New-Object System.Drawing.RectangleF($x, $y, $w, $h)
  $g.DrawString($text, $font, $brush, $rect, $sf)
  $sf.Dispose()
}

function Draw-Background($g, [int]$w, [int]$h) {
  $rect = New-Object System.Drawing.Rectangle(0, 0, $w, $h)
  $bg = New-Object System.Drawing.Drawing2D.LinearGradientBrush($rect, (Get-Color '#150F1E'), (Get-Color '#0D0912'), 45)
  $g.FillRectangle($bg, $rect)
  $bg.Dispose()

  $glow1 = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(20, 168, 85, 247))
  $glow2 = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(14, 124, 58, 237))
  $glow3 = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(10, 192, 132, 252))
  $g.FillEllipse($glow1, [int]($w * 0.58), -220, [int]($w * 0.60), [int]($w * 0.60))
  $g.FillEllipse($glow2, -260, [int]($h * 0.55), [int]($w * 0.55), [int]($w * 0.55))
  $g.FillEllipse($glow3, [int]($w * 0.38), [int]($h * 0.66), [int]($w * 0.40), [int]($w * 0.40))
  $glow1.Dispose()
  $glow2.Dispose()
  $glow3.Dispose()

  $linePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(40, 168, 85, 247), 2)
  $g.DrawLine($linePen, 0, 1, $w, 1)
  $linePen.Dispose()
}

function Draw-LogoMark($g, [int]$x, [int]$y, [int]$size) {
  $path = Get-RoundedPath $x $y $size $size 20
  $rect = New-Object System.Drawing.Rectangle($x, $y, $size, $size)
  $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush($rect, (Get-Color '#A855F7'), (Get-Color '#7C3AED'), 135)
  $g.FillPath($brush, $path)
  $brush.Dispose()

  $note = [string][char]0x266B
  $font = Get-Font 'Segoe UI Symbol' ([int]($size * 0.48)) 'Bold'
  $textBrush = New-Object System.Drawing.SolidBrush((Get-Color '#FFFFFF'))
  Draw-TextCenter $g $note $font $textBrush $x $y $size $size
  $textBrush.Dispose()
  $font.Dispose()
  $path.Dispose()
}

function Draw-QrPanel($g, $qr, [int]$x, [int]$y, [int]$w, [int]$h, [int]$qrSize, [bool]$showCaption) {
  $shadow = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(90, 0, 0, 0))
  $shadowPath = Get-RoundedPath ($x + 12) ($y + 16) $w $h 30
  $g.FillPath($shadow, $shadowPath)
  $shadow.Dispose()
  $shadowPath.Dispose()

  $panelPath = Get-RoundedPath $x $y $w $h 30
  $white = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
  $g.FillPath($white, $panelPath)
  $white.Dispose()

  $accent = New-Object System.Drawing.Pen((Get-Color '#A855F7'), 5)
  $g.DrawLine($accent, $x + 42, $y + 20, $x + $w - 42, $y + 20)
  $accent.Dispose()

  $qx = $x + [int](($w - $qrSize) / 2)
  $qy = $y + [int](($h - $qrSize) / 2) - 18
  $g.DrawImage($qr, (New-Object System.Drawing.Rectangle($qx, $qy, $qrSize, $qrSize)))

  if ($showCaption) {
    $capFont = Get-Font 'Segoe UI' 26 'Bold'
    $capBrush = New-Object System.Drawing.SolidBrush((Get-Color '#0D0912'))
    Draw-TextCenter $g 'SCAN DI SINI' $capFont $capBrush $x ($y + $h - 88) $w 34
    $capBrush.Dispose()
    $capFont.Dispose()

    $subFont = Get-Font 'Segoe UI' 17
    $subBrush = New-Object System.Drawing.SolidBrush((Get-Color '#5D6775'))
    Draw-TextCenter $g 'Arahkan kamera ke QR code' $subFont $subBrush $x ($y + $h - 52) $w 26
    $subBrush.Dispose()
    $subFont.Dispose()
  }

  $panelPath.Dispose()
}

function Draw-Pill($g, [string]$text, [int]$x, [int]$y, [int]$w, [int]$h) {
  $path = Get-RoundedPath $x $y $w $h ([int]($h / 2))
  $fill = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(28, 168, 85, 247))
  $g.FillPath($fill, $path)
  $fill.Dispose()
  $pen = New-Object System.Drawing.Pen((Get-Color '#2F2340'), 2)
  $g.DrawPath($pen, $path)
  $pen.Dispose()

  $font = Get-Font 'Segoe UI' 17 'Bold'
  $brush = New-Object System.Drawing.SolidBrush((Get-Color '#ECE7F3'))
  Draw-TextCenter $g $text $font $brush $x $y $w $h
  $brush.Dispose()
  $font.Dispose()
  $path.Dispose()
}

function Save-Png($bmp, [string]$path) {
  $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
}

# ---------------------------------------------------------
# 1. Download QR
# ---------------------------------------------------------
$qrPath = Join-Path $OutDir 'guitartice-qr.png'
$api = 'https://quickchart.io/qr?text=' + [uri]::EscapeDataString($Url) + '&size=1200&margin=2&ecLevel=H&format=png&dark=0D0912&light=FFFFFF'
try {
  Invoke-WebRequest -Uri $api -OutFile $qrPath -UseBasicParsing -TimeoutSec 45
} catch {
  $api2 = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=' + [uri]::EscapeDataString($Url) + '&color=0D0912&bgcolor=FFFFFF&qzone=2&ecc=H'
  Invoke-WebRequest -Uri $api2 -OutFile $qrPath -UseBasicParsing -TimeoutSec 45
}
$qr = [System.Drawing.Image]::FromFile($qrPath)

# ---------------------------------------------------------
# 2. Kartu 16:9 (1920 x 1080)
# ---------------------------------------------------------
$w = 1920
$h = 1080
$bmp = New-Object System.Drawing.Bitmap($w, $h)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
Draw-Background $g $w $h

$logoX = 150
$logoY = 168
Draw-LogoMark $g $logoX $logoY 82

$brandFont = Get-Font 'Segoe UI' 56 'Bold'
$brandBrush = New-Object System.Drawing.SolidBrush((Get-Color '#ECE7F3'))
$brandAccent = New-Object System.Drawing.SolidBrush((Get-Color '#A855F7'))
$brandX = $logoX + 108
$brandY = $logoY - 6
Draw-Text $g 'Guitar' $brandFont $brandBrush $brandX $brandY
$guitarWidth = $g.MeasureString('Guitar', $brandFont).Width
Draw-Text $g 'Tice' $brandFont $brandAccent ($brandX + $guitarWidth - 4) $brandY

$tagFont = Get-Font 'Segoe UI' 24
$tagBrush = New-Object System.Drawing.SolidBrush((Get-Color '#C084FC'))
Draw-Text $g 'Belajar Gitar Santai' $tagFont $tagBrush $brandX ($brandY + 62)

$headFont = Get-Font 'Segoe UI' 50 'Bold'
$headBrush = New-Object System.Drawing.SolidBrush((Get-Color '#FFFFFF'))
Draw-Text $g 'Scan untuk mulai belajar' $headFont $headBrush 150 350

$subFont = Get-Font 'Segoe UI' 23
$subBrush = New-Object System.Drawing.SolidBrush((Get-Color '#9A8CAE'))
Draw-Text $g 'Chord, latihan, lagu, dan progres dalam satu aplikasi.' $subFont $subBrush 150 426

Draw-Pill $g '38 CHORD' 150 530 180 54
Draw-Pill $g '9 LAGU' 346 530 146 54
Draw-Pill $g '3 LEVEL' 508 530 158 54

$urlLabelFont = Get-Font 'Segoe UI' 20
$urlLabelBrush = New-Object System.Drawing.SolidBrush((Get-Color '#9A8CAE'))
Draw-Text $g 'Buka GuitarTice:' $urlLabelFont $urlLabelBrush 150 642

$urlFont = Get-Font 'Segoe UI' 31 'Bold'
$urlBrush = New-Object System.Drawing.SolidBrush((Get-Color '#C084FC'))
Draw-Text $g $Url $urlFont $urlBrush 150 676

$hintFont = Get-Font 'Segoe UI' 20
$hintBrush = New-Object System.Drawing.SolidBrush((Get-Color '#9A8CAE'))
Draw-Text $g 'Scan QR di samping untuk membuka langsung di browser.' $hintFont $hintBrush 150 726

$accentLine = New-Object System.Drawing.Pen((Get-Color '#A855F7'), 4)
$g.DrawLine($accentLine, 150, 902, 450, 902)
$accentLine.Dispose()

$bottomFont = Get-Font 'Segoe UI' 24
$bottomBrush = New-Object System.Drawing.SolidBrush((Get-Color '#ECE7F3'))
Draw-Text $g 'Gratis. Tanpa install. Genjrengin bae udah.' $bottomFont $bottomBrush 150 928

Draw-QrPanel $g $qr 1110 170 680 740 570 $true

$card1 = Join-Path $OutDir 'guitartice-qr-card-1920x1080.png'
Save-Png $bmp $card1

$brandFont.Dispose(); $brandBrush.Dispose(); $brandAccent.Dispose()
$tagFont.Dispose(); $tagBrush.Dispose(); $headFont.Dispose(); $headBrush.Dispose()
$subFont.Dispose(); $subBrush.Dispose(); $urlLabelFont.Dispose(); $urlLabelBrush.Dispose()
$urlFont.Dispose(); $urlBrush.Dispose(); $hintFont.Dispose(); $hintBrush.Dispose()
$bottomFont.Dispose(); $bottomBrush.Dispose()
$g.Dispose(); $bmp.Dispose()

# ---------------------------------------------------------
# 3. Kartu 1:1 (1080 x 1080)
# ---------------------------------------------------------
$w = 1080
$h = 1080
$bmp = New-Object System.Drawing.Bitmap($w, $h)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
Draw-Background $g $w $h

$brandFont = Get-Font 'Segoe UI' 46 'Bold'
$brandBrush = New-Object System.Drawing.SolidBrush((Get-Color '#ECE7F3'))
$brandAccent = New-Object System.Drawing.SolidBrush((Get-Color '#A855F7'))
$brandText = 'GuitarTice'
$brandSize = $g.MeasureString($brandText, $brandFont)
$markSize = 72
$gap = 16
$groupW = $markSize + $gap + $brandSize.Width
$startX = [int](($w - $groupW) / 2)
$markY = 70
Draw-LogoMark $g $startX $markY $markSize
Draw-Text $g 'Guitar' $brandFont $brandBrush ($startX + $markSize + $gap) ($markY - 4)
$guitarWidth = $g.MeasureString('Guitar', $brandFont).Width
Draw-Text $g 'Tice' $brandFont $brandAccent ($startX + $markSize + $gap + $guitarWidth - 4) ($markY - 4)

$tagFont = Get-Font 'Segoe UI' 20
$tagBrush = New-Object System.Drawing.SolidBrush((Get-Color '#C084FC'))
Draw-TextCenter $g 'Belajar Gitar Santai' $tagFont $tagBrush 0 164 $w 30

$headFont = Get-Font 'Segoe UI' 36 'Bold'
$headBrush = New-Object System.Drawing.SolidBrush((Get-Color '#FFFFFF'))
Draw-TextCenter $g 'Scan untuk mulai belajar' $headFont $headBrush 0 222 $w 46

$subFont = Get-Font 'Segoe UI' 19
$subBrush = New-Object System.Drawing.SolidBrush((Get-Color '#9A8CAE'))
Draw-TextCenter $g 'Chord - latihan - lagu - progres' $subFont $subBrush 0 280 $w 26

Draw-QrPanel $g $qr 230 336 620 624 520 $true

$urlFont = Get-Font 'Segoe UI' 26 'Bold'
$urlBrush = New-Object System.Drawing.SolidBrush((Get-Color '#C084FC'))
Draw-TextCenter $g $Url $urlFont $urlBrush 0 976 $w 36

$bottomFont = Get-Font 'Segoe UI' 17
$bottomBrush = New-Object System.Drawing.SolidBrush((Get-Color '#9A8CAE'))
Draw-TextCenter $g 'Gratis. Tanpa install. Genjrengin bae udah.' $bottomFont $bottomBrush 0 1022 $w 24

$card2 = Join-Path $OutDir 'guitartice-qr-card-1080x1080.png'
Save-Png $bmp $card2

$brandFont.Dispose(); $brandBrush.Dispose(); $brandAccent.Dispose()
$tagFont.Dispose(); $tagBrush.Dispose(); $headFont.Dispose(); $headBrush.Dispose()
$subFont.Dispose(); $subBrush.Dispose(); $urlFont.Dispose(); $urlBrush.Dispose()
$bottomFont.Dispose(); $bottomBrush.Dispose()
$g.Dispose(); $bmp.Dispose()
$qr.Dispose()

Write-Output "URL : $Url"
Write-Output "QR  : $qrPath"
Write-Output "Card: $card1"
Write-Output "Card: $card2"
