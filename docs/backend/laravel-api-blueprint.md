# ATCS Edu Laravel API Blueprint (Frontend-Tailored)

This blueprint maps directly to the current Vue frontend flows:
- Student auth (`/auth/signup`, `/auth/login`, `/exam-training`)
- Admin auth (`/admin/auth/login`, `/admin`)
- Materials (free/paid)
- Exam dashboard with objective + theory attempts and stats
- Paid exam unlock flow (Paystack)

---

## 1) Stack + Project Setup

- Laravel 11+
- PHP 8.2+
- MySQL 8+
- Laravel Sanctum (SPA/API auth)
- Spatie Permissions (recommended for role management) OR native enum role
- Paystack (transaction initialize + webhook verify)

### Install

```bash
composer create-project laravel/laravel atcsedu-api
cd atcsedu-api
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

> Optional role package:
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

---

## 2) Database Schema (Migrations)

## 2.1 users (students + admins)

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['student', 'admin'])->default('student');
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

## 2.2 materials

```php
Schema::create('materials', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('category');
    $table->enum('access_type', ['free', 'paid']);
    $table->decimal('price', 12, 2)->default(0);
    $table->string('format')->nullable(); // pdf, video, bundle
    $table->text('description')->nullable();
    $table->string('cover_url')->nullable();
    $table->string('download_url')->nullable();
    $table->boolean('is_published')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
    $table->timestamps();
});
```

## 2.3 exams

```php
Schema::create('exams', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('category')->nullable();
    $table->enum('access_type', ['free', 'paid'])->default('free');
    $table->decimal('price', 12, 2)->default(0);
    $table->unsignedInteger('duration_minutes')->default(30);
    $table->text('description')->nullable();
    $table->boolean('is_published')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
    $table->timestamps();
});
```

## 2.4 questions

```php
Schema::create('questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['objective', 'theory']);
    $table->longText('prompt');
    $table->unsignedInteger('points')->default(1);
    $table->unsignedInteger('position')->default(1);
    $table->timestamps();
});
```

## 2.5 question_options (objective)

```php
Schema::create('question_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('question_id')->constrained()->cascadeOnDelete();
    $table->string('label', 10)->nullable(); // A/B/C/D
    $table->string('option_text');
    $table->boolean('is_correct')->default(false);
    $table->unsignedInteger('position')->default(1);
    $table->timestamps();
});
```

## 2.6 exam_attempts

```php
Schema::create('exam_attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('submitted');
    $table->unsignedInteger('objective_score')->default(0);
    $table->unsignedInteger('objective_total')->default(0);
    $table->unsignedInteger('theory_score')->default(0);
    $table->unsignedInteger('theory_total')->default(0);
    $table->unsignedInteger('total_score')->default(0);
    $table->unsignedInteger('max_score')->default(0);
    $table->decimal('score_percent', 5, 2)->default(0);
    $table->boolean('passed')->default(false);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('submitted_at')->nullable();
    $table->timestamps();
});
```

## 2.7 exam_attempt_answers

```php
Schema::create('exam_attempt_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
    $table->foreignId('question_id')->constrained()->cascadeOnDelete();
    $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
    $table->longText('theory_answer')->nullable();
    $table->boolean('is_correct')->nullable();
    $table->unsignedInteger('awarded_points')->default(0);
    $table->text('review_comment')->nullable();
    $table->timestamps();
});
```

## 2.8 purchases (exam/material unlock)

```php
Schema::create('purchases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('item_type', ['exam', 'material']);
    $table->unsignedBigInteger('item_id');
    $table->decimal('amount', 12, 2);
    $table->string('currency', 10)->default('NGN');
    $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
    $table->string('provider')->default('paystack');
    $table->string('provider_reference')->nullable()->unique();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();

    $table->index(['item_type', 'item_id']);
});
```

## 2.9 payment_webhooks (audit)

```php
Schema::create('payment_webhooks', function (Blueprint $table) {
    $table->id();
    $table->string('provider')->default('paystack');
    $table->string('event')->nullable();
    $table->longText('payload');
    $table->timestamps();
});
```

---

## 3) Eloquent Models + Relationships

## User

```php
class User extends Authenticatable {
  public function materialsCreated() { return $this->hasMany(Material::class, 'created_by'); }
  public function examsCreated() { return $this->hasMany(Exam::class, 'created_by'); }
  public function attempts() { return $this->hasMany(ExamAttempt::class); }
  public function purchases() { return $this->hasMany(Purchase::class); }
}
```

## Material

```php
class Material extends Model {
  protected $fillable = ['title','category','access_type','price','format','description','cover_url','download_url','is_published','created_by'];
  public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
```

## Exam

```php
class Exam extends Model {
  protected $fillable = ['title','slug','category','access_type','price','duration_minutes','description','is_published','created_by'];
  public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
  public function attempts() { return $this->hasMany(ExamAttempt::class); }
  public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
```

## Question

```php
class Question extends Model {
  protected $fillable = ['exam_id','type','prompt','points','position'];
  public function exam() { return $this->belongsTo(Exam::class); }
  public function options() { return $this->hasMany(QuestionOption::class)->orderBy('position'); }
}
```

## QuestionOption

```php
class QuestionOption extends Model {
  protected $fillable = ['question_id','label','option_text','is_correct','position'];
  public function question() { return $this->belongsTo(Question::class); }
}
```

## ExamAttempt

```php
class ExamAttempt extends Model {
  protected $fillable = ['user_id','exam_id','status','objective_score','objective_total','theory_score','theory_total','total_score','max_score','score_percent','passed','started_at','submitted_at'];
  public function user() { return $this->belongsTo(User::class); }
  public function exam() { return $this->belongsTo(Exam::class); }
  public function answers() { return $this->hasMany(ExamAttemptAnswer::class); }
}
```

## ExamAttemptAnswer

```php
class ExamAttemptAnswer extends Model {
  protected $fillable = ['exam_attempt_id','question_id','selected_option_id','theory_answer','is_correct','awarded_points','review_comment'];
  public function attempt() { return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id'); }
  public function question() { return $this->belongsTo(Question::class); }
  public function selectedOption() { return $this->belongsTo(QuestionOption::class, 'selected_option_id'); }
}
```

## Purchase

```php
class Purchase extends Model {
  protected $fillable = ['user_id','item_type','item_id','amount','currency','status','provider','provider_reference','paid_at'];
  public function user() { return $this->belongsTo(User::class); }
}
```

---

## 4) Routes (api.php)

```php
Route::prefix('v1')->group(function () {
    // Public
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/materials', [MaterialController::class, 'index']);
    Route::get('/materials/{material}', [MaterialController::class, 'show']);

    Route::get('/exams', [ExamController::class, 'index']);
    Route::get('/exams/{exam}', [ExamController::class, 'show']);

    Route::post('/payments/paystack/webhook', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Student
        Route::get('/student/dashboard/stats', [StudentDashboardController::class, 'stats']);
        Route::get('/student/attempts', [StudentDashboardController::class, 'attempts']);

        Route::post('/exams/{exam}/attempts', [ExamAttemptController::class, 'store']);
        Route::get('/exams/{exam}/access', [ExamAccessController::class, 'show']);

        Route::post('/payments/paystack/initialize', [PaymentController::class, 'initialize']);
        Route::get('/payments/{reference}/verify', [PaymentController::class, 'verify']);

        // Admin
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

            Route::apiResource('/materials', AdminMaterialController::class);
            Route::apiResource('/exams', AdminExamController::class);

            Route::post('/exams/{exam}/questions', [AdminQuestionController::class, 'store']);
            Route::put('/questions/{question}', [AdminQuestionController::class, 'update']);
            Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy']);

            Route::post('/attempts/{attempt}/grade-theory', [AdminAttemptController::class, 'gradeTheory']);
        });
    });
});
```

---

## 5) Controller Stubs (Method Map)

## AuthController
- `register(RegisterRequest $request)`
- `login(LoginRequest $request)`
- `me(Request $request)`
- `logout(Request $request)`

## MaterialController
- `index(Request $request)` (filters: `access_type`, `q`, pagination)
- `show(Material $material)`

## ExamController
- `index(Request $request)` (filters: `access_type`, `q`)
- `show(Exam $exam)` (hide correct answers for students)

## ExamAccessController
- `show(Exam $exam)` => `{ has_access: boolean, reason: "free|purchased|required_payment" }`

## ExamAttemptController
- `store(SubmitExamAttemptRequest $request, Exam $exam)`
  - Validate access for paid exams
  - Auto-score objective answers
  - Save theory answers for admin review
  - Return attempt + score summary

## PaymentController
- `initialize(InitializePaymentRequest $request)` (create pending purchase + return Paystack auth URL)
- `verify(string $reference)` (verify + mark purchase paid)
- `webhook(Request $request)` (verify Paystack signature + update purchase)

## StudentDashboardController
- `stats(Request $request)`
- `attempts(Request $request)`

## AdminDashboardController
- `stats()`

## AdminMaterialController (resource)
- `index/store/show/update/destroy`

## AdminExamController (resource)
- `index/store/show/update/destroy`

## AdminQuestionController
- `store(StoreQuestionRequest $request, Exam $exam)`
- `update(UpdateQuestionRequest $request, Question $question)`
- `destroy(Question $question)`

## AdminAttemptController
- `gradeTheory(GradeTheoryRequest $request, ExamAttempt $attempt)`

---

## 6) Exact JSON Contracts

## 6.1 Auth

### `POST /api/v1/auth/register`
Request:
```json
{
  "name": "Jane Student",
  "email": "jane@example.com",
  "password": "Secret123!",
  "password_confirmation": "Secret123!"
}
```
Response `201`:
```json
{
  "status": "success",
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 15,
      "name": "Jane Student",
      "email": "jane@example.com",
      "role": "student"
    },
    "token": "1|sanctum_token_here"
  }
}
```

### `POST /api/v1/auth/login`
Request:
```json
{
  "email": "jane@example.com",
  "password": "Secret123!"
}
```
Response `200`:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 15,
      "name": "Jane Student",
      "email": "jane@example.com",
      "role": "student"
    },
    "token": "2|sanctum_token_here"
  }
}
```

### `GET /api/v1/auth/me`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 15,
      "name": "Jane Student",
      "email": "jane@example.com",
      "role": "student"
    }
  }
}
```

---

## 6.2 Materials

### `GET /api/v1/materials?access_type=free&q=english&page=1`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "items": [
      {
        "id": 1,
        "title": "English Starter Pack",
        "category": "English",
        "access_type": "free",
        "price": 0,
        "format": "pdf",
        "description": "...",
        "cover_url": "https://...",
        "download_url": "https://..."
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 12,
      "total": 42,
      "last_page": 4
    }
  }
}
```

---

## 6.3 Exams

### `GET /api/v1/exams?access_type=paid`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "items": [
      {
        "id": 10,
        "title": "WAEC Complete Mock CBT",
        "slug": "waec-complete-mock-cbt",
        "category": "Premium mock",
        "access_type": "paid",
        "price": 7500,
        "duration_minutes": 80,
        "description": "...",
        "questions_count": 60
      }
    ]
  }
}
```

### `GET /api/v1/exams/{id}`
Response `200` (student-safe, no correct answers):
```json
{
  "status": "success",
  "data": {
    "exam": {
      "id": 10,
      "title": "WAEC Complete Mock CBT",
      "access_type": "paid",
      "price": 7500,
      "duration_minutes": 80,
      "questions": [
        {
          "id": 501,
          "type": "objective",
          "prompt": "Nigeria gained independence in which year?",
          "points": 1,
          "position": 1,
          "options": [
            { "id": 9001, "label": "A", "option_text": "1957" },
            { "id": 9002, "label": "B", "option_text": "1960" },
            { "id": 9003, "label": "C", "option_text": "1963" }
          ]
        },
        {
          "id": 502,
          "type": "theory",
          "prompt": "Write a short strategy for managing time in long-format exams.",
          "points": 5,
          "position": 2,
          "options": []
        }
      ]
    }
  }
}
```

### `GET /api/v1/exams/{id}/access`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "exam_id": 10,
    "has_access": false,
    "reason": "required_payment"
  }
}
```

---

## 6.4 Exam Attempt Submission

### `POST /api/v1/exams/{id}/attempts`
Request:
```json
{
  "answers": [
    { "question_id": 501, "selected_option_id": 9002 },
    { "question_id": 502, "theory_answer": "I allocate time per section and keep a 10-minute review buffer." }
  ]
}
```

Response `201`:
```json
{
  "status": "success",
  "message": "Exam submitted successfully",
  "data": {
    "attempt": {
      "id": 2201,
      "exam_id": 10,
      "status": "submitted",
      "objective_score": 1,
      "objective_total": 1,
      "theory_score": 0,
      "theory_total": 5,
      "total_score": 1,
      "max_score": 6,
      "score_percent": 16.67,
      "passed": false,
      "submitted_at": "2026-03-12T11:22:33Z"
    },
    "summary": {
      "objective": {
        "answered": 1,
        "correct": 1,
        "total": 1
      },
      "theory": {
        "answered": 1,
        "pending_review": true
      }
    }
  }
}
```

---

## 6.5 Student Dashboard

### `GET /api/v1/student/dashboard/stats`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "total_exams_on_platform": 35,
    "exams_attempted": 12,
    "exams_passed": 8,
    "exams_failed": 4,
    "free_exams_available": 20,
    "paid_exams_available": 15,
    "paid_exams_unlocked": 3
  }
}
```

### `GET /api/v1/student/attempts?page=1`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "items": [
      {
        "attempt_id": 2201,
        "exam_id": 10,
        "exam_title": "WAEC Complete Mock CBT",
        "score_percent": 16.67,
        "passed": false,
        "status": "submitted",
        "submitted_at": "2026-03-12T11:22:33Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 10,
      "total": 12,
      "last_page": 2
    }
  }
}
```

---

## 6.6 Paystack

### `POST /api/v1/payments/paystack/initialize`
Request:
```json
{
  "item_type": "exam",
  "item_id": 10
}
```
Response `200`:
```json
{
  "status": "success",
  "data": {
    "reference": "ATCS-PSK-20260312-ABC123",
    "authorization_url": "https://checkout.paystack.com/xxxx",
    "access_code": "xxxx"
  }
}
```

### `GET /api/v1/payments/{reference}/verify`
Response `200`:
```json
{
  "status": "success",
  "message": "Payment verified",
  "data": {
    "reference": "ATCS-PSK-20260312-ABC123",
    "payment_status": "paid",
    "purchase": {
      "id": 880,
      "item_type": "exam",
      "item_id": 10,
      "status": "paid",
      "paid_at": "2026-03-12T11:44:01Z"
    }
  }
}
```

### `POST /api/v1/payments/paystack/webhook`
- Verify `x-paystack-signature`
- Save payload in `payment_webhooks`
- Update matching `purchase` to `paid` on successful event

Response `200`:
```json
{ "status": "ok" }
```

---

## 6.7 Admin APIs

### `GET /api/v1/admin/dashboard/stats`
Response `200`:
```json
{
  "status": "success",
  "data": {
    "students": 1400,
    "materials": 520,
    "exams": 35,
    "attempts": 10300,
    "revenue_total": 12750000,
    "pending_theory_reviews": 242
  }
}
```

### `POST /api/v1/admin/exams`
Request:
```json
{
  "title": "JAMB Elite Simulation",
  "slug": "jamb-elite-simulation",
  "category": "Premium mock",
  "access_type": "paid",
  "price": 12000,
  "duration_minutes": 90,
  "description": "Full simulation",
  "is_published": true
}
```
Response `201`:
```json
{
  "status": "success",
  "message": "Exam created",
  "data": {
    "exam": {
      "id": 17,
      "title": "JAMB Elite Simulation",
      "slug": "jamb-elite-simulation"
    }
  }
}
```

### `POST /api/v1/admin/exams/{exam}/questions`
Request:
```json
{
  "type": "objective",
  "prompt": "The capital of Nigeria is?",
  "points": 1,
  "position": 1,
  "options": [
    { "label": "A", "option_text": "Lagos", "is_correct": false, "position": 1 },
    { "label": "B", "option_text": "Abuja", "is_correct": true, "position": 2 },
    { "label": "C", "option_text": "Kano", "is_correct": false, "position": 3 }
  ]
}
```
Response `201`:
```json
{
  "status": "success",
  "message": "Question created",
  "data": {
    "question": {
      "id": 777,
      "exam_id": 17,
      "type": "objective"
    }
  }
}
```

### `POST /api/v1/admin/attempts/{attempt}/grade-theory`
Request:
```json
{
  "grades": [
    { "question_id": 502, "awarded_points": 4, "review_comment": "Good structure" }
  ]
}
```
Response `200`:
```json
{
  "status": "success",
  "message": "Theory grading completed",
  "data": {
    "attempt_id": 2201,
    "score_percent": 83.33,
    "passed": true,
    "status": "graded"
  }
}
```

---

## 7) Validation Rules (Request Classes)

- `RegisterRequest`
  - `name: required|string|max:120`
  - `email: required|email|unique:users,email`
  - `password: required|string|min:8|confirmed`

- `LoginRequest`
  - `email: required|email`
  - `password: required|string`

- `SubmitExamAttemptRequest`
  - `answers: required|array|min:1`
  - `answers.*.question_id: required|integer|exists:questions,id`
  - `answers.*.selected_option_id: nullable|integer|exists:question_options,id`
  - `answers.*.theory_answer: nullable|string`

- `InitializePaymentRequest`
  - `item_type: required|in:exam,material`
  - `item_id: required|integer`

---

## 8) Frontend Integration Mapping (Current Vue)

- Student signup/login (`AuthPanel.vue`)
  - swap localStorage auth with:
    - `POST /auth/register`
    - `POST /auth/login`
    - store Sanctum token in secure cookie/session strategy

- Admin login (`AdminLoginView.vue`)
  - replace demo login with `POST /auth/login` role=`admin`
  - route guard calls `/auth/me`

- Exam dashboard (`ExamTrainingView.vue`)
  - list exams: `GET /exams`
  - exam details/questions: `GET /exams/{id}`
  - submit attempts: `POST /exams/{id}/attempts`
  - stats cards: `GET /student/dashboard/stats`
  - recent attempts: `GET /student/attempts`

- Paid unlock flow
  - initialize payment: `POST /payments/paystack/initialize`
  - verify after redirect: `GET /payments/{reference}/verify`
  - access gate: `GET /exams/{id}/access`

---

## 9) Seeders (Minimum)

- Admin user seeder:
  - email: `admin@atcsedu.com`
  - password: hashed secure password
  - role: `admin`
- 1 free exam, 1 paid exam, mixed objective/theory questions
- 5 materials (free + paid)

---

## 10) Suggested File Skeleton

```txt
app/
  Http/
    Controllers/Api/V1/
      AuthController.php
      MaterialController.php
      ExamController.php
      ExamAccessController.php
      ExamAttemptController.php
      PaymentController.php
      StudentDashboardController.php
      Admin/
        AdminDashboardController.php
        AdminMaterialController.php
        AdminExamController.php
        AdminQuestionController.php
        AdminAttemptController.php
    Requests/Api/V1/
      RegisterRequest.php
      LoginRequest.php
      SubmitExamAttemptRequest.php
      InitializePaymentRequest.php
      Admin/
        StoreExamRequest.php
        StoreQuestionRequest.php
        GradeTheoryRequest.php
  Models/
    User.php
    Material.php
    Exam.php
    Question.php
    QuestionOption.php
    ExamAttempt.php
    ExamAttemptAnswer.php
    Purchase.php
```

---

## 11) Notes for Production Safety

- Never return correct objective answers in student exam payload.
- Verify Paystack webhook signatures before processing.
- Use DB transactions in `ExamAttemptController@store` and payment verification.
- Add rate limiting to login and webhook routes.
- Store secrets (`PAYSTACK_SECRET_KEY`) in `.env` only.

