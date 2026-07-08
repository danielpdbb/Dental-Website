# July 8, 2026 Revisions and Testing Guide

This document consolidates the revisions completed on July 8, 2026 and explains how to
verify each one in the browser. For the broader system walkthrough, see
[WALKTHROUGH_AND_TESTING.md](WALKTHROUGH_AND_TESTING.md).

## Test setup

The local demo database was rebuilt and both ML systems were retrained on July 8:

```bash
php artisan migrate:fresh --seed
php artisan ml:recommend:train
php artisan ml:scheduling:train
php artisan serve
```

> `migrate:fresh` deletes existing tables. Only run it against a local/demo database.

Useful accounts:

| Role | Login | Password |
|---|---|---|
| Management | `dental@admin.com` | `Bonoan123!` |
| Receptionist | `reception@bonoandental.test` | `Password123!` |
| Dentist | `dentist1@bonoandental.test` | `Password123!` |
| Patient | `patient1@bonoandental.test` | `Password123!` |

## 1. Data, timezone, and ML

Changes:

- The application timezone is `Asia/Manila`.
- Demo appointments were freshly seeded into realistic half-hour clinic slots.
- The old 12:26 AM Elena Santos schedule entry was removed with the fresh rebuild.
- Procedure-recommendation and predictive-scheduling models were retrained.
- Final data audit found 657 appointments and no appointments before 9:00 AM or at/after
  5:00 PM.

How to check:

1. Run `php artisan migrate:status`; every migration should say **Ran**.
2. Log in as a dentist and open `/clinic/my-schedule`; appointment tiles should use
   realistic daytime hours.
3. Confirm there is no July 8 midnight appointment for Dr. Elena Santos.

## 2. Registration

Changes:

- Birthday uses compact Month / Day / Year dropdowns with small, consistent gaps.
- Invalid/future birthdays and patients aged eight or younger are rejected.
- Address uses Philippine fields: street, barangay, city/municipality, province, ZIP.

How to check:

1. Log out and open `/register`.
2. Inspect the compact birthday controls.
3. Try February 30 and a birth year that makes the user eight years old.
4. Submit without a barangay, then complete a valid registration.

## 3. Dentist availability

Changes:

- `/clinic/availability` is available to dentists and management.
- Specific-date overrides appear before the weekly schedule.
- The calendar is now the only date picker; the redundant visible date input was removed.
- Clicking a date shows a readable selected-date summary and enables **Save this date**.
- Types use the clearer label **Custom hours open**.
- Opening/closing controls are readable 30-minute dropdown choices.
- Management has a wider dentist dropdown.
- Availability resolves in this order: specific date, weekly rule, clinic default.

How to check:

1. Log in as `dentist1` and open `/clinic/availability`.
2. Confirm there is no second visible date input beside the calendar.
3. Click a future date; verify the selected-date summary changes and Save becomes enabled.
4. Choose **Custom hours open**, select opening/closing times, and save.
5. Set a weekly day to Off and verify booking calendars mark that day unavailable.
6. Log in as management and confirm the dentist selector fits the full dentist name.

## 4. Patient appointment booking and rescheduling

Changes:

- `/portal/appointments/book` uses a two-column service/dentist and calendar/time layout.
- Month calendars distinguish available, fully booked, closed, and past days.
- Multiple services correctly sum price and duration.
- Selecting a time shows the actual start, end, and total minutes.
- Every slot consumed by the procedure duration is highlighted.
- Rescheduling uses the same month calendar and slot tiles.
- Review modals show **Services** and a human-readable **Schedule**, not request field names.

How to check:

1. Log in as `patient1` and open `/portal/appointments/book`.
2. Select two services and a dentist.
3. Pick a green calendar day and select 10:30 AM.
4. Verify the summary uses the combined duration and highlights tiles through the end.
5. Submit and inspect the confirmation modal formatting.
6. Open the new appointment's Reschedule page and verify the same calendar experience.

## 5. Front-desk appointment creation

Changes:

- `/clinic/appointments/create` is a two-column booking workspace.
- Existing patients can be filtered by name or phone using the search field beside the
  dropdown, avoiding a long manual scroll.
- Selecting an existing patient shows locked first name, last name, and phone fields.
- Patient details, walk-in state, services, dentist, notes, and calendar context survive
  asynchronous form refreshes.
- Walk-ins must provide first name, last name, and phone before Create is enabled.
- Walk-ins select a real available slot instead of being recorded at the current minute.
- Multi-service duration, end time, and consumed-slot highlighting match the portal.
- The confirmation modal no longer exposes `service_ids[]` or raw schedule values.

How to check:

1. Log in as reception and open `/clinic/appointments/create`.
2. Type part of a patient name or phone; open the dropdown and confirm it is filtered.
3. Select the patient and confirm the three locked fields are filled.
4. Change service, dentist, month, date, and slot; the patient must remain selected.
5. Change patient, tick Walk-in, and leave identity fields empty; Create stays disabled.
6. Fill all three fields, select multiple services and a slot, then review the modal.

## 6. Clinic appointment management and rescheduling

Changes:

- Appointment tabs separate Active, Billed, and Finished visits.
- Status, dentist, date, Today/Tomorrow, and patient-name/phone filters work together.
- Receptionists no longer see dentist-only Record treatment actions.
- Follow-up visits have visible badges in list and detail views.
- Clinic rescheduling uses the calendar and duration-aware slot highlighting.

How to check:

1. Open `/clinic/appointments` as reception.
2. Exercise each tab and combine status, dentist, date, and search filters.
3. Open a booked visit and reschedule it from its management page.
4. Confirm the calendar is clear and the chosen duration range is highlighted.

## 7. Dentist schedule and follow-up visibility

Changes:

- `/clinic/my-schedule` defaults to a Google-Calendar-style month grid.
- Month and Day views can be switched at any time.
- Appointment tiles show time, patient, procedure, and follow-up context.
- Clicking a tile opens a modal with status, duration, patient details, Treatment, and
  View patient actions.
- Follow-up banners also appear in the dentist's treatment workspace.

How to check:

1. Log in as `dentist1` and open `/clinic/my-schedule`.
2. Navigate months and click an appointment tile.
3. Confirm the modal actions work.
4. Switch to Day to inspect the timeline.
5. Open a follow-up and verify its badge/banner appears in both schedule and treatment.

## 8. Partial payments and outstanding balances

Changes:

- Older partially paid bills stay in the portal's current outstanding section until the
  remaining balance reaches zero, even when the visit date is in the past.
- **Show breakdown** lists itemized charges, payments already received, and remaining due.
- Patients can make a partial online payment directly inside the breakdown modal.
- Printable statements show total, paid to date, outstanding balance, payment history,
  and a Partially Paid stamp where appropriate.

How to check:

1. Bill a completed visit from the clinic and record only part of its total.
2. Log in as that patient and open `/portal/appointments`.
3. Open **Show breakdown** and verify charge, payment history, and balance.
4. Enter a smaller amount and use **Pay online** from the modal.
5. Print the clinic statement and verify the payment summary.

## 9. Follow-up visits and consolidated invoices

Changes:

- Appointments can link to a parent visit as a follow-up.
- Follow-up badges/banners appear in patient, reception, management, dentist schedule,
  appointment detail, and treatment views.
- Performed follow-up procedures append to the original billing statement.
- Previous payments remain credited; the account reopens only for the new balance.
- Staff can print the complete consolidated statement or an isolated follow-up summary
  showing brought-forward balance, new charges, payments since the visit, and remaining
  balance. Once settled, the isolated view can be printed as a separate follow-up invoice.

How to check:

1. Create a partially paid original appointment.
2. Book a new visit and select the original in **Follow-up of a previous visit?**.
3. As the dentist, perform an adjustment plus another procedure and endorse it.
4. As reception, issue billing; verify the charges append to the original statement.
5. Print the full statement and the follow-up-specific summary/invoice.

## 10. Referral letters and final UI consistency

Changes:

- Clinic staff can prepare numbered referral letters containing addressee, clinical notes,
  allergies, issuer, date, and privacy footer.
- Patients receive a notification and can view the issued letter.
- Confirmation previews consistently format dates and appointment schedules.

How to check:

1. Submit a referral request from `/portal/referrals`.
2. Open `/clinic/referrals`, prepare and issue its letter, then view the PDF.
3. Return to the patient account and verify the notification and letter link.

## Verification completed

- All migrations applied successfully after a fresh rebuild.
- Procedure-recommendation and scheduling model training completed.
- PHP lint passed on changed PHP files.
- Blade templates compiled successfully.
- Automated tests passed.
- `git diff --check` reported no whitespace errors.
