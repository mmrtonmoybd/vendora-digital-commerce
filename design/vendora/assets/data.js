/* Demo data — in Laravel, render this from the DB (or drop it and let Livewire handle state). */
window.SITE = {
 "currency": "৳",
 "demoUser": {
  "name": "Moshiur Rahman",
  "email": "moshiur@example.com",
  "balance": 1250
 },
 "products": {
  "stream-premium-1m": {
   "slug": "stream-premium-1m",
   "name": "StreamBox Premium — 1 Month",
   "category": "streaming",
   "price": 349,
   "regular": 450,
   "stock": 7,
   "badge": "BEST SELLING",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    },
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "4K UHD, 4 screens, private profile.",
   "icon": "play",
   "grad": "from-rose-500 to-red-700",
   "categoryName": "Streaming & OTT"
  },
  "stream-premium-3m": {
   "slug": "stream-premium-3m",
   "name": "StreamBox Premium — 3 Months",
   "category": "streaming",
   "price": 990,
   "regular": 1350,
   "stock": 12,
   "badge": "SAVE 27%",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    },
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Quarterly plan with renewal reminder.",
   "icon": "play",
   "grad": "from-rose-500 to-red-700",
   "categoryName": "Streaming & OTT"
  },
  "music-family-6m": {
   "slug": "music-family-6m",
   "name": "TuneWave Family — 6 Months",
   "category": "streaming",
   "price": 1450,
   "regular": 1800,
   "stock": 4,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    }
   ],
   "short": "Ad-free music for up to 6 members.",
   "icon": "play",
   "grad": "from-rose-500 to-red-700",
   "categoryName": "Streaming & OTT"
  },
  "anime-plus-1y": {
   "slug": "anime-plus-1y",
   "name": "AnimeHub Plus — 1 Year",
   "category": "streaming",
   "price": 2200,
   "regular": 2900,
   "stock": 0,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Simulcast anime, offline downloads.",
   "icon": "play",
   "grad": "from-rose-500 to-red-700",
   "categoryName": "Streaming & OTT"
  },
  "office-suite-lifetime": {
   "slug": "office-suite-lifetime",
   "name": "Office Suite 2024 — Lifetime Key",
   "category": "software",
   "price": 1850,
   "regular": 3500,
   "stock": 23,
   "badge": "BEST SELLING",
   "min": 1,
   "max": 5,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Retail key, 1 PC, bind to your account.",
   "icon": "key",
   "grad": "from-orange-500 to-red-600",
   "categoryName": "Software Licenses"
  },
  "os-pro-key": {
   "slug": "os-pro-key",
   "name": "Desktop OS Pro — Retail Key",
   "category": "software",
   "price": 1250,
   "regular": 2400,
   "stock": 15,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Genuine activation, lifetime updates.",
   "icon": "key",
   "grad": "from-orange-500 to-red-600",
   "categoryName": "Software Licenses"
  },
  "antivirus-3pc": {
   "slug": "antivirus-3pc",
   "name": "SecureGuard Antivirus — 3 PC / 1 Yr",
   "category": "software",
   "price": 890,
   "regular": 1400,
   "stock": 9,
   "badge": "NEW",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Real-time protection for 3 devices.",
   "icon": "key",
   "grad": "from-orange-500 to-red-600",
   "categoryName": "Software Licenses"
  },
  "pdf-editor-pro": {
   "slug": "pdf-editor-pro",
   "name": "PDF Editor Pro — 1 Year",
   "category": "software",
   "price": 1100,
   "regular": 1600,
   "stock": 0,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Edit, sign and convert PDFs.",
   "icon": "key",
   "grad": "from-orange-500 to-red-600",
   "categoryName": "Software Licenses"
  },
  "ff-diamond-520": {
   "slug": "ff-diamond-520",
   "name": "Battle Royale — 520 Diamonds",
   "category": "gaming",
   "price": 399,
   "regular": 450,
   "stock": 99,
   "badge": "INSTANT",
   "min": 1,
   "max": 20,
   "fields": [
    {
     "key": "player_id",
     "label": "Player / UID",
     "type": "text",
     "required": true,
     "placeholder": "e.g. 5123456789"
    },
    {
     "key": "region",
     "label": "Region",
     "type": "select",
     "required": true,
     "options": [
      "Bangladesh",
      "Global",
      "India"
     ]
    }
   ],
   "short": "Direct top-up via Player ID.",
   "icon": "gamepad",
   "grad": "from-fuchsia-600 to-rose-600",
   "categoryName": "Gaming Top-up"
  },
  "ff-weekly": {
   "slug": "ff-weekly",
   "name": "Battle Royale — Weekly Membership",
   "category": "gaming",
   "price": 175,
   "regular": 210,
   "stock": 50,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "player_id",
     "label": "Player / UID",
     "type": "text",
     "required": true,
     "placeholder": "e.g. 5123456789"
    }
   ],
   "short": "Daily diamonds for 7 days.",
   "icon": "gamepad",
   "grad": "from-fuchsia-600 to-rose-600",
   "categoryName": "Gaming Top-up"
  },
  "pubg-uc-660": {
   "slug": "pubg-uc-660",
   "name": "Arena Mobile — 660 UC",
   "category": "gaming",
   "price": 950,
   "regular": 1100,
   "stock": 31,
   "badge": "BEST SELLING",
   "min": 1,
   "max": 20,
   "fields": [
    {
     "key": "player_id",
     "label": "Player / UID",
     "type": "text",
     "required": true,
     "placeholder": "e.g. 5123456789"
    },
    {
     "key": "region",
     "label": "Region",
     "type": "select",
     "required": true,
     "options": [
      "Bangladesh",
      "Global",
      "India"
     ]
    }
   ],
   "short": "UID top-up, delivered in minutes.",
   "icon": "gamepad",
   "grad": "from-fuchsia-600 to-rose-600",
   "categoryName": "Gaming Top-up"
  },
  "game-pass-1m": {
   "slug": "game-pass-1m",
   "name": "Console Game Pass — 1 Month",
   "category": "gaming",
   "price": 690,
   "regular": 900,
   "stock": 6,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    }
   ],
   "short": "Hundreds of games, cloud play.",
   "icon": "gamepad",
   "grad": "from-fuchsia-600 to-rose-600",
   "categoryName": "Gaming Top-up"
  },
  "store-card-10": {
   "slug": "store-card-10",
   "name": "App Store Gift Card — $10",
   "category": "giftcards",
   "price": 1350,
   "regular": 1450,
   "stock": 18,
   "badge": "BEST SELLING",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "region",
     "label": "Region",
     "type": "select",
     "required": true,
     "options": [
      "Bangladesh",
      "Global",
      "India"
     ]
    }
   ],
   "short": "Code delivered to your email.",
   "icon": "gift",
   "grad": "from-amber-500 to-orange-600",
   "categoryName": "Gift Cards"
  },
  "play-card-25": {
   "slug": "play-card-25",
   "name": "Play Store Gift Card — $25",
   "category": "giftcards",
   "price": 3250,
   "regular": 3500,
   "stock": 7,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "region",
     "label": "Region",
     "type": "select",
     "required": true,
     "options": [
      "Bangladesh",
      "Global",
      "India"
     ]
    }
   ],
   "short": "Redeem on any Android device.",
   "icon": "gift",
   "grad": "from-amber-500 to-orange-600",
   "categoryName": "Gift Cards"
  },
  "steam-wallet-20": {
   "slug": "steam-wallet-20",
   "name": "Game Wallet Code — $20",
   "category": "giftcards",
   "price": 2600,
   "regular": 2800,
   "stock": 3,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "region",
     "label": "Region",
     "type": "select",
     "required": true,
     "options": [
      "Bangladesh",
      "Global",
      "India"
     ]
    }
   ],
   "short": "Global wallet code.",
   "icon": "gift",
   "grad": "from-amber-500 to-orange-600",
   "categoryName": "Gift Cards"
  },
  "shop-card-50": {
   "slug": "shop-card-50",
   "name": "Online Shop Card — $50",
   "category": "giftcards",
   "price": 6400,
   "regular": 6800,
   "stock": 0,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "US region shopping card.",
   "icon": "gift",
   "grad": "from-amber-500 to-orange-600",
   "categoryName": "Gift Cards"
  },
  "vpn-pro-1y": {
   "slug": "vpn-pro-1y",
   "name": "SwiftVPN Pro — 1 Year",
   "category": "vpn",
   "price": 1190,
   "regular": 2000,
   "stock": 40,
   "badge": "HOT",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "No-logs VPN, 6 devices.",
   "icon": "shield",
   "grad": "from-stone-700 to-stone-900",
   "categoryName": "VPN & Security"
  },
  "design-pro-1y": {
   "slug": "design-pro-1y",
   "name": "CanvasPro Design — 1 Year",
   "category": "design",
   "price": 1490,
   "regular": 2500,
   "stock": 11,
   "badge": "BEST SELLING",
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    }
   ],
   "short": "Templates, brand kit, background remover.",
   "icon": "pen",
   "grad": "from-red-600 to-pink-600",
   "categoryName": "Design & AI Tools"
  },
  "ai-writer-1m": {
   "slug": "ai-writer-1m",
   "name": "AI Writer Plus — 1 Month",
   "category": "design",
   "price": 1790,
   "regular": 2300,
   "stock": 5,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "account_email",
     "label": "Account email (for activation)",
     "type": "email",
     "required": true,
     "placeholder": "you@example.com"
    }
   ],
   "short": "Priority access, advanced models.",
   "icon": "pen",
   "grad": "from-red-600 to-pink-600",
   "categoryName": "Design & AI Tools"
  },
  "cloud-2tb-1y": {
   "slug": "cloud-2tb-1y",
   "name": "CloudDrive 2 TB — 1 Year",
   "category": "cloud",
   "price": 2490,
   "regular": 3200,
   "stock": 8,
   "badge": null,
   "min": 1,
   "max": 10,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "Secure cloud storage + sync.",
   "icon": "cloud",
   "grad": "from-teal-600 to-emerald-700",
   "categoryName": "Cloud & Storage"
  },
  "course-laravel": {
   "slug": "course-laravel",
   "name": "Laravel Mastery — Video Course",
   "category": "courses",
   "price": 990,
   "regular": 2500,
   "stock": 999,
   "badge": "NEW",
   "min": 1,
   "max": 1,
   "fields": [
    {
     "key": "note",
     "label": "Note for seller (optional)",
     "type": "textarea",
     "required": false,
     "placeholder": "Anything we should know?"
    }
   ],
   "short": "40 hours, lifetime access.",
   "icon": "book",
   "grad": "from-red-800 to-stone-900",
   "categoryName": "Courses & eBooks"
  }
 },
 "payments": [
  {
   "key": "wallet",
   "name": "Wallet balance",
   "type": "wallet",
   "color": "bg-stone-900",
   "short": "W",
   "instructions": "Pay instantly from your Vendora wallet. Top up anytime from your account."
  },
  {
   "key": "bkash",
   "name": "bKash",
   "type": "manual",
   "color": "bg-pink-600",
   "short": "bK",
   "number": "01XXXXXXXXX",
   "account": "Personal",
   "instructions": "Open bKash → Send Money → enter the number below → pay the exact amount → copy the Transaction ID."
  },
  {
   "key": "nagad",
   "name": "Nagad",
   "type": "manual",
   "color": "bg-orange-600",
   "short": "N",
   "number": "01XXXXXXXXX",
   "account": "Personal",
   "instructions": "Open Nagad → Send Money → enter the number below → pay the exact amount → keep the TrxID."
  },
  {
   "key": "rocket",
   "name": "Rocket",
   "type": "manual",
   "color": "bg-violet-700",
   "short": "R",
   "number": "01XXXXXXXXX-X",
   "account": "Personal",
   "instructions": "Dial *322# or use the Rocket app → Send Money → pay the exact amount → note the TxnID."
  },
  {
   "key": "card",
   "name": "Card / Online",
   "type": "gateway",
   "color": "bg-brand-700",
   "short": "C",
   "instructions": "You'll be redirected to the secure payment gateway (Visa, Mastercard, mobile banking)."
  }
 ],
 "coupons": {
  "SAVE10": {
   "type": "percent",
   "value": 10,
   "min": 500,
   "label": "10% off applied!"
  },
  "FLAT100": {
   "type": "fixed",
   "value": 100,
   "min": 1000,
   "label": "৳100 off applied!"
  }
 }
};
