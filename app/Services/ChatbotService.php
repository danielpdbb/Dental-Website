<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Knowledge-base assistant for the public site. Matches the visitor's message to an
 * intent and answers using live clinic data (services, prices, hours). No external
 * AI key required. To upgrade to a real LLM later, swap the body of answer() to call
 * your provider and keep this as the fallback.
 *
 * Returns: ['reply' => string, 'actions' => [['label','url']], 'suggestions' => [string]].
 */
class ChatbotService
{
    public function __construct(private PredictiveScheduler $scheduler) {}

    public function answer(string $message): array
    {
        $m = Str::lower(trim($message));

        if ($m === '') {
            return $this->fallback();
        }

        // Anything that names a dentist, or asks about schedules/availability, goes to
        // the availability handler first (these overlap with other keywords).
        $dentist = $this->matchDentist($m);
        if ($dentist || $this->mentionsAvailability($m)) {
            return $this->availability($m, $dentist);
        }

        // "How much is teeth whitening?" → answer with the live price.
        if ($service = $this->matchService($m)) {
            return [
                'reply' => "{$service->name} is ₱".number_format($service->price, 2).
                    " and takes about {$service->duration_minutes} minutes.\nYou can book it online anytime.",
                'actions' => [['label' => 'Book this service', 'url' => url('/services')]],
                'suggestions' => ['How do I book?', 'Payment options', 'What are your hours?'],
            ];
        }

        return $this->{$this->detectIntent($m)}();
    }

    /**
     * Default starter shown when the chat opens.
     */
    public function greeting(): array
    {
        return [
            'reply' => "Hi! 👋 I'm the Bonoan's Dental Clinic assistant. I can help with our "
                ."services & prices, booking, our dentists, available schedules, payments and more.\nWhat would you like to know?",
            'actions' => [],
            'suggestions' => ['Tell me about your clinic', 'Who are your dentists?', 'What times are available?', 'View services & prices'],
        ];
    }

    private function about(): array
    {
        return [
            'reply' => "Bonoan's Dental Clinic is a modern, family-friendly clinic in Bonoan, Dagupan City, Pangasinan. "
                ."We provide preventive, restorative, cosmetic, surgical and orthodontic care — from cleanings and "
                ."fillings to braces and implants — with easy online booking, digital records and secure payments.\n"
                .'Our motto: “Your smile. Our passion. Our pride.”',
            'actions' => [['label' => 'About us', 'url' => url('/about')], ['label' => 'View services', 'url' => url('/services')]],
            'suggestions' => ['Who are your dentists?', 'What are your hours?', 'How do I book?'],
        ];
    }

    private function dentists(): array
    {
        $names = User::where('role', UserRole::Dentist)->orderBy('name')->pluck('name');

        $reply = $names->isEmpty()
            ? 'Our dentist roster is being updated — please check back soon.'
            : "Our attending dentists are:\n".$names->map(fn ($n) => "• {$n}")->implode("\n");

        return [
            'reply' => $reply,
            'actions' => [['label' => 'Book an appointment', 'url' => url('/services')]],
            'suggestions' => array_merge(
                ['What times are available?', 'How do I book?'],
                $names->take(2)->map(fn ($n) => "Availability for {$n}")->all(),
            ),
        ];
    }

    /**
     * Available schedules — for a named dentist, or the soonest opening per dentist.
     */
    private function availability(string $m, ?User $dentist = null): array
    {
        $duration = (int) config('clinic.slot_minutes', 30);
        $dentist ??= $this->matchDentist($m);

        if ($dentist) {
            $slots = $this->scheduler->suggestSlots($dentist, $duration, now(), 6);

            if ($slots->isEmpty()) {
                return [
                    'reply' => "{$dentist->name} has no open slots in the next few weeks. Try another dentist or date.",
                    'actions' => [['label' => 'Book an appointment', 'url' => url('/services')]],
                    'suggestions' => ['Who are your dentists?', 'How do I book?'],
                ];
            }

            $list = $slots->map(fn ($s) => '• '.$s->format('D, M j · g:i A'))->implode("\n");

            return [
                'reply' => "Next available times for {$dentist->name}:\n{$list}",
                'actions' => [['label' => 'Book now', 'url' => url('/services')]],
                'suggestions' => ['How do I book?', 'Who are your dentists?', 'View services & prices'],
            ];
        }

        $dentists = User::where('role', UserRole::Dentist)->orderBy('name')->get();
        if ($dentists->isEmpty()) {
            return $this->fallback();
        }

        $lines = $dentists->map(function ($d) use ($duration) {
            $slot = $this->scheduler->suggestSlots($d, $duration, now(), 1)->first();

            return '• '.$d->name.' — '.($slot ? $slot->format('D, M j · g:i A') : 'fully booked');
        })->implode("\n");

        return [
            'reply' => "Soonest opening for each dentist:\n{$lines}\nAsk “What's available for Dr. <name>?” for more times.",
            'actions' => [['label' => 'Book an appointment', 'url' => url('/services')]],
            'suggestions' => array_merge(
                ['How do I book?'],
                $dentists->take(2)->map(fn ($d) => "Availability for {$d->name}")->all(),
            ),
        ];
    }

    private function detectIntent(string $m): string
    {
        $intents = [
            'greeting' => ['hi', 'hello', 'hey', 'kumusta', 'good morning', 'good afternoon', 'good evening'],
            'about' => ['about', 'tell me about', 'who are you', 'your clinic', 'the clinic', 'background', 'company'],
            'dentists' => ['dentist', 'doctor', 'attending', 'specialist', 'staff'],
            'hours' => ['hour', 'open', 'close', 'what time', 'operating'],
            'services' => ['service', 'price', 'cost', 'how much', 'treatment', 'offer', 'procedure', 'menu'],
            'booking' => ['book', 'appointment', 'reserve', 'schedule an', 'set an', 'walk in', 'walk-in'],
            'payment' => ['pay', 'payment', 'gcash', 'online', 'installment', 'balance', 'bill', 'card', 'cash'],
            'location' => ['where', 'location', 'address', 'located', 'map', 'direction', 'branch'],
            'contact' => ['contact', 'phone', 'number', 'email', 'call', 'reach', 'message'],
            'account' => ['login', 'log in', 'register', 'sign up', 'signup', 'account', 'password', 'forgot', 'verify'],
            'rewards' => ['referral', 'refer', 'reward', 'points', 'discount', 'promo'],
            'records' => ['record', 'history', 'result', 'x-ray', 'xray'],
            'emergency' => ['emergency', 'urgent', 'severe', 'toothache', 'pain', 'bleeding', 'swelling', 'broke', 'broken', 'knocked'],
            'thanks' => ['thank', 'salamat'],
        ];

        $best = 'fallback';
        $bestScore = 0;
        foreach ($intents as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $k) {
                if (str_contains($m, $k)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $intent;
            }
        }

        return $best;
    }

    private function hours(): array
    {
        return [
            'reply' => "We're open {$this->hoursText()}.\nWalk-ins are welcome, but booking online means a shorter wait.",
            'actions' => [['label' => 'Book an appointment', 'url' => url('/services')]],
            'suggestions' => ['View services & prices', 'How do I book?', 'Where are you located?'],
        ];
    }

    private function services(): array
    {
        $list = Service::active()->orderBy('price')->get()
            ->map(fn ($s) => "• {$s->name} — ₱".number_format($s->price, 2))
            ->implode("\n");

        return [
            'reply' => ($list ? "Here are our services & prices:\n{$list}" : 'Our service list is being updated.')
                ."\nTip: you can tap “Book this service” on the Services page.",
            'actions' => [['label' => 'See full services page', 'url' => url('/services')]],
            'suggestions' => ['How do I book?', 'Payment options', 'Do you offer braces?'],
        ];
    }

    private function booking(): array
    {
        return [
            'reply' => "Booking is easy:\n1) Create a patient account (or log in)\n2) Go to “Book”\n"
                ."3) Pick one or more services, a dentist and an open time slot\n4) Confirm — done!\n"
                .'The schedule automatically blocks the time your chosen services need.',
            'actions' => [
                ['label' => 'Create an account', 'url' => url('/register')],
                ['label' => 'Log in', 'url' => url('/login')],
            ],
            'suggestions' => ['View services & prices', 'What are your hours?', 'Payment options'],
        ];
    }

    private function payment(): array
    {
        return [
            'reply' => "You can pay at the clinic (cash/card) or online. After your visit the front desk "
                ."issues a billing statement, then you can pay:\n• In person\n• Online via PayMongo (card / GCash)\n"
                ."• By scanning a GCash QR at the desk\nPartial / installment payments are supported too.",
            'actions' => [['label' => 'Patient portal', 'url' => url('/login')]],
            'suggestions' => ['How do I book?', 'View services & prices', 'Rewards & referrals'],
        ];
    }

    private function location(): array
    {
        return [
            'reply' => "We're located in Bonoan, Dagupan City, Pangasinan. See our Contact page for the map and details.",
            'actions' => [['label' => 'Contact & map', 'url' => url('/contact')]],
            'suggestions' => ['What are your hours?', 'How do I book?'],
        ];
    }

    private function contact(): array
    {
        return [
            'reply' => 'You can reach us through our Contact page, or just book and message us through your patient portal.',
            'actions' => [['label' => 'Contact us', 'url' => url('/contact')]],
            'suggestions' => ['What are your hours?', 'Where are you located?', 'How do I book?'],
        ];
    }

    private function account(): array
    {
        return [
            'reply' => "New patients can create an account and verify their email to start booking. "
                ."Forgot your password? Use the “Forgot password?” link on the login page to reset it.",
            'actions' => [
                ['label' => 'Create an account', 'url' => url('/register')],
                ['label' => 'Log in', 'url' => url('/login')],
            ],
            'suggestions' => ['How do I book?', 'View services & prices'],
        ];
    }

    private function rewards(): array
    {
        return [
            'reply' => "Yes! Refer a friend and you both earn reward points after their first completed visit. "
                ."Points convert to peso discounts on future bills. You'll find your referral code in your patient portal.",
            'actions' => [['label' => 'Open patient portal', 'url' => url('/login')]],
            'suggestions' => ['How do I book?', 'Payment options'],
        ];
    }

    private function records(): array
    {
        return [
            'reply' => 'Once you have an account, your treatment history and any procedure recommendations are available '
                .'in your patient portal under “My record”.',
            'actions' => [['label' => 'Log in', 'url' => url('/login')]],
            'suggestions' => ['How do I book?', 'View services & prices'],
        ];
    }

    private function emergency(): array
    {
        return [
            'reply' => "If you're in severe pain, have heavy bleeding or facial swelling, please contact the clinic right "
                ."away or visit during {$this->hoursText()}. For a life-threatening emergency, call your local emergency services.",
            'actions' => [['label' => 'Contact the clinic', 'url' => url('/contact')]],
            'suggestions' => ['What are your hours?', 'Where are you located?'],
        ];
    }

    private function thanks(): array
    {
        return [
            'reply' => "You're welcome! 😊 Is there anything else I can help you with?",
            'actions' => [],
            'suggestions' => ['View services & prices', 'How do I book?', 'What are your hours?'],
        ];
    }

    private function fallback(): array
    {
        return [
            'reply' => "I'm not sure about that one yet, but I can help with services & prices, booking, payments, "
                ."clinic hours and location. You can also reach the clinic through our Contact page.",
            'actions' => [['label' => 'Contact us', 'url' => url('/contact')]],
            'suggestions' => ['View services & prices', 'How do I book?', 'What are your hours?', 'Where are you located?'],
        ];
    }

    private function hoursText(): string
    {
        $days = config('clinic.open_days', [1, 2, 3, 4, 5, 6]);
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $from = $names[min($days) - 1] ?? 'Mon';
        $to = $names[max($days) - 1] ?? 'Sat';
        $open = Carbon::parse(config('clinic.open_time', '09:00'))->format('g:i A');
        $close = Carbon::parse(config('clinic.close_time', '17:00'))->format('g:i A');

        return "{$from}–{$to}, {$open}–{$close}";
    }

    private function mentionsAvailability(string $m): bool
    {
        foreach (['available', 'availab', 'vacant', 'free slot', 'open slot', 'open time', 'schedule', 'schedules', 'any slot', 'next slot', 'slots', 'time slot'] as $k) {
            if (str_contains($m, $k)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a dentist the visitor named (e.g. "Dr. Santos") by matching name words.
     */
    private function matchDentist(string $m): ?User
    {
        $stop = ['dr', 'doctor', 'dentist', 'the', 'for', 'this', 'with'];

        foreach (User::where('role', UserRole::Dentist)->get() as $dentist) {
            foreach (preg_split('/\s+/', Str::lower($dentist->name)) as $word) {
                $word = trim($word, '.');
                if (strlen($word) > 2 && ! in_array($word, $stop, true) && str_contains($m, $word)) {
                    return $dentist;
                }
            }
        }

        return null;
    }

    /**
     * Match a service the visitor named (e.g. "whitening", "braces") to answer its price.
     */
    private function matchService(string $m): ?Service
    {
        $stop = ['dental', 'tooth', 'teeth', 'treatment', 'and', 'the', 'for', 'how', 'much', 'cost', 'price', 'is', 'a', 'of'];
        $best = null;
        $bestScore = 0;

        foreach (Service::active()->get() as $service) {
            $score = 0;
            foreach (preg_split('/\s+/', Str::lower($service->name)) as $word) {
                if (strlen($word) > 3 && ! in_array($word, $stop, true) && str_contains($m, $word)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $service;
            }
        }

        return $bestScore > 0 ? $best : null;
    }
}
