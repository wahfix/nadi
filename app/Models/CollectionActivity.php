<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionActivity extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const METHOD_PHONE = 'PHONE';

    public const METHOD_WHATSAPP = 'WHATSAPP';

    public const METHOD_IN_PERSON = 'IN_PERSON';

    public const METHOD_OTHER = 'OTHER';

    public const RESULT_PAID = 'PAID';

    public const RESULT_PROMISE_TO_PAY = 'PROMISE_TO_PAY';

    public const RESULT_NO_RESPONSE = 'NO_RESPONSE';

    public const RESULT_CONTACT_FAILED = 'CONTACT_FAILED';

    public const RESULT_DISPUTED = 'DISPUTED';

    public const RESULT_OTHER = 'OTHER';

    protected $fillable = [
        'loan_id',
        'customer_id',
        'collector_id',
        'contact_date',
        'contact_method',
        'result',
        'promise_to_pay_date',
        'promise_to_pay_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'contact_date' => 'datetime',
            'promise_to_pay_date' => 'date',
            'promise_to_pay_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }
}
