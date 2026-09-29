<?php

namespace App\Models;

use Database\Factories\NoteAddendumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A timestamped correction attached to an already-signed Note. Notes are
 * never edited or overwritten once signed (see Note::isSigned() and the
 * `notes.manage`-gated delete restriction on the show page) — a correction
 * is recorded here instead, alongside the original, per HIPAA's requirement
 * that ePHI not be silently altered.
 */
#[Fillable(['note_id', 'author_id', 'content'])]
class NoteAddendum extends Model
{
    /** @use HasFactory<NoteAddendumFactory> */
    use HasFactory, LogsActivity;

    /**
     * Eloquent's pluralizer doesn't know "addendum" → "addenda".
     */
    protected $table = 'note_addenda';

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['note_id', 'author_id', 'content'])
            ->useLogName('note');
    }
}
