<?php

namespace App\Services;

use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;

class SequentialNumberService
{
    /**
     * Generate sequential code for customer: CUS-YYYY-XXXXXX
     */
    public function generateCustomerCode(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "CUS-{$year}-";

        $lastRecord = Customer::withTrashed()
            ->where('customer_code', 'like', "{$prefix}%")
            ->orderBy('customer_code', 'desc')
            ->value('customer_code');

        $nextSequence = $this->extractNextSequence($lastRecord, $prefix);

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate sequential code for loan: NADI-LOAN-YYYY-XXXXXX
     */
    public function generateLoanNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "NADI-LOAN-{$year}-";

        $lastRecord = Loan::where('loan_number', 'like', "{$prefix}%")
            ->orderBy('loan_number', 'desc')
            ->value('loan_number');

        $nextSequence = $this->extractNextSequence($lastRecord, $prefix);

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate sequential code for payment: PAY-YYYY-XXXXXX
     */
    public function generatePaymentNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "PAY-{$year}-";

        $lastRecord = Payment::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->value('payment_number');

        $nextSequence = $this->extractNextSequence($lastRecord, $prefix);

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate sequential code for collateral: COL-YYYY-XXXXXX
     */
    public function generateCollateralCode(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "COL-{$year}-";

        $lastRecord = Collateral::where('collateral_code', 'like', "{$prefix}%")
            ->orderBy('collateral_code', 'desc')
            ->value('collateral_code');

        $nextSequence = $this->extractNextSequence($lastRecord, $prefix);

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate sequential code for collateral release: REL-YYYY-XXXXXX
     */
    public function generateReleaseNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "REL-{$year}-";

        $lastRecord = CollateralRelease::where('release_number', 'like', "{$prefix}%")
            ->orderBy('release_number', 'desc')
            ->value('release_number');

        $nextSequence = $this->extractNextSequence($lastRecord, $prefix);

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Helper to extract the next sequence integer from the last string code.
     */
    private function extractNextSequence(?string $lastCode, string $prefix): int
    {
        if (! $lastCode) {
            return 1;
        }

        $rawNumber = str_replace($prefix, '', $lastCode);

        return (int) $rawNumber + 1;
    }
}
