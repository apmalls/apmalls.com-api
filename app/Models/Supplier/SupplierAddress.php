<?php

namespace App\Models\Supplier;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'supplier_id',

        'address_type',

        'contact_person',

        'mobile',

        'alternate_mobile',

        'email',

        'address_line_1',

        'address_line_2',

        'landmark',

        'city',

        'state',

        'country',

        'postal_code',

        'is_default',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'is_default' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
