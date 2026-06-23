<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral & rewards program
    |--------------------------------------------------------------------------
    |
    | Tunable rules for the "refer a friend" rewards program. Everything the
    | program does (how much people earn, when they earn it, how it can be
    | spent) is driven from here so the clinic can adjust promos without code
    | changes. Points are the internal unit; `peso_per_point` converts them to
    | a peso discount when redeemed against a bill.
    |
    */

    // Master switch — turn the whole program on/off.
    'enabled' => true,

    // Internal points → peso value when redeemed (1 point = ₱1 by default).
    'peso_per_point' => 1,

    // Points the EXISTING patient earns when someone they referred qualifies.
    'referrer_points' => 200,

    // Welcome points the NEW (referred) patient earns once they qualify.
    'welcome_points' => 100,

    /*
    | What counts as a "successful" referral. To prevent people farming rewards
    | from fake sign-ups, the reward only fires on a real qualifying visit:
    |   'completed' — the referred patient has a completed appointment.
    | If `require_payment` is true, that visit must also have a recorded payment
    | (i.e. they actually paid for a service), which is the strictest, most
    | abuse-resistant setting.
    */
    'qualify_on' => 'completed',
    'require_payment' => false,

    /*
    | Redemption rules (spending points as a discount on a bill).
    */
    // Smallest redemption allowed, in points (₱100 at 1:1).
    'min_redeem_points' => 100,
    // A redemption can cover at most this % of an appointment's total charge,
    // so points top up a bill but never make a whole visit free.
    'max_redeem_percent' => 50,

    /*
    | Housekeeping.
    */
    // Earned points lapse after this many months of account inactivity
    // (0 = never expire). Enforced by the `rewards:expire` command + scheduler.
    'points_expire_months' => 12,

    // Optional cap on how many successful referrals one patient can be
    // rewarded for per calendar month (0 = unlimited).
    'monthly_referral_cap' => 0,

];
