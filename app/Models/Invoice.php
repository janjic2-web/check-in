<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    public const STATUS_PAID    = 'paid';
    public const STATUS_OPEN    = 'open';
    public const STATUS_VOID    = 'void';
    public const STATUS_REFUND  = 'refunded';

    protected $fillable = [
        'company_id',
        'sub_id',
        'amount',               // čuvaj u centima
        'currency',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'provider_invoice_id',
        'pdf_url',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'sub_id'     => 'integer',
        'amount'     => 'integer',
        'issued_at'  => 'datetime',
        'due_at'     => 'datetime',
        'paid_at'    => 'datetime',
    ];
}
