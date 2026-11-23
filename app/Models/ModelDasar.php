<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model dasar yang memetakan kolom timestamp bawaan Eloquent
 * ke nama kolom Indonesia yang dipakai seluruh skema.
 */
abstract class ModelDasar extends Model
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';
}
