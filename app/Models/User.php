<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string $calendar_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'calendar_color'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const ROLE_PT_ASSISTANT = 'PT Assistant';

    /**
     * Restriction permissions — unlike the `resource.action` grants, these take
     * access *away* from the roles that hold them, so a role without any of
     * them keeps the practice-wide access the app has always had. Toggle them
     * per role at /admin/roles.
     */
    public const RESTRICT_OWN_CASELOAD = 'patients.own-caseload-only';

    public const HIDE_DASHBOARD = 'dashboard.hidden';

    public const HIDE_CALENDAR = 'calendar.hidden';

    public const HIDE_DIRECTORIES = 'directories.hidden';

    /**
     * Every restriction permission, in the order the admin panel lists them.
     *
     * @var array<int, string>
     */
    public const RESTRICTIONS = [
        self::RESTRICT_OWN_CASELOAD,
        self::HIDE_DASHBOARD,
        self::HIDE_CALENDAR,
        self::HIDE_DIRECTORIES,
    ];

    /**
     * Mirrors the `calendar_color` column's database default so a freshly
     * created/unsaved instance behaves the same as one just fetched from the
     * database, before any round-trip has happened.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'calendar_color' => 'red',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The admin who personally performs each patient's first and last visit.
     * The longest-standing admin account, so the choice is stable as staff
     * accounts come and go.
     */
    public static function supervisingAdmin(): ?self
    {
        return static::role('admin')->orderBy('id')->first();
    }

    /**
     * Human-readable label for a restriction, for the admin role editor.
     */
    public static function restrictionLabel(string $restriction): string
    {
        return match ($restriction) {
            self::RESTRICT_OWN_CASELOAD => __('Only see patients assigned to them'),
            self::HIDE_DASHBOARD => __('Hide the Dashboard'),
            self::HIDE_CALENDAR => __('Hide the Calendar'),
            self::HIDE_DIRECTORIES => __('Hide Doctors, HHAs and Insurance'),
            default => $restriction,
        };
    }

    /**
     * Whether this user only sees the patients assigned to their own account.
     */
    public function isRestrictedToAssignedPatients(): bool
    {
        return $this->can(self::RESTRICT_OWN_CASELOAD);
    }

    /**
     * Whether a page guarded by the given restriction is hidden from this user.
     */
    public function isPageHidden(string $restriction): bool
    {
        return $this->can($restriction);
    }

    /**
     * The route this user lands on after logging in, and whenever they hit a
     * page a restriction hides from them.
     */
    public function landingRoute(): string
    {
        return $this->isPageHidden(self::HIDE_DASHBOARD) ? 'patients.index' : 'dashboard';
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('user');
    }
}
