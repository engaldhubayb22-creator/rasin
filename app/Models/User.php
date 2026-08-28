<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'phone',
        'job_title',
        'department',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // العلاقات
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // ===== نظام الأدوار =====
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EXECUTIVE = 'executive';
    public const ROLE_PM = 'project_manager';
    public const ROLE_ENGINEER = 'engineer';
    public const ROLE_FINANCE = 'finance';

    /** الأدوار ومفاتيح الترجمة */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN => 'role_admin',
            self::ROLE_EXECUTIVE => 'role_executive',
            self::ROLE_PM => 'role_pm',
            self::ROLE_ENGINEER => 'role_engineer',
            self::ROLE_FINANCE => 'role_finance',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return __('app.'.(self::roles()[$this->role] ?? 'role_admin'));
    }

    /** أي لوحة رئيسية يشوفها المستخدم حسب دوره */
    public function homeView(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'all',        // المالك/الأدمن يشوف الكل
            self::ROLE_EXECUTIVE => 'exec',
            self::ROLE_PM => 'pm',
            self::ROLE_FINANCE => 'finance',
            self::ROLE_ENGINEER => 'engineer',
            default => 'engineer',
        };
    }

    public function canSeeFinance(): bool
    {
        return $this->can('finance.view');
    }

    public function canSeeReports(): bool
    {
        return $this->can('reports.view');
    }

    // ===== نظام الصلاحيات (module.action) =====

    /** ذاكرة مؤقتة لصلاحيات الدور خلال الطلب */
    protected ?array $permCache = null;

    /** كل صلاحيات دور المستخدم (admin يملك الكل) */
    public function permissions(): array
    {
        if ($this->role === self::ROLE_ADMIN) {
            return RolePermission::catalog();
        }

        if ($this->permCache === null) {
            $this->permCache = RolePermission::where('role', $this->role)
                ->pluck('permission')->all();
        }

        return $this->permCache;
    }

    /** هل يملك صلاحية معيّنة؟ (admin دائماً نعم) */
    public function can($permission, $arguments = []): bool
    {
        if (! is_string($permission)) {
            return parent::can($permission, $arguments);
        }

        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        return in_array($permission, $this->permissions(), true);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }
}
