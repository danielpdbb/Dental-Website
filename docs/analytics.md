# Analytics — how it works (Decision Tree, Regression & the dashboard)

This explains the two machine-learning features and the analytics dashboard: what each
number means, **which database table/column it comes from**, the exact formula/logic, and how
each model was trained.

The system has **three** analytical layers:

1. **Decision Tree** — predicts appointment **attendance** (kept vs missed) → powers predictive
   scheduling and the no-show KPIs.
2. **Regression** — recommends **procedures** from a patient's intake/findings.
3. **Descriptive analytics** — the `/admin/analytics` dashboard (KPIs, charts, pivots, clustering)
   computed directly from the database with SQL.

All ML uses the **Rubix ML** PHP library. Trained models are saved as files in
`storage/app/models/` and loaded at request time; if a model file is missing the app degrades
gracefully (scheduling falls back to plain availability; recommendations are skipped).

---

## 1. Decision Tree — appointment attendance (predictive scheduling)

### What it predicts
For a given appointment/time-slot, the probability it will be **kept** (vs **missed**).
- **kept**  = appointment `status = completed`
- **missed** = `status = no_show` OR `status = cancelled`
- (booked / in-treatment have no outcome yet, so they're excluded from training.)

### The features (the "columns" the tree learns on)
Built by `App\Services\ML\AppointmentFeatureExtractor`. Fixed order, identical for training and
prediction:

| # | Feature | Source (table.column) | Meaning |
|---|---------|------------------------|---------|
| 1 | `lead_time_days` | `appointments.created_at` → `appointments.scheduled_at` | days booked in advance |
| 2 | `day_of_week` | `appointments.scheduled_at` (ISO weekday 1–7) | Mon…Sun |
| 3 | `hour` | `appointments.scheduled_at` (hour) | time of day |
| 4 | `is_walk_in` | `appointments.is_walk_in` | walk-in vs booked |
| 5 | `duration_minutes` | `appointments.duration_minutes` | length |
| 6 | `total_amount` | `appointments.total_amount` | bill size |
| 7 | `patient_age` | `patients.date_of_birth` | age in years |
| 8 | `prior_visits` | count of that patient's earlier `appointments` | history depth |
| 9 | `prior_no_shows` | count of that patient's earlier `appointments` with `status = no_show` | reliability |

> **No data leakage:** `prior_visits` and `prior_no_shows` are computed **chronologically** —
> each training row only "knows" what happened *before* its own `scheduled_at`, never the future.

### How it was trained
Command: `php artisan ml:scheduling:train` (`TrainSchedulingModel`).
1. `AppointmentFeatureExtractor::trainingData()` walks every appointment with a final outcome
   (completed/no-show/cancelled) ordered by date, builds the 9-feature vector + the kept/missed
   label.
2. The data is wrapped in a Rubix `Labeled` dataset and split **80% train / 20% test**
   (`stratifiedSplit(0.8)` keeps the kept:missed ratio in both halves).
3. A **`ClassificationTree`** (max depth 6 by default, `--depth=` to change) is trained.
4. It reports **accuracy** and **F1** on the held-out 20%, and saves to
   `storage/app/models/scheduling.model` (`PersistentModel` + `Filesystem`).
- Needs ≥10 labelled appointments (warns under 40 — more history = better).

### How predictions are used
`App\Services\ML\SchedulingModel`:
- `keepProbability(features)` → P(kept) in 0…1 (reads `proba()['kept']`), or `null` if untrained.
- `riskBadge(p)` → no-show risk = `1 − p`, bucketed:
  - **High** ≥ 50% missed (red)
  - **Medium** 25–49% (amber)
  - **Low** < 25% (green)

Where it shows up:
- **`/clinic/scheduling`** — free slots are listed by availability, each tagged with the
  predicted no-show risk; the highest-attendance slot(s) get the **★ Best** flag. Picking a
  patient scores the risk against *their* `prior_visits`/`prior_no_shows`.
- **No-show risk** KPI on the analytics page and the appointment-detail risk badge.

---

## 2. Regression — procedure recommendation

### What it does
For each of four procedures it outputs a probability the procedure is indicated:
`scaling` (cleaning), `filling`, `root_canal`, `extraction`. One **Logistic Regression** model
per procedure (so it's really four binary regressions).

### The features
Built by `App\Services\ML\IntakeFeatureExtractor` (16 features). Sources depend on the stage:

| Group | Features | Source |
|-------|----------|--------|
| Symptoms | toothache, sensitivity, bleeding_gums, bad_breath, swelling | Stage-1 `appointment_intakes` (patient-answered) |
| Behaviours | brushing_per_day, flosses, smoker, sugar_level (low/med/high→0/1/2), months_since_cleaning | `appointment_intakes` |
| Clinical | visible_plaque, decay_observed, gum_condition (healthy/gingivitis/periodontitis→0/1/2), missing_teeth, existing_fillings | Stage-2 `appointment_findings` (dentist) / `clinical_intakes` baseline |
| Demographic | age | `patients.date_of_birth` |

`App\Services\ML\AppointmentRecommender` builds the vector for each stage:
- **Stage 1** (before the visit) — patient's `appointment_intakes` + clinical baseline fallback.
- **Stage 2** (after the visit) — maps the dentist's `appointment_findings` onto the same vector
  (e.g. `cavity_found → decay_observed`, `gum_inflammation → gum_condition`, `plaque_level → visible_plaque`).

It then takes the highest-scoring procedure, sets a **priority** (High ≥ 0.66, Medium ≥ 0.45,
else Low) and a follow-up window, and stores it as an `appointment_recommendations` row.

### How it was trained (on synthetic data — and why)
Command: `php artisan ml:recommend:train` (`TrainRecommendationModel`).

Real "intake → which procedure" history didn't exist when the feature shipped (intakes are new),
so `App\Services\ML\ProcedureDatasetGenerator` **synthesises a realistic training set**:
1. Generate N (default 600) random but plausible intakes (`randomIntake()` — e.g. ~30% have
   toothache, brushing 0–3×/day, random gum condition, etc.).
2. For each, compute a **clinical-heuristic logistic score** per procedure (`probability()`):
   e.g. extraction rises with severe decay + pain + tooth mobility; scaling rises with plaque +
   gingivitis + long time since cleaning. A coin-flip (`bernoulli`) over that probability gives
   a yes/no label, so the data has realistic relationships **plus noise**.
3. For each procedure: wrap in a Rubix `Labeled` set, **80/20 stratified split**, train a
   **`Pipeline([ZScaleStandardizer], LogisticRegression)`** (standardising so each feature
   weighs fairly), report test accuracy, and save to `storage/app/models/recommend_{target}.model`.

> As real intake/finding history accumulates, retrain on **real** data instead of synthetic —
> same command, just point the generator at real rows. The model architecture doesn't change.

### Where it shows up
The dentist's treatment workspace (Stage-1 suggestion + Stage-2 next-visit recommendation),
the per-visit **Clinical summary** on the patient record, and the patient's dashboard once a
recommendation is accepted + sent.

---

## 3. The analytics dashboard (`/admin/analytics`)

Everything respects the **filter bar** (date range, dentist, service, category, status, type).
`App\Services\Analytics\ReportFilter` parses it; `ReportService::base()` builds **one** filtered
SQL query against `appointments` (the "fact table"), left-joined to `services`, `users`
(dentist), `patients`, and a `payments` sub-query (`sum(amount) where status='paid'`). Every KPI
and chart below derives from that same filtered base, so all numbers agree.

### A. KPI cards (top row) — `ReportService::kpis()`

| Card | Formula | Source columns |
|------|---------|----------------|
| **Appointments** | `count(*)` of appointments in range | `appointments` |
| **Revenue collected** | `sum(payments.amount)` where `payments.status='paid'` | `payments.amount`, `payments.status` |
| **Charged** | `sum(appointments.total_amount)` **only** where status ∈ {billed, for_billing, in_treatment, completed} | `appointments.total_amount`, `.status` |
| **Outstanding** | `max(0, charged − collected)` | derived |
| **No-show rate** | `no_show ÷ total × 100` | `appointments.status` |
| **Cancellation rate** | `cancelled ÷ total × 100` | `appointments.status` |
| **Avg bill** | `charged ÷ billed_count` | derived |

> **Why "Charged"/"Outstanding" exclude booked/cancelled/no-show:** a visit is only *billed*
> once reception issues a statement (status `billed`, later `completed`). Booked = not billed yet;
> cancelled/no-show = never billed. Counting their estimate as "owed" was misleading, so they're
> excluded here. The value of cancelled/no-show visits is shown separately as **Est. lost revenue**.

### B. Scheduling-insights cards — `App\Services\Analytics\SchedulingInsights`
Computed from `appointments` in the date range:

| Card | Logic | Source |
|------|-------|--------|
| **Busiest day** | weekday with the most appointments (open days only) | `scheduled_at` (DAYOFWEEK) |
| **Peak hour** | clinic hour with the most bookings | `scheduled_at` (HOUR) |
| **Most underused slot** | clinic hour with the **fewest** bookings | `scheduled_at` (HOUR) |
| **No-show risk** | `(no_show + cancelled) ÷ total × 100` | `status` |
| **Est. lost revenue** | `sum(total_amount)` of cancelled + no-show in range | `total_amount`, `status` |
| **Highest workload** | dentist with the most *active* treatment-minutes | `dentist_id`, `duration_minutes`, `status` |
| **Most booked procedure** | top `procedure_name` count | `appointment_procedures.procedure_name` |
| **Schedule efficiency** | booked active minutes ÷ available chair-minutes (open days × dentists × daily hours) | `duration_minutes` + `config/clinic.php` |

### C. Charts — table, what it shows, what it tells

| Chart | Built by | Source tables/columns | What it tells you |
|-------|----------|------------------------|-------------------|
| **Trend over time** (line + bars) | `timeSeries()` | `payments.amount` (collected) + `count(*)` per day/week/month bucket of `scheduled_at` | Revenue and demand momentum over time. |
| **Appointment status mix** (stacked bar) | `timeSeries()` | `appointments.status` counted per period | How the completed/booked/no-show/cancelled mix changes — spot rising no-shows. |
| **Aggregation** (bar) | `aggregate(dim)` | group by chosen dimension (service / dentist / category / status / weekday / month / age band / type) | The chosen measure (count / charged / collected) ranked by dimension — your "slice & dice". |
| **Revenue by dental service** (bar) | `lineItemRevenue('service')` | `appointment_procedures.price` for performed procedures on **completed** visits, grouped by service | True revenue per service — correct even on multi-service visits (each procedure attributed separately). |
| **Category mix** (donut) | `lineItemRevenue('category')` | same, grouped by `services.category` | Revenue share across preventive/restorative/surgical/etc. |
| **Payment method** (donut) | `paymentMethodMix()` | `payments.amount` grouped by `payments.method` (paid only) | How patients pay (cash/GCash/card/rewards). |
| **Pivot** (cross-tab table) | `pivot(rows × month)` | base query grouped by two dimensions; cell = chosen measure | Two-dimensional comparison, e.g. service × month revenue. |
| **Demand heatmap** (day × hour) | `demandHeatmap()` | `count(*)` grouped by `DAYOFWEEK` × `HOUR` of `scheduled_at` | When the clinic is busy/idle — staffing + the predictive-scheduling signal. |
| **Patient segments** (scatter) | `segments()` (k-means) | per patient: recency/frequency/monetary (see below) | Customer segments — loyal high-value, at-risk/lapsing, new/occasional, regular. |
| **Data table + export** | `tableQuery()` | raw `appointments` rows (paginated) | The underlying records; CSV/Excel export for offline analysis. |

### D. Clustering detail (the scatter) — `segments()`
This is the second ML model on the dashboard (k-means, also Rubix ML). For each patient in the
range it computes **RFM**:
- **Recency** = `datediff(today, max(scheduled_at))` — days since last visit
- **Frequency** = `count(*)` of their appointments
- **Monetary** = `sum(payments.amount)` paid

The three values are **z-score standardised** (so one big-money patient doesn't dominate), then
`KMeans(k=3)` groups patients into clusters. Each cluster is named heuristically from its
averages (`nameSegments()`): *Loyal high-value*, *At-risk / lapsing*, *New / occasional*,
*Regular*. The scatter plots frequency (x) vs spend (y), coloured by cluster.

---

## Quick reference — files

| Concern | File |
|---------|------|
| Decision-Tree features | `app/Services/ML/AppointmentFeatureExtractor.php` |
| Decision-Tree predict | `app/Services/ML/SchedulingModel.php` |
| Decision-Tree train | `app/Console/Commands/TrainSchedulingModel.php` → `ml:scheduling:train` |
| Regression features | `app/Services/ML/IntakeFeatureExtractor.php` |
| Regression predict / mapping | `app/Services/ML/ProcedureRecommendationModel.php`, `AppointmentRecommender.php` |
| Regression train (+ synthetic data) | `app/Console/Commands/TrainRecommendationModel.php`, `ProcedureDatasetGenerator.php` → `ml:recommend:train` |
| Dashboard SQL (KPIs/charts/pivot/clustering) | `app/Services/Analytics/ReportService.php` |
| Scheduling KPIs | `app/Services/Analytics/SchedulingInsights.php` |
| Dashboard page | `resources/views/admin/analytics/index.blade.php` |
| Trained model files | `storage/app/models/*.model` |

### Retraining
```bash
php artisan ml:scheduling:train      # after more appointment history accrues
php artisan ml:recommend:train       # regression models (synthetic by default)
```
The **scheduling** model is set to retrain automatically once a month (`routes/console.php`);
run the **recommendation** retrain manually whenever you want to refresh it (e.g. after enough
real intake/finding data has accumulated).
