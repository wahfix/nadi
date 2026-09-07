<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReference extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const TYPE_CUSTOMER_ID = 'CUSTOMER_ID';

    public const TYPE_COLLATERAL_PROOF = 'COLLATERAL_PROOF';

    public const TYPE_RELEASE_RECEIPT = 'RELEASE_RECEIPT';

    public const TYPE_PAYMENT_RECEIPT = 'PAYMENT_RECEIPT';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
