<?php

namespace App\Models;

use App\Models\Concerns\HasSharedRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    use HasSharedRecords;

    protected $fillable = [
        'user_id',
        'id_mata_kuliah',
        'nama_mata_kuliah',
        'hari',
        'waktu_mulai',
        'waktu_selesai',
        'ruang',
        'dosen',
        'mode',
        'link_zoom',
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
