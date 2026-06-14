<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Services & Pricing — Bonoan's Dental Clinic</title>
  <meta name="description" content="Explore our dental services: cleanings, fillings, root canals, whitening, implants and more — with transparent prices." />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { display: ['Georgia', 'serif'] },
          colors: {
            'brand-blue': '#3B82F6',
            'brand-green': '#10B981',
            'brand-navy': '#1E3A5F',
          },
          boxShadow: {
            'brand': '0 8px 32px -4px rgba(59,130,246,0.18)',
            'soft': '0 2px 16px -2px rgba(0,0,0,0.08)',
          },
        }
      }
    }
  </script>
  <style>
    .gradient-brand { background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%); }
    .text-gradient-brand {
      background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col text-slate-800 relative">
        <!-- Background Blur Blue Fade -->
<div class="fixed inset-0 -z-10">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-500/30 via-sky-200/20 to-transparent blur-3xl"></div>
  <div class="absolute inset-0 bg-gradient-to-t from-white via-white/70 to-transparent"></div>
</div>

@include('components.header')

  <!-- Main -->
  <main class="container mx-auto px-6 py-20 flex-1">
    <div class="max-w-2xl">
      <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Services</div>
      <h1 class="font-display text-5xl font-bold mt-3">Treatments &amp; pricing</h1>
      <p class="mt-4 text-slate-500">Transparent prices. No surprises.</p>
    </div>

    <!-- Loading state (hidden once cards render) -->
    <div id="loading" class="mt-12 grid md:grid-cols-2 gap-5">
      <!-- Skeleton cards -->
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 h-28 animate-pulse"></div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 h-28 animate-pulse"></div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 h-28 animate-pulse"></div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 h-28 animate-pulse"></div>
    </div>

    <!-- Services grid (populated by JS) -->
    <div id="services-grid" class="mt-12 grid md:grid-cols-2 gap-5 hidden"></div>

    <!-- Error state -->
    <div id="error-state" class="mt-12 hidden">
      <p class="text-slate-500 text-sm">Could not load services. Please try again later.</p>
    </div>
  </main>

  <!-- Footer -->
@include('components.footer')

  <script>
    // ─── Supabase config ───────────────────────────────────────────────────────
    // Replace these two values with your actual project URL and anon key.
    const SUPABASE_URL  = "https://YOUR_PROJECT.supabase.co";
    const SUPABASE_ANON = "YOUR_ANON_KEY";

    // ─── Fallback mock data (shown when Supabase isn't configured) ────────────
    const MOCK_SERVICES = [
      { id: 1, name: "Dental Cleaning",        description: "Professional scaling and polishing to remove plaque and tartar.",          duration_minutes: 45,  base_price: 800  },
      { id: 2, name: "Tooth Extraction",       description: "Simple or surgical removal of a damaged or impacted tooth.",               duration_minutes: 30,  base_price: 1200 },
      { id: 3, name: "Composite Filling",      description: "Tooth-coloured resin restoration for cavities.",                          duration_minutes: 45,  base_price: 1500 },
      { id: 4, name: "Root Canal Treatment",   description: "Complete cleaning, shaping and sealing of infected root canals.",         duration_minutes: 90,  base_price: 5000 },
      { id: 5, name: "Dental Crown",           description: "Porcelain or zirconia cap that restores a broken or weakened tooth.",     duration_minutes: 60,  base_price: 7500 },
      { id: 6, name: "Teeth Whitening",        description: "In-office bleaching for a noticeably brighter smile.",                   duration_minutes: 60,  base_price: 4500 },
      { id: 7, name: "Dental Implant",         description: "Titanium post with crown — a permanent replacement for a missing tooth.", duration_minutes: 120, base_price: 35000 },
      { id: 8, name: "Orthodontic Braces",     description: "Metal or ceramic braces to align teeth and correct your bite.",           duration_minutes: 60,  base_price: 30000 },
    ];

    // ─── Render a single service card ─────────────────────────────────────────
    function renderCard(s) {
      return `
        <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start justify-between gap-4 hover:shadow-brand transition">
          <div class="flex-1 min-w-0">
            <div class="font-display font-semibold text-lg">${s.name}</div>
            <p class="text-sm text-slate-500 mt-1">${s.description ?? ""}</p>
            <div class="text-xs text-slate-400 mt-2">~ ${s.duration_minutes} mins</div>
          </div>
          <div class="text-right shrink-0">
            <div class="text-xs uppercase tracking-wider text-slate-400">Starting at</div>
            <div class="font-display font-bold text-2xl text-gradient-brand">
              ₱${Number(s.base_price).toLocaleString()}
            </div>
          </div>
        </div>`;
    }

    // ─── Load services ─────────────────────────────────────────────────────────
    async function loadServices() {
      const grid    = document.getElementById("services-grid");
      const loading = document.getElementById("loading");
      const errEl   = document.getElementById("error-state");

      // If Supabase isn't configured, fall straight to mock data
      if (SUPABASE_URL.includes("YOUR_PROJECT")) {
        loading.classList.add("hidden");
        grid.innerHTML = MOCK_SERVICES.map(renderCard).join("");
        grid.classList.remove("hidden");
        return;
      }

      try {
        const url = `${SUPABASE_URL}/rest/v1/services?active=eq.true&order=base_price&select=*`;
        const res = await fetch(url, {
          headers: {
            "apikey": SUPABASE_ANON,
            "Authorization": `Bearer ${SUPABASE_ANON}`,
          }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        loading.classList.add("hidden");
        grid.innerHTML = data.map(renderCard).join("");
        grid.classList.remove("hidden");
      } catch (err) {
        console.error("Failed to load services:", err);
        loading.classList.add("hidden");
        errEl.classList.remove("hidden");
      }
    }

    loadServices();
  </script>
</body>
</html>