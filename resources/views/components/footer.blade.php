<footer class="border-t border-border/60 bg-gray-100/40 mt-24">
  <div class="container mx-auto px-6 py-12 grid gap-10 md:grid-cols-4">

    <div class="space-y-3">
      <a href="{{ route('home') }}" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl shadow-soft gradient-brand flex items-center justify-center overflow-hidden shrink-0">
          <img src="/images/logo.jpg" alt="Bonoan's Dental Clinic" class="w-full h-full object-cover" />
        </div>
        <span class="font-bold text-lg text-brand-navy leading-tight" style="font-family: Georgia, serif;">Bonoan's Dental Clinic</span>
      </a>
      <p class="text-sm text-gray-500 max-w-xs">
        Your trusted neighbourhood dental care, powered by a modern patient-first platform.
      </p>
    </div>

    <div>
      <div class="font-semibold mb-3 text-sm">Clinic</div>
      <ul class="space-y-2 text-sm text-gray-500">
        <li><a href="{{ route('about') }}" class="hover:text-black">About</a></li>
        <li><a href="{{ route('services') }}" class="hover:text-black">Services</a></li>
        <li><a href="{{ route('contact') }}" class="hover:text-black">Contact</a></li>
      </ul>
    </div>

    <div>
      <div class="font-semibold mb-3 text-sm">Patients</div>
      <ul class="space-y-2 text-sm text-gray-500">
        <li><a href="{{ route('register') }}" class="hover:text-black">Book online</a></li>
        <li><a href="{{ route('login') }}" class="hover:text-black">Patient portal</a></li>
      </ul>
    </div>

    <div>
      <div class="font-semibold mb-3 text-sm">Visit us</div>
      <ul class="space-y-2 text-sm text-gray-500">
        <li>Bonoan, Dagupan City</li>
        <li>Mon–Sat · 9am – 6pm</li>
        <li>(075) 000-0000</li>
      </ul>
    </div>

  </div>

  <div class="border-t border-border/60 py-5 text-center text-xs text-gray-500">
    © 2026 Bonoan's Dental Clinic. All rights reserved.
  </div>
</footer>