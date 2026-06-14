<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About — Bonoan's Dental Clinic</title>
    <meta name="description" content="Learn about our mission, our team and our commitment to gentle, modern dental care.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-blue': '#2563eb', // Replace with your actual brand color
                        'muted-foreground': '#64748b'
                    },
                    fontFamily: {
                        'display': ['sans-serif'] // Add your preferred display font here
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen flex flex-col text-slate-800 relative">
        <!-- Background Blur Blue Fade -->
<div class="fixed inset-0 -z-10">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-500/30 via-sky-200/20 to-transparent blur-3xl"></div>
  <div class="absolute inset-0 bg-gradient-to-t from-white via-white/70 to-transparent"></div>
</div>

    <div class="min-h-screen flex flex-col">
     
      <!-- Header -->
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="container mx-auto px-6 flex items-center justify-between h-16">

        <!-- Left: Logo + Brand -->
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl shadow-soft gradient-brand flex items-center justify-center overflow-hidden">
                <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
            </div>

            <a href="/" class="font-bold text-xl text-brand-navy leading-tight" style="font-family: Georgia, serif;">
                <span class="block">Bonoan's Dental Clinic</span>
                <span class="block text-xs uppercase font-extralight tracking-widest text-black"
                    style="font-family: system-ui, sans-serif; text-shadow: 0 0 8px rgba(0,0,0,0.25);">
                    Your smile. Our pride.
                </span></a>
        </div>

        <!-- Center Nav -->
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <a href="/" class="hover:text-brand-blue transition">Home</a>
            <a href="/services" class="hover:text-brand-blue transition">Services</a>
            <a href="/about" class="hover:text-brand-blue transition">About</a>
            <a href="/contact" class="hover:text-brand-blue transition">Contact</a>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center gap-2">
            <a href="/auth" class="text-sm font-medium text-slate-600 hover:text-brand-blue transition px-3 py-1.5">
                Log in
            </a>
            <a href="/auth?mode=signup"
                class="gradient-brand text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-brand hover:opacity-90 transition">
                Book now
            </a>
        </div>

    </div>
</header>

        <main class="container mx-auto px-6 py-20 max-w-3xl">
            <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Our story</div>
            <h1 class="font-display text-5xl font-bold mt-3">About Bonoan's Dental Clinic</h1>
            
            <p class="mt-6 text-lg text-muted-foreground leading-relaxed">
                Founded with the belief that great dentistry should feel warm and human, Bonoan's Dental
                Clinic has been serving families in Dagupan City for over a decade. We blend
                state-of-the-art tools with a patient-first approach.
            </p>

            <h2 class="font-display text-2xl font-bold mt-12">Our mission</h2>
            <p class="mt-3 text-muted-foreground">
                To make excellent dental care accessible, transparent, and a little bit delightful.
            </p>

            <h2 class="font-display text-2xl font-bold mt-12">Our values</h2>
            <ul class="mt-3 space-y-2 text-muted-foreground list-disc pl-6">
                <li>Gentle, judgement-free care</li>
                <li>Honest pricing and clear treatment plans</li>
                <li>Modern, sterile facilities</li>
                <li>Lifelong learning and continuous improvement</li>
            </ul>
        </main>

        <footer class="w-full p-6 border-t mt-auto">
            <div class="container mx-auto">Site Footer</div>
        </footer>
    </div>

</body>
</html>