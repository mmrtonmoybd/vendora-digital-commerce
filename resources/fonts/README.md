# Fonts bundled for PDF rendering

`HindSiliguri-*.ttf` — Hind Siliguri, © Indian Type Foundry, released under the
SIL Open Font License 1.1 (<https://scripts.sil.org/OFL>). Obtained from Google Fonts:
<https://fonts.google.com/specimen/Hind+Siliguri>.

It is bundled rather than fetched at runtime because the PDF renderer has remote access
disabled, and because it is the same face the storefront loads from Google Fonts — an
invoice that does not match the site it came from looks like a forgery.

Hind Siliguri was chosen over Noto Sans Bengali for one concrete reason: mPDF's OpenType
layout engine rejects Noto Sans Bengali's GPOS table ("Lookup Type 5, Format 3 not
supported"), and without OTL a Bengali invoice renders with pre-base vowels in the wrong
position — "ইনভয়েস" comes out as "ইনভয়সে".
