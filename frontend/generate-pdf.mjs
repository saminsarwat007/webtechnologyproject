import { chromium } from 'playwright'
import fs from 'fs'
import path from 'path'

const url = 'http://localhost:5173/demo/system-overview.html'
const outputDir = 'demo/pdf-slides'
const outputPath = 'demo/Champion.pdf'

async function main () {
  if (!fs.existsSync(outputDir)) fs.mkdirSync(outputDir, { recursive: true })

  const browser = await chromium.launch()
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } })

  console.log('Loading presentation...')
  await page.goto(url, { waitUntil: 'networkidle' })

  await page.evaluate(() => {
    return Promise.all(
      Array.from(document.images).map(img =>
        img.complete ? Promise.resolve() : new Promise(r => { img.onload = img.onerror = r })
      )
    )
  })

  await page.waitForTimeout(2000)

  const totalSlides = await page.evaluate(() => Reveal.getTotalSlides())
  console.log(`Found ${totalSlides} slides. Capturing...`)

  for (let i = 0; i < totalSlides; i++) {
    await page.evaluate((idx) => Reveal.slide(idx), i)
    await page.waitForTimeout(800)
    const screenshotPath = path.join(outputDir, `slide-${String(i + 1).padStart(2, '0')}.png`)
    await page.screenshot({ path: screenshotPath, type: 'png' })
    console.log(`  Captured slide ${i + 1}/${totalSlides}`)
  }

  await browser.close()

  console.log('Combining slides into PDF...')

  const images = fs.readdirSync(outputDir)
    .filter(f => f.endsWith('.png'))
    .sort()
    .map(f => path.join(outputDir, f))

  const html = `<!DOCTYPE html>
<html>
<head>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  @page { size: 1440px 900px; margin: 0; }
  body { margin: 0; padding: 0; }
  .slide-page {
    width: 1440px;
    height: 900px;
    page-break-after: always;
    page-break-inside: avoid;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }
  .slide-page img {
    width: 1440px;
    height: 900px;
    object-fit: contain;
  }
  .slide-page:last-child {
    page-break-after: avoid;
  }
</style>
</head>
<body>
${images.map(img => `  <div class="slide-page"><img src="file://${path.resolve(img)}" /></div>`).join('\n')}
</body>
</html>`

  const htmlPath = path.join(outputDir, 'combine.html')
  fs.writeFileSync(htmlPath, html)

  const { execSync } = await import('child_process')
  const chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
  const absoluteOutputPath = path.resolve(outputPath)

  execSync(`"${chromePath}" --headless --disable-gpu --no-sandbox --print-to-pdf="${absoluteOutputPath}" --print-to-pdf-no-header --no-margins "file://${path.resolve(htmlPath)}"`, {
    stdio: 'pipe',
    cwd: process.cwd()
  })

  const sizeMB = (fs.statSync(outputPath).size / 1024 / 1024).toFixed(1)
  console.log(`PDF saved to ${outputPath} (${sizeMB} MB)`)

  fs.rmSync(outputDir, { recursive: true, force: true })
  console.log('Cleaned up temp files.')
}

main().catch(err => {
  console.error('Error:', err)
  process.exit(1)
})
