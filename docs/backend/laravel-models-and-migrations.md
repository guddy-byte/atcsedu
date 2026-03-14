# Laravel ATCS Edu: Models and Migrations

Here is the exact code for the Models and Migrations based on the blueprint. You can copy and paste these into your new Laravel backend project.

## Migrations

You can generate these migration files by running `php artisan make:migration create_[table_name]_table`.

### 1. `create_users_table.php` (Update the default one)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        
        // Include password reset tokens and sessions if desired
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 2. `create_materials_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
```

### 3. `create_exams_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
```

### 4. `create_questions_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['objective', 'theory']);
            $table->longText('prompt');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
```

### 5. `create_question_options_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 10)->nullable(); // A/B/C/D
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
```

### 6. `create_exam_attempts_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
```

### 7. `create_exam_attempt_answers_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
```

### 8. `create_purchases_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
```

### 9. `create_payment_webhooks_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('paystack');
            $table->string('event')->nullable();
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
```

---

## Models

You can generate these models by running `php artisan make:model [ModelName]`.

### 1. `app/Models/User.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function materialsCreated() { return $this->hasMany(Material::class, 'created_by'); }
    public function examsCreated() { return $this->hasMany(Exam::class, 'created_by'); }
    public function attempts() { return $this->hasMany(ExamAttempt::class); }
    public function purchases() { return $this->hasMany(Purchase::class); }
}
```

### 2. `app/Models/Material.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'category', 'access_type', 'price', 
        'format', 'description', 'cover_url', 
        'download_url', 'is_published', 'created_by'
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
```

### 3. `app/Models/Exam.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'category', 'access_type', 'price', 
        'duration_minutes', 'description', 'is_published', 'created_by'
    ];

    public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
    public function attempts() { return $this->hasMany(ExamAttempt::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
```

### 4. `app/Models/Question.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id', 'type', 'prompt', 'points', 'position'];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function options() { return $this->hasMany(QuestionOption::class)->orderBy('position'); }
}
```

### 5. `app/Models/QuestionOption.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'label', 'option_text', 'is_correct', 'position'];

    public function question() { return $this->belongsTo(Question::class); }
}
```

### 6. `app/Models/ExamAttempt.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'exam_id', 'status', 'objective_score', 'objective_total', 
        'theory_score', 'theory_total', 'total_score', 'max_score', 
        'score_percent', 'passed', 'started_at', 'submitted_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'passed' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function answers() { return $this->hasMany(ExamAttemptAnswer::class); }
}
```

### 7. `app/Models/ExamAttemptAnswer.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id', 'question_id', 'selected_option_id', 
        'theory_answer', 'is_correct', 'awarded_points', 'review_comment'
    ];

    public function attempt() { return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id'); }
    public function question() { return $this->belongsTo(Question::class); }
    public function selectedOption() { return $this->belongsTo(QuestionOption::class, 'selected_option_id'); }
}
```

### 8. `app/Models/Purchase.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'item_type', 'item_id', 'amount', 'currency', 
        'status', 'provider', 'provider_reference', 'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
```

### 9. `app/Models/PaymentWebhook.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = ['provider', 'event', 'payload'];
    
    // Ensure the payload casts to array automatically
    protected $casts = [
        'payload' => 'array',
    ];
}
```
