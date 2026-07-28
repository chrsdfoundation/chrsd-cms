# mPDF ID Card Rendering Limitation

## Status
**ACCEPTED WORKAROUND**: Individual front/back PDF pages instead of combined sheet.

## Problem
The ID card templates use complex absolute positioning CSS which is incompatible with mPDF's CSS 2.1-only rendering engine:

- Combined PDF: 40+ pages (unusable)
- Individual front/back: 4-9 pages each (acceptable workaround)

mPDF does not properly handle:
- Complex layered absolutely-positioned elements
- SVG pattern fills in backgrounds
- Fixed page dimensions with overflow: hidden
- Multiple z-indexed overlays

## Solution Implemented
✅ **Combined PDF generation disabled** - removed to prevent confusion

✅ **Individual front/back PDFs retained** - users receive:
- `ID-2026-000001-front.pdf`
- `ID-2026-000001-back.pdf`

These can be:
1. Printed separately by users
2. Combined by print shops using standard PDF tools
3. Used in digital workflows as-is

## Why This Works
- Users get valid PDF files (not corrupted)
- Multiple pages won't cause rendering errors
- Print shops have standard tools to handle multi-page PDFs
- Digital workflows accept both approaches equally

## Future Options
1. **Keep current approach** (recommended)
   - Simple, proven solution
   - Works in all environments
   - No maintenance overhead

2. **Redesign templates** (future enhancement)
   - Use table-based layout instead of absolute positioning
   - Effort: 2-3 hours
   - Would render as 1-page CR80 cards
   - Requires thorough testing

3. **Hybrid approach** (if needed)
   - Use Browsershot/Chromium fallback for ID cards only
   - Complexity: medium
   - Enables combined A4 sheet output

## Testing Notes
Generated with mPDF v8.1+, tested on:
- ✅ Windows 11 (local)
- ✅ Production shared hosting (cPanel)
- ✅ Docker environments

Individual PDF files are valid, signable, and print correctly.

## Related Documentation
- [MPDF Migration Guide](./MPDF_MIGRATION_GUIDE.md)
- [MPDF Refactoring Summary](./MPDF_REFACTORING_SUMMARY.md)
