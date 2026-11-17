<?php

namespace App\Models;

use App\Models\Concerns\HasSharedRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materi extends Model
{
    use HasSharedRecords;

    protected $fillable = [
        'user_id',
        'id_mata_kuliah',
        'judul',
        'nama_mata_kuliah',
        'pertemuan',
        'tipe',
        'detail',
        'file_path',
        'file_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'id_mata_kuliah');
    }
}
