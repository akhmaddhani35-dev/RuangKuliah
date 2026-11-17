<?php

namespace App\Models;

use App\Models\Concerns\HasSharedRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasSharedRecords;

    protected $fillable = [
        'user_id',
        'nama',
        'kode_mk',
        'dosen',
        'sks',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'id_mata_kuliah');
    }

    public function materis(): HasMany
    {
        return $this->hasMany(Materi::class, 'id_mata_kuliah');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'id_mata_kuliah');
    }
}
