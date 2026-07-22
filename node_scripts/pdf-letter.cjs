'use strict';

const fs   = require('fs');
const path = require('path');

(async () => {
    const optFile = process.argv[2];
    if (!optFile) { console.error('Usage: node pdf-letter.js <options.json>'); process.exit(1); }

    const opts = JSON.parse(fs.readFileSync(optFile, 'utf8'));

    let puppeteer;
    try {
        puppeteer = require(path.join(__dirname, '..', 'node_modules', 'puppeteer'));
    } catch {
        puppeteer = require('puppeteer');
    }

    const launchOpts = {
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-web-security'],
    };
    if (opts.chromePath) launchOpts.executablePath = opts.chromePath;

    // Build header/footer templates when letterhead images are provided.
    // Puppeteer renders these in the physical margin area on EVERY page.
    const hasHeader = !!opts.headerImage;
    const hasFooter = !!opts.footerImage;

    // Puppeteer's header/footer template body has internal Chrome padding.
    // Using position:absolute top:0/bottom:0 bypasses it entirely.
    const resetStyle = '<style>*,body,html{margin:0!important;padding:0!important;box-sizing:border-box;}</style>';

    const headerTemplate = hasHeader
        ? `${resetStyle}<div style="position:absolute;top:0;left:0;width:100%;height:${opts.margin.top};overflow:hidden;-webkit-print-color-adjust:exact;print-color-adjust:exact;"><img src="${opts.headerImage}" style="display:block;width:100%;height:100%;object-fit:fill;"></div>`
        : '<span></span>';

    const footerTemplate = hasFooter
        ? `${resetStyle}<div style="position:absolute;bottom:0;left:0;width:100%;height:${opts.margin.bottom};overflow:hidden;-webkit-print-color-adjust:exact;print-color-adjust:exact;"><img src="${opts.footerImage}" style="display:block;width:100%;height:100%;object-fit:fill;"></div>`
        : '<span></span>';

    const browser = await puppeteer.launch(launchOpts);
    try {
        const page = await browser.newPage();
        await page.setContent(opts.html, { waitUntil: 'networkidle0' });
        const paperOpts = opts.pageSize
            ? { width: opts.pageSize.width, height: opts.pageSize.height }
            : { format: 'A4' };

        await page.pdf({
            ...paperOpts,
            printBackground:     true,
            displayHeaderFooter: hasHeader || hasFooter,
            headerTemplate,
            footerTemplate,
            margin:              opts.margin ?? { top: 0, right: 0, bottom: 0, left: 0 },
            path:                opts.output,
        });
    } finally {
        await browser.close();
    }
})().catch(err => { console.error(err); process.exit(1); });
