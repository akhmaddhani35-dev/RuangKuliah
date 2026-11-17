<?php

namespace App\Models;

use App\Models\Concerns\HasSharedRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaUjian extends Model
{
    use HasSharedRecords;

    protected $fillable = [
        'user_id',
        'id_mata_kuliah',
        'jenis',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'ruang',
        'mode',
        'link_zoom',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'id_mata_kuliah');
    }
}
